<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    // অ্যাপেন্ড করা হচ্ছে যেগুলো JSON রেসপন্সে যাবে
    protected $appends = [
        'total_payment',
        'due_payment',
        'last_payment',
        'last_payment_date',
        'next_payment_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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




    // নতুন: সর্বশেষ পেইড পেমেন্ট রিলেশন
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

    // অ্যাক্সেসরগুলো

    public function getTotalPaymentAttribute()
    {
        return $this->course_payments()->where('status', 'paid')->sum('amount');
    }

public function getDuePaymentAttribute()
{
    $coursePrice = $this->course ? (float) $this->course->price : 0;
    return max(0, $coursePrice - $this->total_payment);
}

    // last_payment অ্যাক্সেসর এখন lastPayment রিলেশন থেকে ডেটা নিচ্ছে
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
    // course price এবং total payment নিয়ে আসা
    $coursePrice = $this->course ? (float) $this->course->price : 0;
    $totalPaid = $this->total_payment;

    // যদি পুরো course price already paid হয়ে থাকে, তাহলে null রিটার্ন করো
    if ($totalPaid >= $coursePrice) {
        return 'Paid';
    }

    // last payment date না থাকলে null
    if (!$this->last_payment_date) {
        return null;
    }

    // last_payment_date কে Carbon ইনস্ট্যান্সে কনভার্ট করা
    $date = $this->last_payment_date instanceof Carbon
        ? $this->last_payment_date
        : Carbon::parse($this->last_payment_date);

    // last_payment_date থেকে ১ মাস যোগ করে রিটার্ন করো
    return $date->copy()->addMonth();
}


}
