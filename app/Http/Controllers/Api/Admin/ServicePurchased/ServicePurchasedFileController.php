<?php

namespace App\Http\Controllers\Api\Admin\ServicePurchased;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use App\Models\ServicePurchased;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ServicePurchasedFile;
use App\Models\User;
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
    public function uploadFiles(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file', // Validate each file in the array
            'service_purchased_id' => 'required|exists:service_purchased,id',
            'service_name' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Fetch the user_id from the service_purchased table
        $servicePurchasedId = $request->input('service_purchased_id');
        $servicePurchased = ServicePurchased::find($servicePurchasedId);

        if (!$servicePurchased) {
            return response()->json(['message' => 'Service purchased record not found'], 404);
        }

        $userId = $servicePurchased->user_id; // Get user_id from service_purchased

        // Get additional data from the request
        $serviceName = $request->input('service_name');
        $note = $request->input('note');

        // Initialize an array to store uploaded file details
        $uploadedFiles = [];

        // Loop through each file and upload it
        foreach ($request->file('files') as $file) {
            $uploadedFile = ServicePurchasedFile::ServicePurchasedFileUpload(
                $file,
                $servicePurchasedId,
                $userId,
                $note,
                $serviceName
            );

            // Add the uploaded file details to the array
            $uploadedFiles[] = $uploadedFile;
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles,
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
            'user_id' => 'nullable|exists:users,client_id', // user_id is optional
            'folder_name' => 'nullable|string', // folder_name is optional
            'year' => 'nullable|integer|min:2000|max:2100', // year is optional
            'month' => 'nullable|integer|min:1|max:12', // month is optional
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get the authenticated user's ID based on the guard
        if (Auth::guard('admin')->check()) {
            // If the user is an admin, allow them to specify a user_id in the request
            $client_id = $request->input('user_id') ?? '';

            // Find the user by client_id
            $user = User::where('client_id', $client_id)->first();

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'message' => 'User not found with the provided client_id.',
                ], 404);
            }

            $userId = $user->id;


        } elseif (Auth::guard('web')->check()) {
            // If the user is a regular user, use their authenticated user_id
            $userId = Auth::guard('web')->user()->id;
        } else {
            // If no authenticated user, return an error
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Extract validated data
        $folderName = $request->input('folder_name');
        $year = $request->input('year');
        $month = $request->input('month');

        // Initialize the query
        $query = ServicePurchasedFile::query();

        // Filter by user_id
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
            $years = $files->groupBy('year')->map(function ($yearFiles, $year) {
                return [
                    'year' => $year,
                    'file_counts' => $this->getMeaningfulFileCounts($yearFiles),
                    'total_files' => $yearFiles->count(),
                ];
            })->values();

            $response = [
                'folder_name' => $folderName,
                'years' => $years,
            ];
        }
        // Case 2: folder_name and year are provided
        elseif ($folderName && $year && !$month) {
            // Group by month
            $months = $files->groupBy('month')->map(function ($monthFiles, $month) {
                return [
                    'month' => $month,
                    'file_counts' => $this->getMeaningfulFileCounts($monthFiles),
                    'total_files' => $monthFiles->count(),
                ];
            })->values();

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
        // Case 4: No folder_name, year, or month provided (only user_id)
        else {
            // Default folders
            $defaultFolders = [
                'Uploaded Documents by CPA Admin',
                'Uploaded Documents by Me',
                // 'Uploaded Documents by Client',
            ];

            // Prepare the response structure
            $response = [
                'folders' => [],
            ];

            foreach ($defaultFolders as $folder) {
                // Filter files for the current folder
                $folderFiles = $files->where('folder_name', $folder);

                // Add folder details to the response
                $response['folders'][] = [
                    'folder_name' => $folder,
                    'file_counts' => $this->getMeaningfulFileCounts($folderFiles),
                    'total_files' => $folderFiles->count(),
                ];
            }

            // Add year list
            $years = $files->groupBy('year')->map(function ($yearFiles, $year) {
                return [
                    'year' => $year,
                    'file_counts' => $this->getMeaningfulFileCounts($yearFiles),
                    'total_files' => $yearFiles->count(),
                ];
            })->values();

            $response['years'] = $years;

            // Add month list
            $months = $files->groupBy('month')->map(function ($monthFiles, $month) {
                return [
                    'month' => $month,
                    'file_counts' => $this->getMeaningfulFileCounts($monthFiles),
                    'total_files' => $monthFiles->count(),
                ];
            })->values();

            $response['months'] = $months;
        }

        return response()->json($response);
    }

    /**
     * Generate meaningful file counts grouped by file type.
     *
     * @param \Illuminate\Support\Collection $files
     * @return array
     */
    protected function getMeaningfulFileCounts($files)
    {
        // Map file extensions to their corresponding file types
        $fileTypeMap = [
            'png' => 'images',
            'jpg' => 'images',
            'jpeg' => 'images',
            'gif' => 'images',
            'pdf' => 'PDF files',
            'doc' => 'Word files',
            'docx' => 'Word files',
            'xls' => 'Excel files',
            'xlsx' => 'Excel files',
            'txt' => 'text files',
            'zip' => 'ZIP files',
            // Add more mappings as needed
        ];

        // Group files by their type and count them
        $fileCounts = [];
        foreach ($files as $file) {
            $extension = strtolower($file->file_extension);
            $fileType = $fileTypeMap[$extension] ?? 'other files'; // Default to 'other files' if extension is not mapped
            if (!isset($fileCounts[$fileType])) {
                $fileCounts[$fileType] = 0;
            }
            $fileCounts[$fileType]++;
        }

        // Generate meaningful messages
        $meaningfulMessages = [];
        foreach ($fileCounts as $fileType => $count) {
            $meaningfulMessages[] = "$count $fileType";
        }

        return $meaningfulMessages;
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
            'per_page' => 'nullable|integer|min:1|max:100', // Validate per_page query parameter
            // 'user_id' => 'required|exists:users,id', // user_id is required (if needed)
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get the number of items per page (default to 20 if not provided)
        $perPage = $request->input('per_page', 20);

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
            ->orderBy('latest_upload', 'desc') // Order by latest_upload in descending order
            ->paginate($perPage); // Use Laravel's pagination

        // Transform the items in the paginated result
        $latestUploads->getCollection()->transform(function ($item) {
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

        // Manually construct the response to match the desired structure
        $response = [
            'current_page' => $latestUploads->currentPage(),
            'data' => $latestUploads->items(),
            'first_page_url' => $latestUploads->url(1),
            'from' => $latestUploads->firstItem(),
            'last_page' => $latestUploads->lastPage(),
            'last_page_url' => $latestUploads->url($latestUploads->lastPage()),
            'links' => [
                [
                    'url' => $latestUploads->previousPageUrl(),
                    'label' => '&laquo; Previous',
                    'active' => false,
                ],
                [
                    'url' => $latestUploads->url(1),
                    'label' => '1',
                    'active' => $latestUploads->currentPage() === 1,
                ],
                [
                    'url' => $latestUploads->nextPageUrl(),
                    'label' => 'Next &raquo;',
                    'active' => false,
                ],
            ],
            'next_page_url' => $latestUploads->nextPageUrl(),
            'path' => $latestUploads->path(),
            'per_page' => $latestUploads->perPage(),
            'prev_page_url' => $latestUploads->previousPageUrl(),
            'to' => $latestUploads->lastItem(),
            'total' => $latestUploads->total(),
        ];

        return response()->json($response);
    }
}
