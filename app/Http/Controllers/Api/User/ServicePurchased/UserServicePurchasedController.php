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
            'files' => 'nullable|array',
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
        // $serviceDetails = json_decode($request->input('service_details'), true);


        $serviceDetails = $request->input('service_details');

        // Option 1: If service_details is a JSON string, decode it
        if (is_string($serviceDetails)) {
            $serviceDetails = json_decode($serviceDetails, true);

            // Check if JSON decoding was successful
            if (json_last_error() === JSON_ERROR_NONE) {
                 $serviceDetails = $serviceDetails;
            } else {

                $serviceDetails = []; // Return an empty array if decoding fails
            }
        }

        // Option 2: If service_details is already an array, use it directly
        if (is_array($serviceDetails)) {
             $serviceDetails =$serviceDetails;
        }








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

                // Use the ServicePurchasedFile model's upload method
                ServicePurchasedFile::ServicePurchasedFileUpload($file, $servicePurchased->id,$userId);
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
            'event' => 'Purchase', //Purchase/Due Amount
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
                'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'], // Only card for simplicity
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'service_purchased_id' => $servicePurchased->id,
                ],
            ]);

            $payment->update(['stripe_session' => $session->id]);

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








// public function createStripeCheckoutSession(Request $request): JsonResponse
// {
//     // Validate the request
//     $validator = Validator::make($request->all(), [
//         'coupon_id' => 'nullable|exists:coupons,id',
//         'service_details' => 'required', // Ensure service_details is a valid JSON string
//         'files' => 'required|array',
//         'files.*' => 'file|mimes:jpeg,png,pdf,doc,docx',
//         'success_url' => 'nullable|string',
//         'cancel_url' => 'nullable|string',
//     ]);

//     if ($validator->fails()) {
//         return response()->json($validator->errors(), 422);
//     }

//     // Get the authenticated user's ID
//     $userId = Auth::id();
//     if (!$userId) {
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }

//     // Decode the service_details JSON string into an array
//     $serviceDetails = json_decode($request->input('service_details'), true);

//     if (json_last_error() !== JSON_ERROR_NONE) {
//         return response()->json(['error' => 'Invalid service_details JSON'], 400);
//     }

//     // Extract amount from service_details
//     $amount = $serviceDetails['total_price'] ?? 0; // Get the total_price from service_details
//     $notes = $serviceDetails['notes'] ?? '';

//     if ($amount <= 0) {
//         return response()->json(['error' => 'Invalid total price in service details'], 400);
//     }

//     // Fixed currency
//     $currency = 'USD';

//     // Coupon ID (if provided)
//     $couponId = $request->input('coupon_id');

//     // Success and cancel URLs
//     $baseSuccessUrl = $request->input('success_url', 'http://localhost:8000/stripe/payment/success');
//     $baseCancelUrl = $request->input('cancel_url', 'http://localhost:8000/stripe/payment/cancel');

//     $discount = 0;
//     $finalAmount = $amount; // Start with the base amount

//     // Handle coupon discount
//     if ($couponId) {
//         $coupon = Coupon::find($couponId);
//         if ($coupon && $coupon->isValid()) {
//             $discount = $coupon->getDiscountAmount($amount);
//             $finalAmount -= $discount; // Subtract discount from the final amount
//         } else {
//             return response()->json(['error' => 'Invalid or expired coupon'], 400);
//         }
//     }

//     if ($finalAmount <= 0) {
//         return response()->json(['error' => 'Payment amount must be greater than zero'], 400);
//     }

//     // Create the ServicePurchased record
//     $servicePurchased = ServicePurchased::create([
//         'user_id' => $userId,
//         'date' => now(),
//         'subtotal' => $amount,
//         'paid_amount' => 0, // Initially 0, updated after payment success
//         'due_amount' => $finalAmount,
//         'status' => 'pending',
//         'client_note' => $notes,
//         'admin_note' => null,
//         'discount_amount' => $discount,
//         'service_details' => $serviceDetails, // Store the decoded service_details
//     ]);

//     // Handle file uploads from the request
//     if ($request->hasFile('files')) {
//         $files = $request->file('files');
//         foreach ($files as $file) {
//             // Use the ServicePurchasedFile model's upload method
//             ServicePurchasedFile::ServicePurchasedFileUpload($file, $servicePurchased->id, $userId);
//         }
//     }

//     // Create the payment record
//     $payment = Payment::create([
//         'user_id' => $userId,
//         'gateway' => 'stripe',
//         'amount' => $finalAmount,
//         'currency' => $currency,
//         'status' => 'pending',
//         'transaction_id' => uniqid(),
//         'payable_type' => ServicePurchased::class,
//         'payable_id' => $servicePurchased->id,
//         'coupon_id' => $couponId,
//         'event' => 'Purchase', // Purchase/Due Amount
//     ]);

//     try {
//         Stripe::setApiKey(config('STRIPE_SECRET'));

//         // Check if the user already has a Stripe customer record
//         $stripeCustomer = StripeCustomer::where('user_id', $userId)->first();

//         if ($stripeCustomer) {
//             // Retrieve the default payment method for the customer
//             $defaultPaymentMethod = StripePaymentMethod::where('stripe_customer_id', $stripeCustomer->id)
//                 ->where('is_default', true)
//                 ->first();

//             if ($defaultPaymentMethod) {
//                 // Use the existing payment method to create a PaymentIntent
//                 $paymentIntent = \Stripe\PaymentIntent::create([
//                     'amount' => $finalAmount * 100, // Amount in cents
//                     'currency' => $currency,
//                     'customer' => $stripeCustomer->stripe_customer_id,
//                     'payment_method' => $defaultPaymentMethod->stripe_payment_method_id,
//                     'off_session' => true, // Indicates that the customer is not present
//                     'confirm' => true, // Automatically confirm the PaymentIntent
//                     'metadata' => [
//                         'payment_id' => $payment->id,
//                         'service_purchased_id' => $servicePurchased->id,
//                     ],
//                 ]);

//                 // Update the payment record with the PaymentIntent ID
//                 $payment->update([
//                     'stripe_payment_intent_id' => $paymentIntent->id,
//                     'status' => 'completed', // Mark as completed since the payment is confirmed
//                     'paid_at' => now(),
//                 ]);

//                 // Update the ServicePurchased record
//                 $servicePurchased->update([
//                     'paid_amount' => $finalAmount,
//                     'due_amount' => 0,
//                     'status' => 'In Review',
//                 ]);

//                 return response()->json([
//                     'payment_intent_id' => $paymentIntent->id,
//                     'payment_id' => $payment->id,
//                     'service_purchased_id' => $servicePurchased->id,
//                     'message' => 'Payment completed using existing payment method.',
//                 ]);
//             }
//         }

//         // If no existing customer or payment method, create a new CheckoutSession
//         $successUrl = "{$baseSuccessUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";
//         $cancelUrl = "{$baseCancelUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";

//         // Product details
//         $productName = 'Service Purchase';
//         $lineItems = [
//             [
//                 'price_data' => [
//                     'currency' => $currency,
//                     'product_data' => [
//                         'name' => $productName,
//                     ],
//                     'unit_amount' => $finalAmount * 100, // Amount in cents
//                 ],
//                 'quantity' => 1,
//             ],
//         ];

//         // Create the Stripe Checkout session
//         $session = Session::create([
//             'payment_method_types' => ['card'], // Only card for simplicity
//             'line_items' => $lineItems,
//             'mode' => 'payment',
//             'success_url' => $successUrl,
//             'cancel_url' => $cancelUrl,
//             'metadata' => [
//                 'payment_id' => $payment->id,
//                 'service_purchased_id' => $servicePurchased->id,
//             ],
//         ]);

//         $payment->update(['stripe_session' => $session->id]);

//         return response()->json([
//             'session_id' => $session->id,
//             'payment_id' => $payment->id,
//             'service_purchased_id' => $servicePurchased->id,
//             'url' => $session->url,
//         ]);
//     } catch (\Exception $e) {
//         // Handle Stripe errors
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }
