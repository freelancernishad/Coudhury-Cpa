<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialCalculator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'inputs',
        'formula',
        'results',
        'is_active',
    ];

    protected $casts = [
        'inputs' => 'array',
        'results' => 'array',
        'is_active' => 'boolean',
    ];
}
