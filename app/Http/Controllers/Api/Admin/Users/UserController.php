<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ServicePurchased;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // List users with optional search
    public function index(Request $request)
    {
        $query = User::query();

        // Apply search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Set dynamic pagination
        $perPage = $request->input('per_page', 10); // Default to 10 if not specified
        $users = $query->paginate($perPage);

        return response()->json($users);
    }




    public function store(Request $request)
    {
        // Define validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'profile_picture' => 'sometimes|image|max:2048',
            'status' => 'sometimes|string|max:255',
            'nid_no' => 'sometimes|string|max:255',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
        ];

        // Validate the request
        $validationResponse = validateRequest($request->all(), $rules);
        if ($validationResponse) {
            return $validationResponse; // Return if validation fails
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? 'active', // Default to 'active' if not provided
            'nid_no' => $request->nid_no,
            'address_line1' => $request->address_line1,
            'address_line2' => $request->address_line2,
            'phone' => $request->phone,
        ]);

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

        return response()->json($user, 201);
    }


    public function show(Request $request, $id)
    {
        // Get the status from the request parameters
        $status = $request->query('status');

        // Get the user details
        $user = User::where('client_id', $id)->first();

        // Check if the user exists
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Get ServicePurchased lists based on the status
        $servicePurchasedLists = ServicePurchased::getGroupedByStatus($user->id, $status);

        // Combine user details and ServicePurchased lists
        $response = [
            'user' => $user,
            'service_purchased' => $servicePurchasedLists,
        ];

        return response()->json($response);
    }


    // Update a user
    public function update(Request $request, User $user)
    {
        // Define validation rules
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'profile_picture' => 'sometimes|image|max:2048',
            'status' => 'sometimes|string|max:255',
            'nid_no' => 'sometimes|string|max:255',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
        ];

        // Validate the request
        $validationResponse = validateRequest($request->all(), $rules);
        if ($validationResponse) {
            return $validationResponse; // Return if validation fails
        }

        // Prepare the data array for update
        $data = [
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'password' => isset($request->password) ? Hash::make($request->password) : $user->password,
            'status' => $request->status ?? $user->status,
            'nid_no' => $request->nid_no ?? $user->nid_no,
            'address_line1' => $request->address_line1 ?? $user->address_line1,
            'address_line2' => $request->address_line2 ?? $user->address_line2,
            'phone' => $request->phone ?? $user->phone,
        ];

        // Update the user with the new data
        $user->update($data);

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

    // Delete a user
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }



     /**
     * Activate or deactivate a user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function toggleStatus(Request $request, User $user)
    {
        // Validate the request
        $request->validate([
            'status' => 'sometimes|string|in:active,inactive', // Optional status parameter
        ]);

        // Toggle the status if no specific status is provided
        if (!$request->has('status')) {
            $user->status = $user->status === 'active' ? 'inactive' : 'active';
        } else {
            // Set the status to the provided value
            $user->status = $request->input('status');
        }

        // Save the updated status
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data' => $user,
        ]);
    }




}
