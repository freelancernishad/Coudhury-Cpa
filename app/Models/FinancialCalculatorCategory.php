<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancialCalculatorCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'order_index',
        'is_active',
    ];

    public function calculators()
    {
        return $this->hasMany(FinancialCalculator::class, 'category_id');
    }
}
