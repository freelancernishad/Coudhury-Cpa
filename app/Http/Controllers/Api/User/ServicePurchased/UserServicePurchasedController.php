<?php

namespace App\Http\Controllers\Api\User\ServicePurchased;

use App\Http\Controllers\Controller;
use App\Models\ServicePurchased;
use App\Models\Payment;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class UserServicePurchasedController extends Controller
{
    /**
     * Create a Stripe Checkout Session for ServicePurchased.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createStripeCheckoutSession(Request $request): JsonResponse
    {
        // Validate the request
        $data = $request->validate([
            'coupon_id' => 'nullable|exists:coupons,id',
            'service_details' => 'required',
            'success_url' => 'nullable|string',
            'cancel_url' => 'nullable|string',
        ]);

        // Get the authenticated user's ID
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Extract amount from service_details
        $serviceDetails = $data['service_details'];
        $amount = $serviceDetails['total_price'] ?? 0; // Get the total_price from service_details

        if ($amount <= 0) {
            return response()->json(['error' => 'Invalid total price in service details'], 400);
        }

        // Fixed currency
        $currency = 'USD';

        // Coupon ID (if provided)
        $couponId = $data['coupon_id'] ?? null;

        // Success and cancel URLs
        $baseSuccessUrl = $data['success_url'] ?? 'http://localhost:8000/stripe/payment/success';
        $baseCancelUrl = $data['cancel_url'] ?? 'http://localhost:8000/stripe/payment/cancel';

        $discount = 0;
        $finalAmount = $amount; // Start with the base amount

        // Handle coupon discount
        if ($couponId) {
            $coupon = Coupon::find($couponId);
            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->getDiscountAmount($amount);
                $finalAmount -= $discount; // Subtract discount from the final amount
            } else {
                return response()->json(['error' => 'Invalid or expired coupon'], 400);
            }
        }

        if ($finalAmount <= 0) {
            return response()->json(['error' => 'Payment amount must be greater than zero'], 400);
        }

        // Create the ServicePurchased record
        $servicePurchased = ServicePurchased::create([
            'user_id' => $userId,
            'date' => now(),
            'subtotal' => $amount,
            'paid_amount' => 0, // Initially 0, updated after payment success
            'due_amount' => $finalAmount,
            'status' => 'pending',
            'client_note' => 'Payment initiated via Stripe',
            'admin_note' => null,
            'discount_amount' => $discount,
            'service_details' => $serviceDetails,
        ]);

        // Create the payment record
        $payment = Payment::create([
            'user_id' => $userId,
            'gateway' => 'stripe',
            'amount' => $finalAmount,
            'currency' => $currency,
            'status' => 'pending',
            'transaction_id' => uniqid(),
            'payable_type' => ServicePurchased::class,
            'payable_id' => $servicePurchased->id,
            'coupon_id' => $couponId,
        ]);

        try {
            Stripe::setApiKey(config('STRIPE_SECRET'));

            // Success and Cancel URLs for Stripe Checkout session
            $successUrl = "{$baseSuccessUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";
            $cancelUrl = "{$baseCancelUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";

            // Product details
            $productName = 'Service Purchase';
            $lineItems = [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $productName,
                        ],
                        'unit_amount' => $finalAmount * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ],
            ];

            // Create the Stripe Checkout session
            $session = Session::create([
                'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'service_purchased_id' => $servicePurchased->id,
                ],
            ]);

            return response()->json([
                'session_id' => $session->id,
                'payment_id' => $payment->id,
                'service_purchased_id' => $servicePurchased->id,
                'url' => $session->url,
            ]);
        } catch (\Exception $e) {
            // Handle Stripe errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
