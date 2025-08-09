<?php

namespace App\Http\Controllers\Api\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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
                    'course'   => $purchase->course->title ?? '',
                    'amount'   => $purchase->amount,
                    'status'   => $purchase->status,
                    'payments' => $purchase->payments,
                ];
            }),
        ]);
    }

}
