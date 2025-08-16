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
        'file_name',
        'file_type',
        'file_size',
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
        $this->file_name = $file->getClientOriginalName();
        $this->file_type = $file->getClientMimeType();
        \Log::info('File Size (bytes): ' . $file->getSize());
      $this->file_size = round($file->getSize() / 1024, 2); // size in KB with 2 decimal places
        $this->save();

        return $filePath;
    }
}
