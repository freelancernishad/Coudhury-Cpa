<?php

namespace App\Http\Controllers\Api\Admin\StudentV2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CoursePurchasePayment;
use App\Models\Student; // User এর জায়গায় Student use করা হয়েছে

class V2AdminStudentController extends Controller
{
    /**
     * Show all students (student model handle করবে, তাই index optional)
     */

    
    public function index(Request $request)
    {
        $students = Student::withCount('coursePurchases')
            ->with(['coursePurchases' => function ($query) {
                $query->orderByDesc('created_at');
            }]);

        if ($request->has('search')) {
            $search = $request->input('search');
            $students->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('client_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // ✅ Filter by course_id if provided
        if ($request->has('course_id')) {
            $courseId = $request->input('course_id');

            $students->whereHas('coursePurchases', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }


        $students = $students->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'students' => $students
        ]);
    }

    /**
     * Show single student by client_id
     */
    public function singleStudent($id)
    {
        $student = Student::withCount('coursePurchases')
            ->with([
                'coursePurchases.course',
                'coursePurchases.course_payments',
                'courseContents',
            ])
            ->where('client_id', $id)
            ->first();

        return response()->json([
            'student' => $student,
        ]);
    }

    /**
     * Payments with course details for authenticated student
     */

   public function getPaymentsWithCourseDetails(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $search = $request->input('search'); // Optional filter

    $paymentsQuery = CoursePurchasePayment::with('coursePurchase.course', 'coursePurchase.user')->where('status', 'paid');

    // Filter by user email if provided
    if ($search) {
        $paymentsQuery->whereHas('coursePurchase.user', function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%");
        });
    }

    $payments = $paymentsQuery->paginate($perPage);

    // Transform each item in the collection
    $payments->getCollection()->transform(function ($payment) {
        return [
            'payment_id' => $payment->id,
            'email' => $payment->coursePurchase->user->email ?? null,
            'stripe_payment_id' => $payment->stripe_payment_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
            'course_purchase_id' => $payment->course_purchase_id,
            'user_email' => $payment->coursePurchase->user->email ?? null,
            'course' => [
                'id' => $payment->coursePurchase->course->id ?? null,
                'title' => $payment->coursePurchase->course->title ?? 'N/A',
                'price' => $payment->coursePurchase->course->price ?? 0,
                'recurring_price' => $payment->coursePurchase->course->recurring_price ?? 0,
                'recurring_month' => $payment->coursePurchase->course->recurring_month ?? 0,
            ],
        ];
    });

    return response()->json($payments);
}

    /**
     * Show course purchases for authenticated student
     */

public function purchases($id)
{
    // SQL log
    // DB::listen(function ($query) {
    //     logger('SQL: '.$query->sql, $query->bindings);
    // });

    $student = Student::with(['coursePurchases.course'])
        ->findOrFail($id);

    return response()->json([
        'student' => $student->only(['id', 'name', 'email', 'phone']),
        'purchases' => $student->coursePurchases,
    ]);
}
    /**
     * Show payments for a specific course purchase
     */
    public function purchasePayments($studentId, $purchaseId)
    {
        $purchase = \App\Models\CoursePurchase::with('payments', 'course')
            ->where('user_id', $studentId)
            ->findOrFail($purchaseId);

        return response()->json([
            'course' => $purchase->course->title ?? '',
            'amount' => $purchase->amount,
            'status' => $purchase->status,
            'payments' => $purchase->payments,
        ]);
    }

    /**
     * Show all purchases and payments for authenticated student
     */
    public function studentPayments($studentId)
    {
        $purchases = \App\Models\CoursePurchase::with('payments', 'course')
              ->where('status', 'paid')
            ->where('user_id', $studentId)
            ->get();

        return response()->json([
            'student_id' => $studentId,
            'purchases' => $purchases->map(function ($purchase) {
                return [
                    'id'   => $purchase->id ?? '',
                    'course_title'   => $purchase->course->title ?? '',
                    'course'   => $purchase->course ?? [],
                    'amount'   => $purchase->amount,
                    'status'   => $purchase->status,
                    'payments' => $purchase->payments,
                ];
            }),
        ]);
    }
}

