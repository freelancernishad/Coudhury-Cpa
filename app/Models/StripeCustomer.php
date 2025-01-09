<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stripe_customer_id',
    ];

    /**
     * Get the payment methods associated with the Stripe customer.
     */
    public function paymentMethods()
    {
        return $this->hasMany(StripePaymentMethod::class);
    }
}
