<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;

class PublicResourceController extends Controller
{
    public function index()
    {
        $resources = Resource::where('visibility', 'Active')
            ->orderBy('created_at', 'desc')
            ->select('id', 'title', 'slug', 'visibility') // don't load full blocks for list
            ->get();

        return response()->json($resources);
    }

    public function showBySlug($slug)
    {
        $resource = Resource::where('slug', $slug)
            ->where('visibility', 'Active')
            ->first();

        if (!$resource) {
            return response()->json([
                'message' => 'Resource not found'
            ], 404);
        }

        return response()->json($resource);
    }
}
