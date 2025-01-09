<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Payment;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\ServicePurchased;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class StripeController extends Controller
{
    // Set up Stripe API key
    public function __construct()
    {
        Stripe::setApiKey(config('STRIPE_SECRET'));
    }

    // Create a payment session for Stripe Checkout
    public function createCheckoutSession(Request $request)
    {
        // Get the authenticated user's ID
        $userId = auth()->id() ?? null;

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'coupon_id' => 'nullable|exists:coupons,id',
            'payable_type' => 'nullable|string',
            'payable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Add authenticated user ID to the validated data
            $validatedData = $validator->validated();
            $validatedData['user_id'] = $userId;

            // Pass validated data to the helper function
            return createStripeCheckoutSession($validatedData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function handleWebhook(Request $request)
    {
        // Secret key for Stripe Webhook signature verification
        $endpoint_secret = config('STRIPE_WEBHOOK_SECRET');

        // Get raw body and signature header
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            // Handle different event types
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object; // Contains \Stripe\Checkout\Session

                    // Retrieve the PaymentIntent associated with the session
                    $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

                    // Extract payment method details
                    $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);

                    // Extract card or bank details
                    $paymentMethodDetails = [];
                    if ($paymentMethod->type === 'card') {
                        $paymentMethodDetails = [
                            'type' => 'card',
                            'brand' => $paymentMethod->card->brand,
                            'last4' => $paymentMethod->card->last4,
                            'exp_month' => $paymentMethod->card->exp_month,
                            'exp_year' => $paymentMethod->card->exp_year,
                        ];
                    } elseif ($paymentMethod->type === 'bank_account') {
                        $paymentMethodDetails = [
                            'type' => 'bank_account',
                            'bank_name' => $paymentMethod->bank_account->bank_name,
                            'last4' => $paymentMethod->bank_account->last4,
                            'routing_number' => $paymentMethod->bank_account->routing_number,
                        ];
                    }


                    // Find the payment record and update status
                    $payment = Payment::where('stripe_session', $session->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                            'response_data' => json_encode($session),
                            'payment_method_details' => json_encode($paymentMethodDetails), // Store payment method details
                        ]);

                        // Check if payable type is "ServicePurchased" and update the ServicePurchased record
                        if ($payment->payable_type === ServicePurchased::class) {
                            $servicePurchased = ServicePurchased::find($payment->payable_id);
                            if ($servicePurchased && $servicePurchased->status === 'pending') {



                                // Update the paid_amount and due_amount
                                $servicePurchased->paid_amount += $payment->amount;
                                $servicePurchased->due_amount = $servicePurchased->subtotal - $servicePurchased->paid_amount;

                                // Update status based on due_amount
                                if ($servicePurchased->due_amount <= 0) {
                                    $servicePurchased->status = 'In review';
                                } else {
                                    $servicePurchased->status = 'partially_paid';
                                }
                                $servicePurchased->save();



                            }
                        }

                        // Check if payable type is "Package" and call PackageSubscribe
                        if ($payment->payable_type === 'Package') {
                            PackageSubscribe($payment->payable_id, $payment->user_id);
                        }
                    }
                    break;

                case 'payment_intent.succeeded':
                    // Handle successful payment
                    $paymentIntent = $event->data->object; // Contains \Stripe\PaymentIntent

                    // Extract payment method details
                    $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);

                    // Extract card or bank details
                    $paymentMethodDetails = [];
                    if ($paymentMethod->type === 'card') {
                        $paymentMethodDetails = [
                            'type' => 'card',
                            'brand' => $paymentMethod->card->brand,
                            'last4' => $paymentMethod->card->last4,
                            'exp_month' => $paymentMethod->card->exp_month,
                            'exp_year' => $paymentMethod->card->exp_year,
                        ];
                    } elseif ($paymentMethod->type === 'bank_account') {
                        $paymentMethodDetails = [
                            'type' => 'bank_account',
                            'bank_name' => $paymentMethod->bank_account->bank_name,
                            'last4' => $paymentMethod->bank_account->last4,
                            'routing_number' => $paymentMethod->bank_account->routing_number,
                        ];
                    }

                    $payment = Payment::where('transaction_id', $paymentIntent->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                            'payment_method_details' => json_encode($paymentMethodDetails), // Store payment method details
                        ]);

                        // Check if payable type is "ServicePurchased" and update the ServicePurchased record
                        if ($payment->payable_type === ServicePurchased::class) {
                            $servicePurchased = ServicePurchased::find($payment->payable_id);
                            if ($servicePurchased) {
                                // Update the paid_amount and due_amount
                                $servicePurchased->paid_amount += $payment->amount;
                                $servicePurchased->due_amount = $servicePurchased->subtotal - $servicePurchased->paid_amount;

                                // Update status based on due_amount
                                if ($servicePurchased->due_amount <= 0) {
                                    $servicePurchased->status = 'In review';
                                } else {
                                    $servicePurchased->status = 'partially_paid';
                                }

                                $servicePurchased->save();
                            }
                        }

                        // Check if payable type is "Package" and call PackageSubscribe
                        if ($payment->payable_type === 'Package') {
                            PackageSubscribe($payment->payable_id);
                        }
                    }
                    break;

                case 'payment_intent.payment_failed':
                    // Handle failed payment
                    $paymentIntent = $event->data->object;
                    $payment = Payment::where('transaction_id', $paymentIntent->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                        ]);

                        // Check if payable type is "ServicePurchased" and update the ServicePurchased record
                        if ($payment->payable_type === ServicePurchased::class) {
                            $servicePurchased = ServicePurchased::find($payment->payable_id);
                            if ($servicePurchased) {
                                // Update status to failed
                                $servicePurchased->status = 'failed';
                                $servicePurchased->save();
                            }
                        }
                    }
                    break;

                // Handle other events as needed

                default:
                    // Unexpected event type
                    return response()->json(['message' => 'Event type not handled'], 400);
            }

            // Respond to Stripe that the webhook was received successfully
            return response()->json(['message' => 'Webhook handled'], 200);

        } catch (\Exception $e) {
            // If there is an error with the webhook or signature verification
            return response()->json(['error' => 'Webhook Error: ' . $e->getMessage()], 400);
        }
    }


    // Create a PaymentIntent (for processing payment)
    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Create PaymentIntent with Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $validatedData['amount'] * 100, // Amount in cents
                'currency' => $validatedData['currency'],
                'payment_method_types' => ['card'],
            ]);

            // Respond with the client secret for the frontend to use
            return response()->json(['client_secret' => $paymentIntent->client_secret]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating PaymentIntent: ' . $e->getMessage()], 500);
        }
    }

    // Confirm the payment with a PaymentIntent
    public function confirmPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Confirm the payment with the provided payment method ID
            $paymentIntent = PaymentIntent::retrieve($validatedData['payment_intent_id']);
            $paymentIntent->confirm([
                'payment_method' => $validatedData['payment_method_id'],
            ]);

            // Respond with the payment status
            return response()->json(['status' => $paymentIntent->status]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error confirming PaymentIntent: ' . $e->getMessage()], 500);
        }
    }
}

