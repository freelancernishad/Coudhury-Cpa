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
        return $this->belongsTo(User::class)->select(['id', 'name', 'client_id', 'email', 'phone']);
    }

    // Relationship with Payment model (polymorphic)
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }


    // In App\Models\ServicePurchased

    // In App\Models\ServicePurchased

    public static function getGroupedByStatus($userId, $status = null)
    {
        // Fetch all ServicePurchased records for the user, excluding "pending" status
        $query = self::where('user_id', $userId)->with('files')
            ->where('status', '!=', 'pending') // Exclude "pending" status
            // ->with(['user' => function ($query) {
            //     $query->select(['id', 'name', 'client_id', 'email', 'phone']);
            // }])
            ->latest();

        // Apply status filter if provided
        if ($status) {
            $query->where('status', $status);
        }

        // Get the results
        $servicePurchasedList = $query->get();

        // Group records by status if no specific status is provided
        if (!$status) {
            $grouped = [
                'in_review' => $servicePurchasedList->where('status', 'In Review')->values(),
                'others' => $servicePurchasedList->where('status', '!=', 'In Review')->values(),
            ];
            return $grouped;
        }

        // Return filtered results if a specific status is provided
        return $servicePurchasedList;
    }


    protected $hidden = ['service_details'];
    protected $appends = ['formatted_service_details'];

    public function getFormattedServiceDetailsAttribute()
    {
        // Get the service_details array
        $serviceDetails = $this->service_details;

        // Initialize the result array
        $formattedDetails = [
            'selected_services' => [],
            'addons' => [],
        ];

        // Extract names from selected_services
        if (isset($serviceDetails['selected_services'])) {
            foreach ($serviceDetails['selected_services'] as $selectedService) {
                $formattedDetails['selected_services'][] = $selectedService['name'];
            }
        }

        // Extract names from addons => selectedServices
        if (isset($serviceDetails['addons'])) {
            foreach ($serviceDetails['addons'] as $addon) {
                $addonServices = [];
                if (isset($addon['selectedServices'])) {
                    foreach ($addon['selectedServices'] as $selectedService) {
                        $addonServices[] = $selectedService['name'];
                    }
                }
                $formattedDetails['addons'][] = $addonServices;
            }
        }

        return $formattedDetails;
    }




    public function files()
    {
        return $this->hasMany(ServicePurchasedFile::class)
            ->select('file_name', 'file_path', 'file_size', 'service_purchased_id')->with('admin');
    }


    public static function getTotalAmounts($userId, $status = null)
    {
        $query = self::where('user_id', $userId)->where('status', '!=', 'pending');

        if ($status) {
            $query->where('status', $status);
        }

        return [
            'total_paid_amount' => $query->sum('paid_amount'),
            'total_due_amount' => $query->sum('due_amount'),
        ];
    }



}
