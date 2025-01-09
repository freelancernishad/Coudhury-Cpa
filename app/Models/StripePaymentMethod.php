<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripePaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_customer_id',
        'stripe_payment_method_id',
        'details',
        'is_default',
    ];

    protected $casts = [
        'details' => 'array', // Cast JSON to array
    ];

    /**
     * Get the Stripe customer associated with the payment method.
     */
    public function stripeCustomer()
    {
        return $this->belongsTo(StripeCustomer::class);
    }
}
