<?php

namespace App\Http\Controllers\Api\Admin\ServicePurchased;

use Illuminate\Http\Request;
use App\Models\ServicePurchased;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\ServicePurchasedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServicePurchasedController extends Controller
{
    /**
     * Get the latest list of ServicePurchased records.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Pagination and filters
        $perPage = $request->query('per_page', 10); // Default to 10 records per page
        $status = $request->query('status'); // Filter by status (e.g., 'pending', 'completed', 'failed')
        $search = $request->query('search'); // Global search term

        // Query builder
        $query = ServicePurchased::with(['user', 'files'])
            ->latest();

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', 'pending');
        }

        // Handle user_id based on the guard
        if (Auth::guard('admin')->check()) {
            $query->where('status', 'In Review');
        }



        if (Auth::guard('user')->check()) {
            // If the guard is 'user', get the user_id from the authenticated user
            $query->where('user_id', Auth::guard('user')->id());
        } elseif ($request->has('user_id')) {
            // For other guards, get the user_id from the request
            $query->where('user_id', $request->query('user_id'));
        }

        // Apply global search
        if ($search) {
            $query->where(function ($q) use ($search) {
                // Search by service name (within service_details JSON)
                $q->where('service_details', 'like', '%' . $search . '%');

                // Search by user name (via user relationship)
                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                              ->orWhere('email', 'like', '%' . $search . '%')
                              ->orWhere('client_id', 'like', '%' . $search . '%');
                });

                // Search by date (format: Y-m-d)
                $q->orWhereDate('date', 'like', '%' . $search . '%');
            });
        }

        // Paginate results
        $servicePurchasedList = $query->paginate($perPage);

        return response()->json($servicePurchasedList);
    }

    /**
     * Get a single ServicePurchased record by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Find the ServicePurchased record
        $servicePurchased = ServicePurchased::with(['user', 'payments'])
        ->where('status', 'In Review')
        ->find($id);

        if (!$servicePurchased) {
            return response()->json([
                'success' => false,
                'message' => 'ServicePurchased record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $servicePurchased,
        ]);
    }

    /**
     * Delete a ServicePurchased record by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Find the ServicePurchased record
        $servicePurchased = ServicePurchased::find($id);

        if (!$servicePurchased) {
            return response()->json([
                'success' => false,
                'message' => 'ServicePurchased record not found.',
            ], 404);
        }


        $servicePurchased->delete();

        return response()->json([
            'success' => true,
            'message' => 'ServicePurchased record deleted successfully.',
        ]);
    }




    /**
     * Change the status of a ServicePurchased record.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:In Review,Reviewed,Due Added,Working,Complete', // Allowed statuses
        ]);

        // Find the ServicePurchased record
        $servicePurchased = ServicePurchased::find($id);

        if (!$servicePurchased) {
            return response()->json([
                'success' => false,
                'message' => 'ServicePurchased record not found.',
            ], 404);
        }

        // Update the status
        $servicePurchased->status = $request->input('status');
        $servicePurchased->save();

        return response()->json([
            'success' => true,
            'message' => 'ServicePurchased status updated successfully.',
            'data' => $servicePurchased,
        ]);
    }

    public function addDueAmount(Request $request, int $id): JsonResponse
    {
        // Validate the request
        $request->validate([
            'due_amount' => 'required|numeric|min:0', // Ensure due_amount is a positive number
        ]);

        // Find the ServicePurchased record
        $servicePurchased = ServicePurchased::find($id);

        if (!$servicePurchased) {
            return response()->json([
                'success' => false,
                'message' => 'ServicePurchased record not found.',
            ], 404);
        }

        // Update the due_amount
        $servicePurchased->due_amount += $request->input('due_amount'); // Add the new due amount to the existing one
        $servicePurchased->save();

        return response()->json([
            'success' => true,
            'message' => 'Due amount added successfully.',
            'data' => $servicePurchased,
        ]);
    }


    public function removeDueAmount(Request $request, int $id): JsonResponse
    {
        // Validate the request
        $request->validate([
            'due_amount' => 'required|numeric|min:0', // Ensure due_amount is a positive number
        ]);

        // Find the ServicePurchased record
        $servicePurchased = ServicePurchased::find($id);

        if (!$servicePurchased) {
            return response()->json([
                'success' => false,
                'message' => 'ServicePurchased record not found.',
            ], 404);
        }

        // Calculate the new due_amount
        $newDueAmount = $servicePurchased->due_amount - $request->input('due_amount');

        // Ensure the due_amount does not go below zero
        if ($newDueAmount < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Due amount cannot be negative.',
            ], 400);
        }

        // Update the due_amount
        $servicePurchased->due_amount = $newDueAmount;
        $servicePurchased->save();

        return response()->json([
            'success' => true,
            'message' => 'Due amount removed successfully.',
            'data' => $servicePurchased,
        ]);
    }




    public function createServicePurchased(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'service_details' => 'required', // Ensure service_details is a valid JSON string
            'status' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:jpeg,png,pdf,doc,docx',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get the user ID from the request
        $userId = $request->input('user_id');

        // Decode the service_details JSON string into an array
        $serviceDetails = $request->input('service_details');

        if (is_string($serviceDetails)) {
            $serviceDetails = json_decode($serviceDetails, true);
        }

        if (!is_array($serviceDetails)) {
            return response()->json(['error' => 'Invalid service_details JSON'], 400);
        }

        // Extract amount from service_details
        $amount = $serviceDetails['total_price'] ?? 0;
        $notes = $serviceDetails['notes'] ?? '';
        $status = $request->input('status');

        if ($amount <= 0) {
            return response()->json(['error' => 'Invalid total price in service details'], 400);
        }

        // Create the ServicePurchased record
        $servicePurchased = ServicePurchased::create([
            'user_id' => $userId,
            'date' => now(),
            'subtotal' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'status' => $status,
            'client_note' => $notes,
            'admin_note' => null,
            'discount_amount' => 0,
            'service_details' => $serviceDetails, // Store the decoded service_details
        ]);

        // Handle file uploads from the request
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            foreach ($files as $file) {
                ServicePurchasedFile::ServicePurchasedFileUpload($file, $servicePurchased->id, $userId);
            }
        }

        return response()->json([
            'service_purchased_id' => $servicePurchased->id,
            'message' => 'Service purchased record created successfully.',
        ]);
    }




    public function updateAdminNote(Request $request, $id)
    {
        // Validate the request data
        $validated = $request->validate([
            'admin_note' => 'required|string|max:255',
        ]);

        // Find the service purchased record by ID
        $servicePurchased = ServicePurchased::findOrFail($id);

        // Update the admin_note field
        $servicePurchased->admin_note = $validated['admin_note'];
        $servicePurchased->save();

        // Return a successful response
        return response()->json([
            'message' => 'Admin note updated successfully.',
            'service_purchased' => $servicePurchased
        ]);
    }



    public function updateAdminPrivateNote(Request $request, $id)
    {
        // Validate the request data
        $validated = $request->validate([
            'admin_private_note' => 'required|string|max:255',
        ]);

        // Find the service purchased record by ID
        $servicePurchased = ServicePurchased::findOrFail($id);

        // Update the admin_private_note field
        $servicePurchased->admin_private_note = $validated['admin_private_note'];
        $servicePurchased->save();

        // Return a successful response
        return response()->json([
            'message' => 'Admin Private note updated successfully.',
            'service_purchased' => $servicePurchased
        ]);
    }


}
