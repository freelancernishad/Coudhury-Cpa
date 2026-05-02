<?php

namespace App\Http\Controllers\Api\Admin\Resources;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminResourcesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $resources = Resource::orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($resources, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:resources,slug',
            'visibility' => 'required|string',
            'blocks' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $resource = Resource::create($request->all());
        return response()->json($resource, 201);
    }

    public function show($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json($resource, 200);
    }

    public function update(Request $request, $id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:resources,slug,' . $id,
            'visibility' => 'required|string',
            'blocks' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $resource->update($request->all());

        return response()->json($resource, 200);
    }

    public function destroy($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $resource->delete();

        return response()->json(['message' => 'Resource deleted successfully'], 200);
    }
}
