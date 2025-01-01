<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
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


    // Show user details
    public function show(User $user)
    {
        return response()->json($user);
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
}
