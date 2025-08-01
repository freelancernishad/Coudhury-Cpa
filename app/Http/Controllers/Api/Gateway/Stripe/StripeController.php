<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\User;
use App\Models\Payment;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\CoursePurchase;
use App\Models\StripeCustomer;
use App\Models\ServicePurchased;
use App\Models\UserPackageAddon;
use App\Models\StripePaymentMethod;
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


        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'coupon_id' => 'nullable|exists:coupons,id',
            'payable_type' => 'nullable|string',
            'payable_id' => 'nullable|integer',
            'customerId' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Add authenticated user ID to the validated data
            $validatedData = $validator->validated();
            // First, check if the user is authenticated
            if (auth()->check()) {
                // Use the authenticated user's ID
                $userId = auth()->id();
            } else {
                // If not authenticated, check if customerId is provided in the request
                if ($request->customerId) {
                    // Find the user where client_id matches customerId
                    $user = User::where('client_id', $request->customerId)->first();

                    // If the user is found, use their ID; otherwise, use null or a default value
                    $userId = $user ? $user->id : null;
                } else {
                    // If no customerId is provided and the user is not authenticated, set userId to null
                    $userId = null;
                }
            }

            $validatedData['user_id'] = $userId;


            // Pass validated data to the helper function
            return createStripeCheckoutSession($validatedData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


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

            $session = $event->data->object;
            $paymentType = $session->metadata->payment_type ?? null;
            if($paymentType === 'course_purchase'){
                return $this->course_purchase($event);
            }

            // Handle different event types
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;


                    // Retrieve the PaymentIntent associated with the session
                    $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
                    // Extract payment method details
                    $paymentMethod = PaymentMethod::retrieve($paymentIntent->payment_method);
                    // Extract card or bank details
                    $paymentMethodDetails = $this->extractPaymentMethodDetails($paymentMethod);






                    // Find the payment record and update status
                    $payment = Payment::where('stripe_session', $session->id)->first();


                    if ($payment) {
                        $this->updatePaymentAndServicePurchased($payment, $paymentIntent, $paymentMethodDetails);

                        // Check if payable type is "Package" and call PackageSubscribe
                        if ($payment->payable_type === 'App\\Models\\Package') {
                            Log::info("Package: " . $payment->payable_type);
                            PackageSubscribe($payment->payable_id, $payment->user_id);
                        }
                    }


                    break;

                case 'payment_intent.succeeded':
                    // Handle successful payment
                    $paymentIntent = $event->data->object; // Contains \Stripe\PaymentIntent
                    Log::info('PaymentIntent Succeeded:', $paymentIntent->toArray());

                    // Extract payment method details
                    $paymentMethod = PaymentMethod::retrieve($paymentIntent->payment_method);
                    Log::info('PaymentMethod:', $paymentMethod->toArray());

                    // Extract card or bank details
                    $paymentMethodDetails = $this->extractPaymentMethodDetails($paymentMethod);

                    // Find the payment record and update status
                    $payment = Payment::where('transaction_id', $paymentIntent->id)->first();
                    if ($payment) {
                        $this->updatePaymentAndServicePurchased($payment, $paymentIntent, $paymentMethodDetails);

                        // Check if payable type is "Package" and call PackageSubscribe
                        if ($payment->payable_type === 'Package') {
                            PackageSubscribe($payment->payable_id, $payment->user_id);
                        }
                    }
                    break;

                case 'payment_intent.payment_failed':
                    // Handle failed payment
                    $paymentIntent = $event->data->object;
                    Log::info('PaymentIntent Failed:', $paymentIntent->toArray());

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



                    case 'invoice.payment_succeeded':
                        // Handle successful subscription payment
                        $invoice = $event->data->object;

                        // Find the UserPackage by Stripe subscription ID
                        $userPackage = UserPackage::where('stripe_subscription_id', $invoice->subscription)->first();

                        // If UserPackage does not exist, create it
                        if (!$userPackage) {
                            // Retrieve the Stripe subscription to get details
                            $stripeSubscription = \Stripe\Subscription::retrieve($invoice->subscription);

                            // Retrieve the package ID from the subscription metadata or other source
                            $packageId = $stripeSubscription->metadata->package_id ?? null;

                            // Retrieve the user ID from the Stripe customer
                            $stripeCustomer = \Stripe\Customer::retrieve($stripeSubscription->customer);
                            $user = User::where('stripe_customer_id', $stripeCustomer->id)->first();

                            if ($user && $packageId) {
                                // Create a new UserPackage
                                $userPackage = UserPackage::create([
                                    'user_id'                => $user->id,
                                    'package_id'             => $packageId,
                                    'started_at'             => now(),
                                    'ends_at'                => now()->addMonths(1), // Default to 1 month
                                    'stripe_subscription_id' => $invoice->subscription,
                                    'stripe_customer_id'     => $stripeSubscription->customer,
                                    'status'                 => 'active',
                                ]);
                            } else {
                                Log::error("User or package not found for Stripe subscription: {$invoice->subscription}");
                                return response()->json(['error' => 'User or package not found'], 400);
                            }
                        }

                        // Retrieve the charge ID from the invoice
                        $chargeId = $invoice->charge;
                        $paymentMethodDetails = null;

                        if ($chargeId) {
                            // Retrieve charge details from Stripe
                            $charge = \Stripe\Charge::retrieve($chargeId);

                            if (isset($charge->payment_method)) {
                                // Retrieve the payment method
                                $paymentMethod = \Stripe\PaymentMethod::retrieve($charge->payment_method);
                                $paymentMethodDetails = $this->extractPaymentMethodDetails($paymentMethod);
                            }
                        }

                        // Create a new payment record for the successful charge
                        $payment = Payment::create([
                            'user_id'               => $userPackage->user_id,
                            'gateway'               => 'stripe',
                            'amount'                => $invoice->amount_paid / 100, // Convert from cents to dollars
                            'currency'              => $invoice->currency,
                            'status'                => 'completed',
                            'paid_at'               => now(),
                            'payable_type'          => 'App\\Models\\Package',
                            'payable_id'            => $userPackage->package_id,
                            'user_package_id'       => $userPackage->id,
                            'business_name'         => $userPackage->business_name,
                            'is_recurring'          => true,
                            'response_data'         => json_encode($event),
                            'payment_method_details'=> json_encode($paymentMethodDetails), // Fixed this part
                        ]);

                        // Update the next billing date
                        $userPackage->update([
                            'next_billing_at' => Carbon::createFromTimestamp($invoice->lines->data[0]->period->end),
                        ]);

                        // Update UserPackageAddons with the payment ID
                        UserPackageAddon::where('user_id', $userPackage->user_id)
                            ->where('package_id', $userPackage->package_id)
                            ->update(['purchase_id' => $payment->id]);

                        break;


                    case 'invoice.payment_failed':
                        // Handle failed subscription payment
                        $invoice = $event->data->object;

                        // Find the UserPackage by Stripe subscription ID
                        $userPackage = UserPackage::where('stripe_subscription_id', $invoice->subscription)->first();
                        if ($userPackage) {
                            // Create a new payment record for the failed charge
                            Payment::create([
                                'user_id' => $userPackage->user_id,
                                'gateway' => 'stripe',
                                'amount' => $invoice->amount_due / 100, // Convert from cents to dollars
                                'currency' => $invoice->currency,
                                'status' => 'failed',

                                'payable_type' => 'App\\Models\\Package',
                                'payable_id' => $userPackage->package_id,
                                'business_name' => $userPackage->business_name,
                                'is_recurring' => true,
                                'response_data' => json_encode($event),
                            ]);

                            // Notify the user about the failed payment (you can add this logic)
                            Log::warning("Payment failed for user {$userPackage->user_id} on subscription {$invoice->subscription}");
                        }
                        break;

                    case 'customer.subscription.deleted':
                        // Handle subscription cancellation or expiration
                        $subscription = $event->data->object;

                        // Find the UserPackage by Stripe subscription ID
                        $userPackage = UserPackage::where('stripe_subscription_id', $subscription->id)->first();
                        if ($userPackage) {
                            // Mark the subscription as canceled
                            $userPackage->update([
                                'status' => 'canceled',
                                'canceled_at' => now(),
                            ]);
                        }
                        break;





                default:
                    // Unexpected event type
                    Log::info('Unhandled Event Type:', ['type' => $event->type]);
                    return response()->json(['message' => 'Event type not handled'], 400);
            }

            // Respond to Stripe that the webhook was received successfully
            return response()->json(['message' => 'Webhook handled'], 200);

        } catch (\Exception $e) {
            // If there is an error with the webhook or signature verification
            Log::error('Webhook Error:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook Error: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Extract payment method details.
     *
     * @param \Stripe\PaymentMethod $paymentMethod
     * @return array
     */
    private function extractPaymentMethodDetails($paymentMethod): array
    {
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
        return $paymentMethodDetails;
    }

    /**
     * Update payment and ServicePurchased records.
     *
     * @param Payment $payment
     * @param \Stripe\PaymentIntent $paymentIntent
     * @param array $paymentMethodDetails
     * @return void
     */
    private function updatePaymentAndServicePurchased($payment, $paymentIntent, $paymentMethodDetails): void
    {
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'response_data' => json_encode($paymentIntent),
            'payment_method_details' => json_encode($paymentMethodDetails), // Store payment method details
        ]);

        // Save Stripe customer and payment method details
        // $stripeCustomer = StripeCustomer::updateOrCreate(
        //     ['user_id' => $payment->user_id], // Match by user ID
        //     ['stripe_customer_id' => $paymentIntent->customer]
        // );

        // // Save the payment method
        // StripePaymentMethod::updateOrCreate(
        //     ['stripe_payment_method_id' => $paymentMethodDetails['id'] ?? null], // Match by payment method ID
        //     [
        //         'stripe_customer_id' => $stripeCustomer->id,
        //         'details' => $paymentMethodDetails,
        //         'is_default' => false, // Mark as non-default (you can set logic for default)
        //     ]
        // );


        // Check if payable type is "ServicePurchased"
        if ($payment->payable_type === ServicePurchased::class) {

            $servicePurchased = ServicePurchased::find($payment->payable_id);

            if ($servicePurchased) {
                // Handle based on the event type
                switch ($payment->event) {
                    case 'Service Purchase':
                        Log::info("updatePaymentAndServicePurchased =".$servicePurchased);
                        // Execute your old code for "Purchase" event
                        $this->handlePurchaseEvent($servicePurchased, $payment);
                        break;

                    case 'Due Amount':
                        // Handle "Due Amount" event
                        $this->handleDueAmountEvent($servicePurchased, $payment);
                        break;

                    default:
                        // Log unexpected event type
                        Log::info('ServicePurchased record skipped due to invalid event:', [
                            'id' => $servicePurchased->id,
                            'event' => $payment->event,
                        ]);
                        break;
                }
            }
        }
    }

    /**
     * Handle "Purchase" event.
     *
     * @param ServicePurchased $servicePurchased
     * @param Payment $payment
     * @return void
     */
    private function handlePurchaseEvent(ServicePurchased $servicePurchased, Payment $payment): void
    {
        Log::info("handlePurchaseEvent =".$servicePurchased);
        // Your old code for handling "Purchase" event
        $servicePurchased->paid_amount = $payment->amount;
        $servicePurchased->due_amount = $servicePurchased->subtotal - $servicePurchased->paid_amount;

        // Update status based on due_amount
        if ($servicePurchased->due_amount <= 0) {
            $servicePurchased->status = 'In Review';
        } else {
            $servicePurchased->status = 'partially_paid';
        }

        $servicePurchased->save();

        Log::info('ServicePurchased record updated for Purchase event:', [
            'id' => $servicePurchased->id,
            'paid_amount' => $servicePurchased->paid_amount,
            'due_amount' => $servicePurchased->due_amount,
            'status' => $servicePurchased->status,
        ]);
    }

    /**
     * Handle "Due Amount" event.
     *
     * @param ServicePurchased $servicePurchased
     * @param Payment $payment
     * @return void
     */
    private function handleDueAmountEvent(ServicePurchased $servicePurchased, Payment $payment): void
    {
        // Decrease the due_amount by the payment amount
        $servicePurchased->due_amount -= $payment->amount;

        // Ensure due_amount does not go below zero
        if ($servicePurchased->due_amount < 0) {
            $servicePurchased->due_amount = 0;
        }

        // Save the updated ServicePurchased record
        $servicePurchased->save();

        // Log the update
        Log::info('ServicePurchased record updated for Due Amount event:', [
            'id' => $servicePurchased->id,
            'due_amount' => $servicePurchased->due_amount,
        ]);
    }


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




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






  /**
     * Stripe Webhook Handler
     */
    public function course_purchase($event)
    {



        switch ($event->type) {

            case 'checkout.session.completed':
                $session = $event->data->object;

                $userId = $session->metadata->user_id ?? null;
                $courseId = $session->metadata->course_id ?? null;

                if ($userId && $courseId) {
                    $purchase = CoursePurchase::where('user_id', $userId)
                        ->where('course_id', $courseId)
                        ->first();

                    if ($purchase && $purchase->status !== 'paid') {
                        $course = $purchase->course;

                        $purchase->status = 'paid';
                        $purchase->stripe_payment_id = $session->payment_intent ?? null;
                        $purchase->stripe_subscription_id = $session->subscription ?? null;
                        $purchase->starts_at = now();
                        $purchase->ends_at = $course->recurring_month > 0
                            ? now()->addMonths($course->recurring_month)
                            : null;
                        $purchase->save();

                        // Avoid duplicate payments
                        if (!$purchase->payments()->where('stripe_payment_id', $session->payment_intent)->exists()) {
                            $purchase->payments()->create([
                                'stripe_payment_id' => $session->payment_intent ?? null,
                                'amount' => $purchase->amount,
                                'status' => 'paid',
                                'paid_at' => now(),
                            ]);
                        }
                    }
                }
                break;

            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $subscriptionId = $invoice->subscription ?? null;

                if ($subscriptionId) {
                    $purchase = CoursePurchase::where('stripe_subscription_id', $subscriptionId)->first();

                    if ($purchase) {
                        $purchase->ends_at = now()->addMonths($purchase->course->recurring_month);
                        $purchase->save();

                        $purchase->payments()->create([
                            'stripe_payment_id' => $invoice->payment_intent,
                            'amount' => $invoice->amount_paid / 100,
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }
                }
                break;

            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $subscriptionId = $invoice->subscription ?? null;

                if ($subscriptionId) {
                    $purchase = CoursePurchase::where('stripe_subscription_id', $subscriptionId)->first();

                    if ($purchase) {
                        $purchase->status = 'failed';
                        $purchase->save();

                        Log::warning("Payment failed for subscription ID: {$subscriptionId}");
                    }
                }
                break;

            case 'customer.subscription.deleted':
                $subscription = $event->data->object;

                $purchase = CoursePurchase::where('stripe_subscription_id', $subscription->id)->first();
                if ($purchase) {
                    $purchase->status = 'canceled';
                    $purchase->ends_at = now();
                    $purchase->save();

                    Log::info("Subscription canceled for user ID: {$purchase->user_id}");
                }
                break;

            default:
                Log::info("Unhandled Stripe event type: {$event->type}");
        }

        return response('Webhook handled', 200);
    }






}

