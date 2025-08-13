<?php

// app/Models/CourseContent.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'description',
        'link',
        'file_path'
    ];

    protected $appends = ['students_count'];
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    // Accessor for students_count
    public function getStudentsCountAttribute()
    {
        return $this->students()->count();
    }
    public function students()
    {
        return $this->belongsToMany(User::class, 'course_content_user');
    }


}
