<?php

namespace App\Http\Controllers\Api\Admin\Transitions;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminPaymentController extends Controller
{
    /**
     * Get all types of transaction history.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTransactionHistory(Request $request)
    {
        // Initialize query
        $query = Payment::query();

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'completed');
        }

        // Filter by gateway if provided
        if ($request->has('gateway')) {
            $query->where('gateway', $request->input('gateway'));
        }

        // Filter by specific payable type and ID
        if ($request->has('payable_type') && $request->has('payable_id')) {
            $query->where('payable_type', $request->input('payable_type'))
                ->where('payable_id', $request->input('payable_id'));
        }

        // Filter by coupon usage
        if ($request->has('coupon_id')) {
            $query->where('coupon_id', $request->input('coupon_id'));
        }

        // Handle user_id based on the guard
        if (Auth::guard('user')->check()) {
            // If the guard is 'user', get the user_id from the authenticated user
            $query->where('user_id', Auth::guard('user')->id());
        } elseif ($request->has('user_id')) {
            // For other guards, get the user_id from the request
            $query->where('user_id', $request->input('user_id'));
        }

        // Apply date range filter based on `created_at`
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        // Apply date range filter based on `paid_at`
        if ($request->has('paid_start_date') && $request->has('paid_end_date')) {
            $query->whereBetween('paid_at', [
                $request->input('paid_start_date'),
                $request->input('paid_end_date'),
            ]);
        }

        // Select specific fields and include user data
        $query->with(['user' => function ($query) {
            $query->select([
                'id',
                'client_id',
                'profile_picture',
                'name',
                'email',
            ]);
        }]);

        // Include ServicePurchased to access service_details and due_amount
        $query->with(['payable']);

        // Fetch results with pagination and order by `paid_at` descending
        $transactions = $query->orderBy('paid_at', 'desc')->paginate($request->input('per_page', 15));

        // Transform the response to include user data, due_amount, and service_details
        $transactions->getCollection()->transform(function ($payment) {
            $servicePurchased = $payment->payable;

            return [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'client_id' => $payment->user->client_id,
                'name' => $payment->user->name,
                'email' => $payment->user->email,
                'profile_picture' => $payment->user->profile_picture,
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at,
                'event' => $payment->event,
                'status' => $payment->status,
                'due_amount' => $servicePurchased ? $servicePurchased->due_amount : 0, // Add due_amount at root level
                'service_details' => $servicePurchased ? $servicePurchased->formatted_service_details : 0, // Add service_details at root level
            ];
        });

        return response()->json($transactions);
    }
}
