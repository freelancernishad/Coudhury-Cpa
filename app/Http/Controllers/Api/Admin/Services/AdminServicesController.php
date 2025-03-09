<?php

namespace App\Http\Controllers\Api\Admin\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminServicesController extends Controller
{
    /**
     * Display a listing of the services (only non-private).
     */
    public function index()
    {
        // Only show services that are not private and have no parent (root-level services)
        $services = Service::with('children')
            ->whereNull('parent_id')
            ->where('is_private', false) // Filter out private services
            ->get();

        return response()->json($services, 200);
    }

    public function allservices()
    {
        // Only show services that are not private and have no parent (root-level services)
        $services = Service::with('children')
            ->whereNull('parent_id')
            // ->where('is_private', false)
            ->get();

        return response()->json($services, 200);
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:services,id',
            'input_label' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'is_select_multiple_child' => 'nullable',
            'is_add_on' => 'nullable',
            'is_state_select' => 'nullable',
            'is_need_appointment' => 'nullable',
            'is_private' => 'nullable|boolean', // Allow setting private status
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the service
        $service = Service::create($request->only([
            'name', 'slug', 'parent_id', 'input_label', 'price',
            'is_select_multiple_child', 'is_add_on', 'is_state_select',
            'is_need_appointment', 'is_private'
        ]));

        return response()->json($service, 201);
    }

    /**
     * Store multiple services with optional parent IDs.
     */
    public function store2(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|array', // Allow parent_id to be an array
            'parent_id.*' => 'nullable|exists:services,id', // Validate each parent_id in the array
            'input_label' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'is_select_multiple_child' => 'nullable',
            'is_add_on' => 'nullable',
            'is_state_select' => 'nullable',
            'is_need_appointment' => 'nullable',
            'is_private' => 'nullable|boolean', // Allow setting private status
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the parent_ids from the request
        $parentIds = $request->input('parent_id', []);

        // If no parent_ids are provided, create a single service
        if (empty($parentIds)) {
            $service = Service::create($request->only([
                'name', 'slug', 'input_label', 'price',
                'is_select_multiple_child', 'is_add_on', 'is_state_select',
                'is_need_appointment', 'is_private'
            ]));
            return response()->json($service, 201);
        }

        // Create a service for each parent_id
        $createdServices = [];
        foreach ($parentIds as $parentId) {
            $service = Service::create([
                'name' => $request->input('name'),
                'parent_id' => $parentId,
                'input_label' => $request->input('input_label'),
                'price' => $request->input('price'),
                'is_select_multiple_child' => $request->input('is_select_multiple_child'),
                'is_add_on' => $request->input('is_add_on'),
                'is_state_select' => $request->input('is_state_select'),
                'is_need_appointment' => $request->input('is_need_appointment'),
                'is_private' => $request->input('is_private', false), // Default to false if not provided
            ]);
            $createdServices[] = $service;
        }

        return response()->json([
            'success' => true,
            'message' => 'Services created successfully.',
            'data' => $createdServices,
        ], 201);
    }

    /**
     * Display the specified service with its children (only if not private).
     */
    public function show($id)
    {
        // Find the service and ensure it is not private
        $service = Service::with('children')
            ->where('id', $id)
            ->where('is_private', false) // Filter out private services
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found or is private'], 404);
        }

        return response()->json($service, 200);
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:services,id|not_in:' . $id,
            'input_label' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'is_select_multiple_child' => 'nullable',
            'is_add_on' => 'nullable',
            'is_state_select' => 'nullable',
            'is_need_appointment' => 'nullable',
            'is_private' => 'nullable|boolean', // Allow updating private status
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find the service
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        // Update the service
        $service->update($request->only([
            'name', 'slug', 'parent_id', 'input_label', 'price',
            'is_select_multiple_child', 'is_add_on', 'is_state_select',
            'is_need_appointment', 'is_private'
        ]));

        return response()->json(['message' => 'Service updated successfully', 'service' => $service], 200);
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        // Delete the service along with its children
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully'], 200);
    }

    /**
     * Get all non-private services for dropdown or other purposes.
     */
    public function list()
    {
        // Only show services that are not private
        $services = Service::where('is_private', false)->get();
        return response()->json($services, 200);
    }

    /**
     * Reassign child services and update the parent_id of the specified service (only if not private).
     */
    public function reassignAndUpdateParent(Request $request, $id)
    {
        // Find the service and ensure it is not private
        $service = Service::where('id', $id)
            ->where('is_private', false) // Filter out private services
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found or is private'], 404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'new_parent_id' => 'nullable|exists:services,id|not_in:' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $newParentId = $request->input('new_parent_id'); // The new parent ID from the request

        // Reassign child services
        if ($service->children()->exists()) {
            foreach ($service->children as $child) {
                $child->update(['parent_id' => $service->parent_id]);
            }
        }

        // Update the service's parent_id
        if ($newParentId) {
            $service->update(['parent_id' => $newParentId]);
        } else {
            $service->update(['parent_id' => null]);
        }

        return response()->json(['message' => 'Service updated successfully, and children reassigned', 'service' => $service], 200);
    }
}
