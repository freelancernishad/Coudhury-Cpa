<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'description', 'price', 'duration_days', 'features'];

    // Accessor to get features as an array
    public function getFeaturesAttribute($value)
    {
        return json_decode($value, true);
    }

    // Mutator to set features as JSON
    public function setFeaturesAttribute($value)
    {
        $this->attributes['features'] = json_encode($value);
    }


    /**
     * Relationship to the PackageDiscount model.
     */
    public function discounts()
    {
        return $this->hasMany(PackageDiscount::class);
    }

    /**
     * Calculate discounted price based on selected duration.
     *
     * @param int $duration Duration in months (e.g., 1 for monthly, 12 for yearly)
     * @return float Discounted price
     */
    public function calculateDiscountedPrice(int $duration): float
    {
        $discountRate = $this->discounts()->where('duration_months', $duration)->value('discount_rate') ?? 0;

        // Calculate the total price for the duration
        $totalPrice = $this->price * $duration;

        // Apply discount
        $discountedPrice = $totalPrice - ($totalPrice * ($discountRate / 100));

        return round($discountedPrice, 2);
    }

    // Add these properties to the append array
    protected $appends = ['discount_rate', 'discounted_price'];


    // Accessor to get the discount rate dynamically
    public function getDiscountRateAttribute()
    {
        $durationMonths = request()->query('discount_months', 0); // Get duration from URL query
        $discountData = applyDiscount($this->price, $durationMonths, $this->discounts->toArray());

        return $discountData['discount_rate']; // Return the calculated discount rate
    }

    // Accessor to get the discounted price dynamically
    public function getDiscountedPriceAttribute()
    {
        $durationMonths = request()->query('discount_months', 0); // Get duration from URL query
        $discountData = applyDiscount($this->price, $durationMonths, $this->discounts->toArray());

        return $discountData['discounted_price']; // Return the calculated discounted price
    }



}
