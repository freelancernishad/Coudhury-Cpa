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
        ->where('role', 'student');

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
    $search = $request->input('search');

    // Eager load course and direct User relation (asUser)
    $paymentsQuery = CoursePurchasePayment::with([
        'coursePurchase.course',
        'coursePurchase.asUser',  // direct User
    ])->where('status', 'paid');

    // Search filter using direct user relation
    if ($search) {
        $paymentsQuery->whereHas('coursePurchase.asUser', function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%");
        });
    }

    $payments = $paymentsQuery->paginate($perPage);

    // Transform after pagination
    $payments->getCollection()->transform(function ($payment) {
        $coursePurchase = $payment->coursePurchase;

        return [
            'payment_id' => $payment->id,
            'email' => $payment->coursePurchase->asUser->email ?? null,
            'stripe_payment_id' => $payment->stripe_payment_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
            'course_purchase_id' => $payment->course_purchase_id,
            'user_email' => $coursePurchase->asUser->email ?? null, // use asUser
            'course' => [
                'id' => $coursePurchase->course->id ?? null,
                'title' => $coursePurchase->course->title ?? 'N/A',
                'price' => $coursePurchase->course->price ?? 0,
                'recurring_price' => $coursePurchase->course->recurring_price ?? 0,
                'recurring_month' => $coursePurchase->course->recurring_month ?? 0,
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
