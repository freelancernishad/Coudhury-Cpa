<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    // transaction_id, user->client_id,user->name , user-> email, amount , paid_at,status
    protected $fillable = [
        'user_id', 'gateway', 'transaction_id', 'currency', 'amount', 'fee',
        'status', 'response_data', 'payment_method', 'payer_email', 'paid_at','coupon_id','payable_type','payable_id','user_package_id','event','stripe_session',
    ];

    protected $casts = [
        'response_data' => 'array', // Cast JSON data to an array
        'paid_at' => 'datetime', // Cast as a datetime
    ];

    // Define relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class);
    }


        /**
     * Scope for completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for refunded payments.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope for payments with discounts.
     */
    public function scopeDiscounted($query)
    {
        return $query->whereNotNull('coupon_id');
    }

    /**
     * Scope for payments by gateway.
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope for payments by service or package.
     */
    public function scopeForPayable($query, $payableType, $payableId)
    {
        return $query->where('payable_type', $payableType)->where('payable_id', $payableId);
    }





}
