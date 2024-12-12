<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    protected $fillable = ['user_id', 'package_id', 'started_at', 'ends_at'];

    /**
     * Relationship: A UserPackage belongs to a User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A UserPackage belongs to a Package.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Relationship: A UserPackage has many UserPackageAddons.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addons()
    {
        return $this->hasMany(UserPackageAddon::class,'purchase_id','id');
    }

    /**
     * Relationship: A UserPackage has many Addons through UserPackageAddon.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function addonsDetails()
    {
        // Corrected 'hasManyThrough' to link `PackageAddon` via `UserPackageAddon`
        return $this->hasManyThrough(
            PackageAddon::class,
            UserPackageAddon::class,
            'purchase_id',  // Foreign key on UserPackageAddon to UserPackage
            'id',               // Foreign key on PackageAddon to be matched
            'id',               // Local key on UserPackage to be matched
            'addon_id'          // Foreign key in UserPackageAddon for PackageAddon
        );
    }

    /**
     * Relationship: A UserPackage has many payments through UserPackageAddon.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
