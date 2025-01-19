<?php

namespace App\Http\Controllers\Api\User\UserManagement;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        $user = Auth::user(); // Retrieve the authenticated user
        return response()->json($user);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user(); // Retrieve the authenticated user

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|image|max:2048',
            // 'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'nid_no' => 'sometimes|string|max:255',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'business_type' => 'sometimes|string|max:255', // Add validation for business_type
            'business_name' => 'sometimes|string|max:255', // Add validation for business_name
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update user's profile with validated data
        $user->update($request->only([
            'name',
            // 'email',
            'nid_no',
            'address_line1',
            'address_line2',
            'phone',
            'business_type', // Include business_type in the update
            'business_name', // Include business_name in the update
        ]));

        // Handle profile picture upload if provided
        if ($request->hasFile('profile_picture')) {
            try {
                $filePath = $user->saveProfilePicture($request->file('profile_picture'));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to upload profile picture: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json($user);
    }

    /**
     * Update client IDs for all users where client_id is NULL or 0.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateClientIds()
    {
        // Get all users where client_id is NULL or 0
        $users = User::all();

        foreach ($users as $user) {
            do {
                // Generate a random 6-digit number
                $clientId = mt_rand(100000, 999999);
            } while (User::where('client_id', $clientId)->exists()); // Ensure it's unique

            // Update the user with the unique client_id
            $user->update(['client_id' => $clientId]);
        }

        return response()->json(['message' => 'Client IDs updated successfully!']);
    }
}
