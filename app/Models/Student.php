<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Student extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'profile_picture',
        'password',
        'email_verification_hash',
        'email_verified_at',
        'otp',
        'otp_expires_at',
        'client_id',
        'status',
        'role',
        'nid_no',
        'address_line1',
        'address_line2',
        'phone',
        'business_type',
        'business_name',
        'stripe_customer_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'email_verification_hash',
        'otp',
        'otp_expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            $student->client_id = static::generateUniqueClientId();
        });
    }

    protected static function generateUniqueClientId(): int
    {
        do {
            $clientId = mt_rand(100000, 999999);
        } while (static::where('client_id', $clientId)->exists());

        return $clientId;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // Relations using user_id as foreign key
    public function userPackage()
    {
        return $this->hasOne(UserPackage::class, 'user_id');
    }

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class, 'user_id');
    }

    public function currentPackage()
    {
        return $this->userPackage ? $this->userPackage->package : null;
    }

    public function hasFeature($feature)
    {
        $package = $this->currentPackage();
        return $package && in_array($feature, $package->features);
    }

    public function saveProfilePicture($file)
    {
        $filePath = uploadFileToS3($file, 'profile_pictures');
        $this->profile_picture = $filePath;
        $this->save();
        return $filePath;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }


        public function getMorphClass()
    {
        return 'student'; // এখানে 'student' ব্যবহার হবে full class name নয়
    }
public function coursePurchases()
{
    return $this->morphMany(CoursePurchase::class, 'user', 'user_type', 'user_id')
                ->where('status', 'paid');
}



    public function courseContents()
    {
        return $this->belongsToMany(
            CourseContent::class,
            'course_content_user',
            'user_id', // foreign key in pivot table
            'course_content_id' // related key
        );
    }

    public function getLastPaymentDateAttribute()
    {
        $lastPayment = $this->payments()->latest('paid_at')->first();
        return $lastPayment ? $lastPayment->paid_at : null;
    }

    public function getLastPaymentAmountAttribute()
    {
        $lastPayment = $this->payments()->latest('paid_at')->first();
        return $lastPayment ? $lastPayment->amount : null;
    }

    public function getTotalDueAttribute()
    {
        return $this->servicePurchased()->sum('due_amount');
    }

    public function servicePurchased()
    {
        return $this->hasMany(ServicePurchased::class, 'user_id')->where('status', '!=', 'pending');
    }

    public function getServicePurchasedListAttribute()
    {
        $servicePurchased = $this->servicePurchased()
            ->where('status', '!=', 'pending')
            ->select('service_details')
            ->latest()
            ->first();

        if (!$servicePurchased) {
            return [];
        }

        $serviceDetails = $servicePurchased->service_details;
        $names = [];

        if (isset($serviceDetails['selected_services'])) {
            foreach ($serviceDetails['selected_services'] as $selectedService) {
                $names[] = $selectedService['name'];
            }
        }

        if (isset($serviceDetails['addons'])) {
            foreach ($serviceDetails['addons'] as $addon) {
                if (isset($addon['selectedServices'])) {
                    foreach ($addon['selectedServices'] as $selectedService) {
                        $names[] = $selectedService['name'];
                    }
                }
            }
        }

        return $names;
    }

    protected $appends = [
        'last_payment_date',
        'last_payment_amount',
        'total_due',
        'service_purchased_list'
    ];
}
