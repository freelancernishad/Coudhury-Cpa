<?php

namespace App\Http\Controllers\Api\Auth\Student;

use App\Models\Student;
use App\Mail\VerifyEmail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\OtpNotification;
use App\Models\TokenBlacklist;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthStudentController extends Controller
{
    /**
     * Register a new student.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:students',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Create the student
        $student = Student::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'student',
        ]);

        // Generate a JWT token for the newly created student
        try {
            $token = JWTAuth::fromUser($student, ['guard' => 'student']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Generate verification URL or OTP
        $verify_url = $request->verify_url ?? null;

        if ($verify_url) {
            Mail::to($student->email)->send(new VerifyEmail($student, $verify_url));
        } else {
            $otp = random_int(100000, 999999);
            $student->otp = Hash::make($otp);
            $student->otp_expires_at = now()->addMinutes(5);
            $student->save();

            Mail::to($student->email)->send(new OtpNotification($otp));
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'email'          => $student->email,
                'name'           => $student->name,
                'role'           => $student->role,
                'email_verified' => $student->hasVerifiedEmail(),
            ],
        ], 201);
    }

    /**
     * Log in a student.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::guard('student')->attempt($credentials)) {
            $student = Auth::guard('student')->user();

            if (!$student->hasVerifiedEmail()) {
                $verify_url = $request->verify_url ?? null;

                if ($verify_url) {
                    try {
                        Mail::to($student->email)->send(new VerifyEmail($student, $verify_url));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification email: ' . $e->getMessage());
                    }
                } else {
                    $otp = random_int(100000, 999999);
                    $student->otp = Hash::make($otp);
                    $student->otp_expires_at = now()->addMinutes(5);
                    $student->save();

                    try {
                        Mail::to($student->email)->send(new OtpNotification($otp));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send OTP: ' . $e->getMessage());
                    }
                }
            }

            try {
                $token = JWTAuth::fromUser($student, ['guard' => 'student']);
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token'], 500);
            }

            return response()->json([
                'token' => $token,
                'user'  => [
                    'email'          => $student->email,
                    'name'           => $student->name,
                    'role'           => $student->role,
                    'email_verified' => $student->hasVerifiedEmail(),
                ],
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    /**
     * Get authenticated student info.
     */
    public function me(Request $request)
    {
        return response()->json(Auth::guard('student')->user());
    }

    /**
     * Logout a student.
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token not provided.'], 401);
        }

        try {
            TokenBlacklist($token);
            JWTAuth::setToken($token)->invalidate();

            return response()->json(['success' => true, 'message' => 'Logged out successfully.'], 200);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Error while processing token: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Change password for student.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $student = Auth::guard('student')->user();

        if (!Hash::check($request->current_password, $student->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        $student->password = Hash::make($request->new_password);
        $student->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Check JWT token validity for student.
     */
   public function checkToken(Request $request)
{
    $token = $request->bearerToken();

    if (!$token) {
        return response()->json(['message' => 'Token not provided.'], 400);
    }

    try {
        // Use student guard
        $student = auth('student')->setToken($token)->user();

        if (!$student) {
            return response()->json(['message' => 'Token is invalid or student not found.'], 401);
        }

        return response()->json([
            'message' => 'Token is valid.',
            'user'    => [
                'email'          => $student->email,
                'name'           => $student->name,
                'email_verified' => $student->hasVerifiedEmail(),
            ]
        ], 200);
    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json(['message' => 'Token has expired.'], 401);
    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return response()->json(['message' => 'Token is invalid.'], 401);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['message' => 'Token is missing or malformed.'], 401);
    }
}

}
