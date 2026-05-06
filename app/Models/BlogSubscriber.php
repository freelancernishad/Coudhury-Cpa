<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'is_active',
        'unsubscribe_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unsubscribe_token)) {
                $model->unsubscribe_token = Str::random(40);
            }
        });
    }
}
