<?php

namespace App\Http\Controllers\Api\User\ServicePurchased;

use Stripe\Stripe;
use App\Models\Coupon;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\ServicePurchased;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ServicePurchasedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'coupon_id' => 'nullable|exists:coupons,id',
            'service_details' => 'required', // Ensure service_details is a valid JSON string
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpeg,png,pdf,doc,docx',
            'success_url' => 'nullable|string',
            'cancel_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get the authenticated user's ID
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Decode the service_details JSON string into an array
        $serviceDetails = json_decode($request->input('service_details'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid service_details JSON'], 400);
        }

        // Extract amount from service_details
        $amount = $serviceDetails['total_price'] ?? 0; // Get the total_price from service_details
        $notes = $serviceDetails['notes'] ?? '';

        if ($amount <= 0) {
            return response()->json(['error' => 'Invalid total price in service details'], 400);
        }

        // Fixed currency
        $currency = 'USD';

        // Coupon ID (if provided)
        $couponId = $request->input('coupon_id');

        // Success and cancel URLs
        $baseSuccessUrl = $request->input('success_url', 'http://localhost:8000/stripe/payment/success');
        $baseCancelUrl = $request->input('cancel_url', 'http://localhost:8000/stripe/payment/cancel');

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
            'client_note' => $notes,
            'admin_note' => null,
            'discount_amount' => $discount,
            'service_details' => $serviceDetails, // Store the decoded service_details
        ]);

        // Handle file uploads from the request
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            foreach ($files as $file) {
                // Log the file details (optional)
                Log::info('Uploading file:', [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                // Use the ServicePurchasedFile model's upload method
                ServicePurchasedFile::ServicePurchasedFileUpload($file, $servicePurchased->id);
            }
        }

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
                'payment_method_types' => ['card'], // Only card for simplicity
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
