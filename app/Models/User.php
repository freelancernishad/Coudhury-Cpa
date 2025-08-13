<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'email_verification_hash',
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


      /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate a unique client_id before creating the user
        static::creating(function ($user) {
            $user->client_id = static::generateUniqueClientId();
        });
    }

    /**
     * Generate a unique numeric client_id.
     *
     * @return int
     */
    protected static function generateUniqueClientId(): int
    {
        do {
            $clientId = mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (static::where('client_id', $clientId)->exists()); // Ensure it's unique

        return $clientId;
    }





    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }


    public function userPackage()
    {
        return $this->hasOne(UserPackage::class);
    }
    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
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
        $filePath = uploadFileToS3($file, 'profile_pictures'); // Define the S3 directory
        $this->profile_picture = $filePath;
        $this->save();

        return $filePath;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function coursePurchases()
    {
        return $this->hasMany(\App\Models\CoursePurchase::class)->where('status', 'paid');
    }


    public function courseContents()
    {
        return $this->belongsToMany(CourseContent::class, 'course_content_user');
    }




    /**
     * Get the last payment date of the user.
     *
     * @return string|null
     */
    public function getLastPaymentDateAttribute()
    {
        $lastPayment = $this->payments()->latest('paid_at')->first();
        return $lastPayment ? $lastPayment->paid_at : null;
    }

    /**
     * Get the last payment amount of the user.
     *
     * @return float|null
     */
    public function getLastPaymentAmountAttribute()
    {
        $lastPayment = $this->payments()->latest('paid_at')->first();
        return $lastPayment ? $lastPayment->amount : null;
    }



       /**
     * Calculate the total due amount for the user.
     */
    public function getTotalDueAttribute()
    {
        return $this->servicePurchased()->sum('due_amount');
    }

    /**
     * Relationship with ServicePurchased model.
     */
    public function servicePurchased()
    {
        return $this->hasMany(ServicePurchased::class)->where('status', '!=', 'pending');
    }


    public function getServicePurchasedListAttribute()
    {
        // Fetch the latest servicePurchased entry where status is not 'pending'
        $servicePurchased = $this->servicePurchased()
            ->where('status', '!=', 'pending') // Exclude pending status
            ->select('service_details')
            ->latest()
            ->first();

        // If no servicePurchased entry exists, return an empty array
        if (!$servicePurchased) {
            return [];
        }

        // Decode the service_details JSON (if it's stored as JSON)
        $serviceDetails = $servicePurchased->service_details;

        // Initialize an array to store the names
        $names = [];

        // Extract names from selected_services
        if (isset($serviceDetails['selected_services'])) {
            foreach ($serviceDetails['selected_services'] as $selectedService) {
                $names[] = $selectedService['name'];
            }
        }

        // Extract names from addons => selectedServices
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

    protected $appends = ['last_payment_date', 'last_payment_amount','total_due','service_purchased_list'];

}


