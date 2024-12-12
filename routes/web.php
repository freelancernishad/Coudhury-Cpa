<?php

use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\SystemSettings\SystemSettingController;

Route::get('/', function () {
    return view('welcome');
});

// For web routes
Route::get('/clear-cache', [SystemSettingController::class, 'clearCache']);


Route::get('send-test-email', function () {
    $email = 'freelancernishad123@gmail.com';  // Enter your test email here

    try {
        Mail::to($email)->send(new TestMail());
        return response()->json('Test email sent!');
    } catch (\Exception $e) {
        return response()->json('Error: ' . $e->getMessage());
    }

});



Route::get('/files/{path}', function ($path) {
    try {
        // Check if the file exists in the protected disk
        if (!Storage::disk('protected')->exists($path)) {
            return response()->json([
                'error' => 'File not found',
            ], 404);
        }

        // Serve the file directly with custom headers
        return response()->file(Storage::disk('protected')->path($path))
            ->withHeaders([
                'Content-Type' => 'application/octet-stream',  // Adjust MIME type if needed
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 500);
    }
})->where('path', '.*');



