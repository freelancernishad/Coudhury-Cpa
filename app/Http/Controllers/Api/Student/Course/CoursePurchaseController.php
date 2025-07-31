<?php

namespace App\Http\Controllers\Api\Student\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\CoursePurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

class CoursePurchaseController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Show course details
     */
    public function showCourse($id)
    {
        $course = Course::findOrFail($id);

        return response()->json([
            'course' => $course,
        ]);
    }

    /**
     * Initiate Stripe Checkout session for course purchase
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);

        try {
            // Total price after VAT/tax
            $total = $course->recurring_month > 0 ? $course->recurring_price : $course->price;
            $totalInCents = intval(round($total * 100));

            // Stripe session setup
            $session = CheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $course->title,
                            'description' => $course->short_description,
                        ],
                        'unit_amount' => $totalInCents,
                        'recurring' => $course->recurring_month > 0 ? [
                            'interval' => 'month',
                            'interval_count' => 1,
                        ] : null,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => $course->recurring_month > 0 ? 'subscription' : 'payment',
                'customer_email' => $user->email,
                'success_url' => $request->success_url . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
            ]);

            // Create or update purchase record as pending
            CoursePurchase::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'status' => 'pending',
                    'amount' => $total,
                    'currency' => 'usd',
                    'stripe_payment_id' => null,
                    'stripe_subscription_id' => null,
                ]
            );

            return response()->json([
                'checkout_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Stripe checkout session creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stripe Webhook Handler
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $userId = $session->metadata->user_id ?? null;
                $courseId = $session->metadata->course_id ?? null;

                if ($userId && $courseId) {
                    $purchase = CoursePurchase::where('user_id', $userId)
                        ->where('course_id', $courseId)
                        ->first();

                    if ($purchase) {
                        $course = $purchase->course;

                        $purchase->status = 'paid';
                        $purchase->stripe_payment_id = $session->payment_intent ?? null;
                        $purchase->stripe_subscription_id = $session->subscription ?? null;
                        $purchase->starts_at = now();
                        $purchase->ends_at = $course->recurring_month > 0
                            ? now()->addMonths($course->recurring_month)
                            : null;
                        $purchase->save();

                        $purchase->payments()->create([
                            'stripe_payment_id' => $session->payment_intent ?? null,
                            'amount' => $purchase->amount,
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }
                }
                break;

            case 'invoice.payment_failed':
                // Optional: handle failed payment
                break;

            case 'customer.subscription.deleted':
                // Optional: mark subscription as canceled
                break;
        }

        return response('Webhook handled', 200);
    }
}
