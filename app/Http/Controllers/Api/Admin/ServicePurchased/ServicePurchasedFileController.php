<?php

namespace App\Http\Controllers\Api\Admin\ServicePurchased;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ServicePurchasedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServicePurchasedFileController extends Controller
{
    /**
     * Upload a file and save its details to the database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required', // File is required
            'file' => 'required|file', // File is required
            'service_purchased_id' => 'required|exists:service_purchased,id', // service_purchased_id is required
            'service_name' => 'required|string', // service_name is required
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Upload the file and save its details
        $user_id = $request->file('user_id');
        $file = $request->file('file');
        $servicePurchasedId = $request->input('service_purchased_id');
        $serviceName = $request->input('service_name');

        $uploadedFile = ServicePurchasedFile::ServicePurchasedFileUpload($file, $servicePurchasedId,$user_id, $serviceName);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $uploadedFile,
        ]);
    }

    /**
     * Get the list of files grouped by folder_name.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilesGroupedByFolder(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id', // user_id is required
            'folder_name' => 'nullable|string', // folder_name is optional
            'year' => 'nullable|integer|min:2000|max:2100', // year is optional
            'month' => 'nullable|integer|min:1|max:12', // month is optional
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Extract validated data
        $userId = $request->input('user_id');
        $folderName = $request->input('folder_name');
        $year = $request->input('year');
        $month = $request->input('month');

        // Initialize the query
        $query = ServicePurchasedFile::query();

        // Filter by user_id (required)
        $query->where('user_id', $userId);

        // Filter by folder_name (if provided)
        if ($folderName) {
            $query->where('folder_name', $folderName);
        }

        // Filter by year (if provided)
        if ($year) {
            $query->where('year', $year);
        }

        // Filter by month (if provided)
        if ($month) {
            $query->where('month', $month);
        }

        // Fetch the results
        $files = $query->get();

        // Response structure
        $response = [];

        // Case 1: Only folder_name is provided
        if ($folderName && !$year && !$month) {
            // Group by year
            $years = $files->groupBy('year')->keys();

            $response = [
                'folder_name' => $folderName,
                'years' => $years,
            ];
        }
        // Case 2: folder_name and year are provided
        elseif ($folderName && $year && !$month) {
            // Group by month
            $months = $files->groupBy('month')->keys();

            $response = [
                'folder_name' => $folderName,
                'year' => $year,
                'months' => $months,
            ];
        }
        // Case 3: folder_name, year, and month are provided
        elseif ($folderName && $year && $month) {
            // Get files for the specified month
            $files = $files->map(function ($item) {
                return [
                    'id' => $item->id,
                    'file_name' => $item->file_name,
                    'file_path' => $item->file_path,
                    'file_extension' => $item->file_extension,
                    'file_size' => $item->file_size,
                    'mime_type' => $item->mime_type,
                    'year' => $item->year,
                    'month' => $item->month,
                    'date' => $item->date,
                    'service_name' => $item->service_name,
                ];
            });

            $response = [
                'folder_name' => $folderName,
                'year' => $year,
                'month' => $month,
                'files' => $files,
            ];
        }
        // Case 4: No folder_name, year, or month provided
        else {
            // Return all folders for the user
            $folders = $files->groupBy('folder_name')->keys();

            $response = [
                'folders' => $folders,
            ];
        }

        return response()->json($response);
    }


    /**
     * Get the latest upload timestamp grouped by user_id and service_purchased_id.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestUploadsGroupedByUserAndService(Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        // 'user_id' => 'required|exists:users,id', // user_id is required (if needed)
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Fetch the latest upload timestamp grouped by user_id and service_purchased_id
    $latestUploads = ServicePurchasedFile::select(
            'service_purchased_files.user_id',
            'service_purchased_files.service_purchased_id',
            'users.name as user_name', // Include user's name
            'users.email as user_email', // Include user's email
            DB::raw('MAX(service_purchased_files.created_at) as latest_upload')
        )
        ->join('users', 'service_purchased_files.user_id', '=', 'users.id') // Join the users table
        // ->where('service_purchased_files.user_id', $userId) // Uncomment if user_id filter is needed
        ->groupBy('service_purchased_files.user_id', 'service_purchased_files.service_purchased_id', 'users.name', 'users.email')
        ->get();

    // Transform the response
    $response = $latestUploads->map(function ($item) {
        // Calculate time ago using Carbon
        $latestUploadTime = Carbon::parse($item->latest_upload);
        $timeAgo = $latestUploadTime->diffForHumans(); // e.g., "1 hour ago", "2 days ago"

        return [
            'user_id' => $item->user_id,
            'service_purchased_id' => $item->service_purchased_id,
            'user_name' => $item->user_name, // Include user's name
            'user_email' => $item->user_email, // Include user's email
            'latest_upload' => $item->latest_upload,
            'time_ago' => $timeAgo, // Include time ago
        ];
    });

    return response()->json($response);
}
}
