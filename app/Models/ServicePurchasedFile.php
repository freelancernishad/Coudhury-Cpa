<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ServicePurchasedFile extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = [
        'user_id',
        'service_purchased_id',
        'file_name',
        'file_path',
        'file_extension',
        'file_size',
        'mime_type',
        'folder_name', // New column
        'year',        // New column
        'month',       // New column
        'date',        // New column
        'service_name',// New column
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
     * @param string $serviceName (name of the service)
     * @return \App\Models\ServicePurchasedFile
     */
    public static function ServicePurchasedFileUpload($file, $servicePurchasedId,$userId, $serviceName='')
    {
        // Define the S3 directory
        $directory = 'service_purchased_files';

        // Upload the file to S3 using the custom function
        $filePath = uploadFileToS3($file, $directory);

        // Get the current date
        $currentDate = Carbon::now();

        // Determine the folder name based on who uploaded the file
        $folderName = self::getUploadedByFolderName();

        // Save file details to the database
        $servicePurchasedFile = self::create([
            'user_id' => $userId,
            'service_purchased_id' => $servicePurchasedId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'folder_name' => $folderName, // Set folder name
            'year' => $currentDate->year, // Set year
            'month' => $currentDate->month, // Set month
            'date' => $currentDate->day, // Set date
            'service_name' => $serviceName, // Set service name
        ]);

        return $servicePurchasedFile;
    }

    /**
     * Determine the folder name based on the authenticated user.
     *
     * @return string
     */
    protected static function getUploadedByFolderName()
    {
        if (Auth::guard('admin')->check()) {
            return 'Uploaded Documents by CPA Admin';
        } elseif (Auth::guard('web')->check()) {
            return 'Uploaded Documents by Client';
        }

        // Default folder name if no user is authenticated
        return 'Uploaded Documents by Unknown';
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
