<?php

namespace App\Http\Controllers\Api\User\ServicePurchased;

use App\Http\Controllers\Controller;
use App\Models\ServicePurchased;
use App\Models\User;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\StripeCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Product;
use Stripe\Price;

class ServicePurchasedDuePaymentController extends Controller
{
    /**
     * Get ServicePurchased list with due amounts for a specific user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServicePurchasedList(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'customerId' => 'required|exists:users,client_id', // Ensure customerId exists in users table
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Find the user by client_id (customerId)
            $user = User::where('client_id', $request->customerId)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Retrieve all ServicePurchased records for the user with due amount and without pending status
            $servicePurchasedList = ServicePurchased::where('user_id', $user->id)
                ->where('status', '!=', 'pending') // Exclude pending status
                ->where('due_amount', '>', 0) // Only include records with due amount
                ->with('files') // Load related files
                ->latest()
                ->get();

            return response()->json([
                'service_purchased_list' => $servicePurchasedList,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a payment for a specific ServicePurchased record using Stripe.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'customerId' => 'required|exists:users,client_id', // Ensure customerId exists in users table
            'service_purchased_id' => 'required|exists:service_purchased,id', // Ensure serviceId exists in service_purchased table
            'amount' => 'required|numeric|min:1', // Payment amount must be at least 1
            'currency' => 'required|string|size:3', // Currency code (e.g., USD)
            'success_url' => 'required|url', // Success URL for Stripe
            'cancel_url' => 'required|url', // Cancel URL for Stripe
            'coupon_id' => 'nullable|exists:coupons,id', // Optional coupon ID
            'payment_method' => 'nullable|string', // Optional payment method (default: stripe)
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Find the user by client_id (customerId)
            $user = User::where('client_id', $request->customerId)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Find the ServicePurchased record by serviceId
            $servicePurchased = ServicePurchased::find($request->service_purchased_id);

            if (!$servicePurchased) {
                return response()->json(['error' => 'ServicePurchased record not found'], 404);
            }

            // Ensure the ServicePurchased record belongs to the user
            if ($servicePurchased->user_id !== $user->id) {
                return response()->json(['error' => 'ServicePurchased record does not belong to the user'], 403);
            }

            // Ensure the amount is not greater than the due amount
            if ($request->amount > $servicePurchased->due_amount) {
                return response()->json(['error' => 'Amount cannot be greater than the due amount'], 400);
            }

            // Prepare data for Stripe checkout session
            $validatedData = [
                'amount' => $request->amount,
                'currency' => $request->currency,
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'coupon_id' => $request->coupon_id,
                'payable_type' => ServicePurchased::class,
                'payable_id' => $servicePurchased->id,
                'user_id' => $user->id,
                'event' => 'Due Amount',
                'payment_method' => $request->payment_method ?? 'stripe',
            ];

            // Create Stripe checkout session
            return createStripeCheckoutSession($validatedData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



}
