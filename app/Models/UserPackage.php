<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;

class UserPackage extends Model
{
    protected $fillable = [
            'user_id',
            'package_id',
            'started_at',
            'ends_at',
            'business_name',
            'stripe_subscription_id',
            'stripe_customer_id', 
            'status',
            'canceled_at',
            'next_billing_at',
        ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Relationship: A UserPackage belongs to a User.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A UserPackage belongs to a Package.
     *
     * @return BelongsTo
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }





    /**
     * Relationship: A UserPackage has many UserPackageAddons.
     *
     * @return HasMany
     */
    public function addons(): HasMany
    {
        return $this->hasMany(UserPackageAddon::class, 'purchase_id', 'id');
    }

    /**
     * Relationship: A UserPackage has many Addons through UserPackageAddon.
     *
     * @return HasManyThrough
     */
    public function addonsDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            PackageAddon::class,
            UserPackageAddon::class,
            'purchase_id', // Foreign key on UserPackageAddon to UserPackage
            'id', // Foreign key on PackageAddon to be matched
            'id', // Local key on UserPackage to be matched
            'addon_id' // Foreign key in UserPackageAddon for PackageAddon
        );
    }

    /**
     * Relationship: A UserPackage has one Payment.
     *
     * @return HasOne
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get formatted package details.
     *
     * @return array
     */
    public function getFormattedDetails(): array
    {
        return [
            'package_name' => $this->package->name ?? 'N/A', // Package name
            'plan' => $this->getPlanType(), // Monthly or Yearly
            'active_date' => $this->started_at->toDateString(), // Active date
            'end_date' => $this->ends_at->toDateString(), // End date
            'status' => $this->getStatus(), // Active or Expired
        ];
    }

    /**
     * Determine the plan type (monthly/yearly) based on the package duration.
     *
     * @return string
     */
    protected function getPlanType(): string
    {
        $durationInMonths = $this->started_at->diffInMonths($this->ends_at);

        return $durationInMonths >= 12 ? 'yearly' : 'monthly';
    }

    /**
     * Determine the status of the package (active/expired).
     *
     * @return string
     */
    protected function getStatus(): string
    {
        $now = now();

        if ($this->started_at <= $now && $this->ends_at >= $now) {
            return 'active';
        }

        return 'expired';
    }

    /**
     * Get active packages for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActivePackages(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('started_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('package') // Eager load package details
            ->get()
            ->map(function ($userPackage) {
                return $userPackage->getFormattedDetails();
            });
    }

    /**
     * Get package history for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPackageHistory(int $userId)
    {
        return self::where('user_id', $userId)
            ->with('package') // Eager load package details
            ->orderBy('started_at', 'desc') // Order by start date (most recent first)
            ->get()
            ->map(function ($userPackage) {
                return $userPackage->getFormattedDetails();
            });
    }
}
