<?php

namespace App\Http\Controllers\Api\Admin\Student;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CoursePurchasePayment;

class AdminStudentController extends Controller
{


    /**
     * Show all users with role 'student'
     */
    public function index(Request $request)
    {
        $students = User::withCount('coursePurchases')
            ->with(['coursePurchases' => function ($query) {
                $query->orderByDesc('created_at');
            }])
            ->where('role', 'student')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                    ->where('client_id', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'students' => $students,
        ]);
    }


    /**
     * Show all users with role 'student'
     */
    public function singleStudent($id)
    {
        // DB::listen(function ($query) {
        //     logger($query->sql, $query->bindings);
        // });

        $student = User::withCount('coursePurchases')
            ->with([
                'coursePurchases.course',
                'coursePurchases.course_payments',
                'courseContents', // <-- add this line
            ])
            ->where('client_id', $id)
            ->first();

        return response()->json([
            'student' => $student,
        ]);
    }


   public function getPaymentsWithCourseDetails(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $search = $request->input('search'); // Optional filter

    $paymentsQuery = CoursePurchasePayment::with('coursePurchase.course', 'coursePurchase.user');

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
     * Show course purchases for a specific student
     */
    public function purchases($id)
    {
        $student = User::with(['coursePurchases.course'])
            ->where('role', 'student')
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
     * Show all purchases and payments for a specific student
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
