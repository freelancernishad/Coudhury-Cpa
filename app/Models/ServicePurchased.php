<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePurchased extends Model
{
    use HasFactory;

    protected $table = 'service_purchased';
    
    // Fields that can be mass-assigned
    protected $fillable = [
        'user_id',
        'date',
        'subtotal',
        'paid_amount',
        'due_amount',
        'status',
        'client_note',
        'admin_note',
        'discount_amount',
        'service_details', // JSON column
    ];

    // Cast JSON column to an array
    protected $casts = [
        'date' => 'datetime',
        'service_details' => 'array', // Cast JSON to array
    ];

    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Payment model (polymorphic)
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

}
