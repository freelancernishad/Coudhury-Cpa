<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_purchase_id',
        'note_text',
        'file_path',
    ];

    public function coursePurchase()
    {
        return $this->belongsTo(CoursePurchase::class);
    }
}
