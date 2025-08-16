<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // changed from user_id
        'user_type',
        'course_id',
        'stripe_payment_id',
        'stripe_subscription_id',
        'amount',
        'currency',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    protected $appends = [
        'total_payment',
        'due_payment',
        'last_payment',
        'last_payment_date',
        'next_payment_date',
    ];

    // Relation names kept same, just updated foreign key
    public function user()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }


    // Direct relation with User model (for when you are sure it's a User)
    public function asUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Direct relation with Student model (for when you are sure it's a Student)
    public function asStudent()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function course_payments()
    {
        return $this->hasMany(CoursePurchasePayment::class, 'course_purchase_id');
    }

    public function payments()
    {
        return $this->hasMany(CoursePurchasePayment::class, 'course_purchase_id');
    }

    public function lastPayment()
    {
        return $this->hasOne(CoursePurchasePayment::class, 'course_purchase_id')
            ->where('status', 'paid')
            ->latest('paid_at');
    }

    public function notes()
    {
        return $this->hasMany(CourseNote::class);
    }

    // Status helper methods
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Accessors
    public function getTotalPaymentAttribute()
    {
        return $this->course_payments()->where('status', 'paid')->sum('amount');
    }

    public function getDuePaymentAttribute()
    {
        $coursePrice = $this->course ? (float) $this->course->price : 0;
        return max(0, $coursePrice - $this->total_payment);
    }

    public function getLastPaymentAttribute()
    {
        return $this->lastPayment()->first();
    }

    public function getLastPaymentDateAttribute()
    {
        return optional($this->last_payment)->paid_at;
    }

    public function getNextPaymentDateAttribute()
    {
        $coursePrice = $this->course ? (float) $this->course->price : 0;
        $totalPaid = $this->total_payment;

        if ($totalPaid >= $coursePrice) {
            return 'Paid';
        }

        if (!$this->last_payment_date) {
            return null;
        }

        $date = $this->last_payment_date instanceof Carbon
            ? $this->last_payment_date
            : Carbon::parse($this->last_payment_date);

        return $date->copy()->addMonth();
    }
}
