<?php

namespace App\Http\Controllers\Api\StudentV2\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\CoursePurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

class V2CoursePurchaseController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Get all courses
     */
    public function getAllCourses()
    {
        $courses = Course::all();

        return response()->json([
            'courses' => $courses,
        ]);
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

        // Use student guard
        $student = Auth::guard('student')->user();
        $course = Course::findOrFail($request->course_id);

        try {
            $total = $course->recurring_month > 0 ? $course->recurring_price : $course->price;
            $totalInCents = intval(round($total * 100));

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
                'customer_email' => $student->email,
                'success_url' => $request->success_url . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'user_id' => $student->id, // changed from user_id
                    'course_id' => $course->id,
                    'payment_type' => 'course_purchase',
                    'recurring_limit' => $course->recurring_month,
                ],
            ]);

            CoursePurchase::updateOrCreate(
                [
                    'user_id' => $student->id, // changed from user_id
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
     * Show all course purchases for authenticated student.
     */
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();

        $purchases = CoursePurchase::with('course')
            ->where('user_id', $student->id) // changed
            ->where('status', 'paid')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($purchases);
    }

    /**
     * Show a single course purchase with payments.
     */
    public function show($id)
    {
        $student = Auth::guard('student')->user();

        $purchase = CoursePurchase::with(['course', 'payments'])
            ->where('id', $id)
            ->where('user_id', $student->id) // changed
            ->where('status', 'paid')
            ->firstOrFail();

        return response()->json($purchase);
    }

    /**
     * Show all payments for a specific course purchase.
     */
    public function payments($id)
    {
        $student = Auth::guard('student')->user();

        $purchase = CoursePurchase::with('payments')
            ->where('id', $id)
            ->where('user_id', $student->id) // changed
            ->where('status', 'paid')
            ->firstOrFail();

        return response()->json([
            'purchase_id' => $purchase->id,
            'course' => $purchase->course->title ?? '',
            'payments' => $purchase->payments
        ]);
    }

    public function studentPayments()
    {
        $student = Auth::guard('student')->user();

        $purchases = CoursePurchase::with('payments', 'course')
            ->where('status', 'paid')
            ->where('user_id', $student->id) // changed
            ->get();

        return response()->json([
            'purchases' => $purchases->map(function ($purchase) {
                return [
                    'id' => $purchase->id ?? '',
                    'course_title' => $purchase->course->title ?? '',
                    'course' => $purchase->course ?? [],
                    'amount' => $purchase->amount,
                    'status' => $purchase->status,
                    'payments' => $purchase->payments,
                ];
            }),
        ]);
    }
}
