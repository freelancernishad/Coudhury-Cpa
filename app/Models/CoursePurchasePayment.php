<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePurchasePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_purchase_id',
        'stripe_payment_id',
        'amount',
        'status',
        'paid_at',
    ];

    protected $dates = [
        'paid_at',
    ];

    public function coursePurchase()
    {
        return $this->belongsTo(CoursePurchase::class, 'course_purchase_id');
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
}
