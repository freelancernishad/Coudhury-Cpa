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
        'status', 'response_data', 'payment_method', 'payer_email', 'paid_at','coupon_id','payable_type','payable_id','user_package_id','event','stripe_session','payment_method_details'
    ];

    protected $casts = [
        'response_data' => 'array', // Cast JSON data to an array
        'paid_at' => 'datetime', // Cast as a datetime
        'payment_method_details' => 'object', // Cast to array
    ];


    /**
     * Accessor for payment_method_details.
     * Ensures the value is always returned as an object.
     *
     * @param mixed $value
     * @return object
     */
    public function getPaymentMethodDetailsAttribute($value)
    {
        // If the value is already an object, return it as is
        if (is_object($value)) {
            return $value;
        }

        // If the value is a JSON string, decode it into an object
        if (is_string($value)) {
            return json_decode($value);
        }

        // If the value is an array, convert it to an object
        if (is_array($value)) {
            return (object) $value;
        }

        // Fallback: return an empty object
        return (object) [];
    }


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




    protected static function boot()
    {
        parent::boot();

        // Generate a unique transaction_id before creating the payment
        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = static::generateUniqueTransactionId();
            }
        });
    }

    /**
     * Generate a unique transaction ID.
     *
     * @return string
     */
    public static function generateUniqueTransactionId()
    {
        $prefix = 'txn_'; // Prefix for the transaction ID
        $timestamp = now()->format('YmdHis'); // Current date and time in YYYYMMDDHHMMSS format
        $randomString = Str::random(3); // Random alphanumeric string of 6 characters

        // Combine prefix, timestamp, and random string
        $transactionId = $prefix . $timestamp . '_' . $randomString;

        return $transactionId;
    }



}
