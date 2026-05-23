<?php


use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

function handleGoogleAuth(Request $request)
{
    $authConfig = getDomainAuthConfig($request);
    $role = $authConfig['role'];
    $redirectUrl = $authConfig['redirect_url'];

    $request->merge([
        'role' => $role
    ]);

    // Validate the access token
    $validator = Validator::make($request->all(), [
        'access_token' => 'required|string',
        'role' => 'in:client,student',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Fetch user data from Google API
        $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'access_token' => $request->access_token,
        ]);

        if ($response->failed() || !isset($response['email'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid access token.',
            ], 400);
        }

        $userData = $response->json();
        $user = User::where('email', $userData['email'])->first();

        if ($user) {
            if ($user->role !== $authConfig['role']) {
                try {
                    $token = JWTAuth::fromUser($user, ['guard' => 'user']);
                } catch (\Exception $e) {
                    $token = null;
                }

                $originUrl = getAppOrigin($request);
                $parsed = parse_url($originUrl);
                $scheme = $parsed['scheme'] ?? 'http';
                $host = $parsed['host'] ?? '';
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

                if ($user->role === 'student') {
                    if ($host && !str_starts_with($host, 'cu.') && !str_contains($host, '.cu.')) {
                        if ($host === 'localhost' || $host === '127.0.0.1') {
                            $host = 'cu.localhost';
                        } else {
                            $host = 'cu.' . $host;
                        }
                    }
                } else {
                    if ($host && (str_starts_with($host, 'cu.') || str_contains($host, '.cu.'))) {
                        if (str_starts_with($host, 'cu.')) {
                            $host = substr($host, 3);
                        }
                    }
                }
                $targetOrigin = $scheme . '://' . $host . $port;
                $targetLoginUrl = $targetOrigin . '/login?token=' . $token;

                if ($user->role === 'student') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Students are not allowed to login from the main domain.',
                        'redirect_url' => $targetLoginUrl,
                    ], 403);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Clients are not allowed to login from the student domain.',
                        'redirect_url' => $targetLoginUrl,
                    ], 403);
                }
            }
        }

        if (!$user) {

            $role = $request->input('role', 'client');
            // Register the user if they don't exist
            $user = User::create([
                'name' => $userData['name'] ?? explode('@', $userData['email'])[0],
                'email' => $userData['email'],
                'password' => Hash::make(Str::random(16)), // Generate a random password
                'email_verified_at' => now(),
                'role' => $role,
            ]);
        } else {
            $user->update(['email_verified_at' => now()]);
        }

        // Authenticate the user
        Auth::login($user);

        // Custom payload data
        $payload = [
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'category' => $user->category ?? 'default', // Assuming category might not be set for Google users
            'email_verified' => $user->hasVerifiedEmail(),
        ];

        try {
            // Generate a JWT token with custom claims
            $token = JWTAuth::fromUser($user, ['guard' => 'user']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
            'redirect_url' => $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . 'token=' . $token,
            'detected_origin' => $authConfig['detected_origin'] ?? null,
            'requested_origin' => $request->header('Origin') ?: $request->header('Referer'),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'An error occurred during authentication.',
            'details' => $e->getMessage(),
        ], 500);
    }
}





function handleAppleAuth(Request $request)
{
    $authConfig = getDomainAuthConfig($request);
    $role = $authConfig['role'];
    $redirectUrl = $authConfig['redirect_url'];

    $request->merge([
        'role' => $role
    ]);

    $validator = Validator::make($request->all(), [
        'identity_token' => 'required|string',
        'name' => 'nullable|string',
        'role' => 'in:client,student', // role validation like Google Auth
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Decode Apple Identity Token (you already have this function)
        $appleUserInfo = decodeAppleIdentityToken($request->identity_token);
        Log::info($appleUserInfo);

        if (!$appleUserInfo || !isset($appleUserInfo['email'])) {
            return response()->json(['error' => 'Invalid Apple token'], 400);
        }

        $user = User::where('email', $appleUserInfo['email'])->first();

        if ($user) {
            if ($user->role !== $authConfig['role']) {
                try {
                    $token = JWTAuth::fromUser($user, ['guard' => 'user']);
                } catch (\Exception $e) {
                    $token = null;
                }

                $originUrl = getAppOrigin($request);
                $parsed = parse_url($originUrl);
                $scheme = $parsed['scheme'] ?? 'http';
                $host = $parsed['host'] ?? '';
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

                if ($user->role === 'student') {
                    if ($host && !str_starts_with($host, 'cu.') && !str_contains($host, '.cu.')) {
                        if ($host === 'localhost' || $host === '127.0.0.1') {
                            $host = 'cu.localhost';
                        } else {
                            $host = 'cu.' . $host;
                        }
                    }
                } else {
                    if ($host && (str_starts_with($host, 'cu.') || str_contains($host, '.cu.'))) {
                        if (str_starts_with($host, 'cu.')) {
                            $host = substr($host, 3);
                        }
                    }
                }
                $targetOrigin = $scheme . '://' . $host . $port;
                $targetLoginUrl = $targetOrigin . '/login?token=' . $token;

                if ($user->role === 'student') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Students are not allowed to login from the main domain.',
                        'redirect_url' => $targetLoginUrl,
                    ], 403);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Clients are not allowed to login from the student domain.',
                        'redirect_url' => $targetLoginUrl,
                    ], 403);
                }
            }
        }

        if (!$user) {
            $role = $request->input('role', 'client'); // default role

            // Register new user
            $user = User::create([
                'name' => $request->name ?? explode('@', $appleUserInfo['email'])[0],
                'email' => $appleUserInfo['email'],
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
                'role' => $role,
                // যদি profile_completion লাগে তাহলে দিতে পারেন,
                // 'profile_completion' => 10,
            ]);
        } else {
            // Existing user, update email verified timestamp
            $user->update(['email_verified_at' => now()]);
        }

        Auth::login($user);

        // Prepare JWT token payload like Google Auth
        $payload = [
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'category' => $user->category ?? 'default',
            'email_verified' => $user->hasVerifiedEmail(),
        ];

        try {
            $token = JWTAuth::fromUser($user, ['guard' => 'user']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
            'redirect_url' => $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . 'token=' . $token,
            'detected_origin' => $authConfig['detected_origin'] ?? null,
            'requested_origin' => $request->header('Origin') ?: $request->header('Referer'),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'An error occurred during Apple authentication.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

// Helper function, your existing one
function decodeAppleIdentityToken($identityToken)
{
    try {
        $tokenParts = explode(".", $identityToken);
        $payload = base64_decode(strtr($tokenParts[1], '-_', '+/'));
        return json_decode($payload, true);
    } catch (\Exception $e) {
        return null;
    }
}
