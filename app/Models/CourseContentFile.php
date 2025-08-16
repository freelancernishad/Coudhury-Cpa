<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseContentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_content_id',
        'file_path',
        'link',
    ];

    public function content()
    {
        return $this->belongsTo(CourseContent::class, 'course_content_id');
    }

    public function saveFile($file)
    {
        $filePath = uploadFileToS3($file, 'course_content_file'); // S3 folder name
        $this->file_path = $filePath;
        $this->save();

        return $filePath;
    }
}
