<?php

namespace App\Http\Controllers\Api\Admin\ServicePurchased;

use App\Http\Controllers\Controller;
use App\Models\ServicePurchased;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $userId = $request->query('user_id'); // Filter by user ID
        $search = $request->query('search'); // Global search term

        // Query builder
        $query = ServicePurchased::with(['user'])
            ->latest();

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Apply global search
        if ($search) {
            $query->where(function ($q) use ($search) {
                // Search by service name (within service_details JSON)
                $q->where('service_details', 'like', '%' . $search . '%');

                // Search by user name (via user relationship)
                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
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
        $servicePurchased = ServicePurchased::with(['user', 'payments'])->find($id);

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
}
