<?php

namespace App\Http\Controllers\Api\Auth\Student;

use App\Models\Student;
use App\Mail\VerifyEmail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\OtpNotification;
use Illuminate\Routing\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\RegistrationSuccessful;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function verifyEmail(Request $request, $hash)
    {
        $student = Student::where('email_verification_hash', $hash)->first();

        if (!$student) {
            return response()->json(['error' => 'Invalid or expired verification link.'], 400);
        }

        if ($student->hasVerifiedEmail()) {
            $token = JWTAuth::fromUser($student);

            return response()->json([
                'message' => 'Email already verified.',
                'student' => [
                    'email'           => $student->email,
                    'name'            => $student->name,
                    'username'        => $student->username,
                    'step'            => $student->step,
                    'email_verified'  => true,
                ],
                'token' => $token
            ], 200);
        }

        $student->markEmailAsVerified();
        $token = JWTAuth::fromUser($student);

        return response()->json([
            'message' => 'Email verified successfully.',
            'student' => [
                'email'           => $student->email,
                'name'            => $student->name,
                'username'        => $student->username,
                'step'            => $student->step,
                'email_verified'  => true,
            ],
            'token' => $token
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:students,email',
            'otp'   => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $student = Student::where('email', $request->email)->first();

        if ($student->otp_expires_at < now()) {
            return response()->json(['error' => 'OTP has expired'], 400);
        }

        if (Hash::check($request->otp, $student->otp)) {
            if ($student->hasVerifiedEmail()) {
                $token = JWTAuth::fromUser($student);

                return response()->json([
                    'message' => 'Email already verified.',
                    'student' => [
                        'email'           => $student->email,
                        'name'            => $student->name,
                        'username'        => $student->username,
                        'step'            => $student->step,
                        'email_verified'  => true,
                    ],
                    'token' => $token
                ], 200);
            }

            $student->markEmailAsVerified();
            $student->otp = null;
            $student->otp_expires_at = null;
            $student->save();

            $token = JWTAuth::fromUser($student);

            // Optionally send registration success email
            // Mail::to($student->email)->send(new RegistrationSuccessful(['name' => $student->name]));

            return response()->json([
                'message' => 'Email verified successfully.',
                'student' => [
                    'email'           => $student->email,
                    'name'            => $student->name,
                    'username'        => $student->username,
                    'step'            => $student->step,
                    'email_verified'  => true,
                ],
                'token' => $token
            ], 200);
        }

        return response()->json(['error' => 'Invalid OTP'], 400);
    }

    public function resendVerificationLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'      => 'required|email|exists:students,email',
            'verify_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::where('email', $request->email)->first();

        if (!$student || $student->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is either already verified or student does not exist.'], 400);
        }

        $verificationToken = Str::random(60);
        $student->email_verification_hash = $verificationToken;
        $student->save();

        $verify_url = $request->verify_url;

        Mail::to($student->email)->send(new VerifyEmail($student, $verify_url));

        return response()->json(['message' => 'Verification link has been sent.'], 200);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:students,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::where('email', $request->email)->first();

        if (!$student || $student->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is either already verified or student does not exist.'], 400);
        }

        $otp = random_int(100000, 999999);
        $student->otp = Hash::make($otp);
        $student->otp_expires_at = now()->addMinutes(5);
        $student->save();

        Mail::to($student->email)->send(new OtpNotification($otp));

        return response()->json(['message' => 'A new OTP has been sent to your email.'], 200);
    }
}
