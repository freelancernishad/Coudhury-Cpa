<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_description',
        'description',
        'price',
        'recurring_price',
        'recurring_month',
        'vat_tax_type',
        'vat_tax_value',
    ];

    protected static function booted()
    {
        static::creating(function ($course) {
            $course->calculateRecurringPrice();
        });

        static::updating(function ($course) {
            $course->calculateRecurringPrice();
        });
    }

    /**
     * Calculate total price including VAT and divide by months to get recurring price.
     */
    public function calculateRecurringPrice()
    {
        $total = $this->price;

        if ($this->vat_tax_type === 'percent') {
            $total += ($this->price * ($this->vat_tax_value / 100));
        } elseif ($this->vat_tax_type === 'flat') {
            $total += $this->vat_tax_value;
        }

        $this->recurring_price = $this->recurring_month > 0
            ? round($total / $this->recurring_month, 2)
            : $total;
    }
}
