<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePurchasedFile extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = [
        'service_purchased_id',
        'file_name',
        'file_path',
        'file_extension',
        'file_size',
        'mime_type',
    ];

    // Relationship with ServicePurchased model
    public function servicePurchased()
    {
        return $this->belongsTo(ServicePurchased::class);
    }

    /**
     * Upload a file to S3 and save its details to the database.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $servicePurchasedId
     * @return \App\Models\ServicePurchasedFile
     */

    public static function ServicePurchasedFileUpload($file, $servicePurchasedId)
    {
        // Define the S3 directory
        $directory = 'service_purchased_files';

        // Upload the file to S3 using the custom function
        $filePath = uploadFileToS3($file, $directory);

        // Save file details to the database
        $servicePurchasedFile = self::create([
            'service_purchased_id' => $servicePurchasedId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return $servicePurchasedFile;
    }

    /**
     * Get the full URL of the file from S3.
     *
     * @return string|null
     */
    public function getFileUrl()
    {
        if ($this->file_path) {
            return Storage::disk('s3')->url($this->file_path);
        }
        return null;
    }
}
