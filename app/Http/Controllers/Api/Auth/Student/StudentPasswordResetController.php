<?php

namespace App\Http\Controllers\Api\Auth\Student;

use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;

class StudentPasswordResetController extends Controller
{
    /**
     * Send a password reset link to the student.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email|exists:students,email',
            'redirect_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email        = $request->input('email');
        $resetUrlBase = $request->input('redirect_url');

        // Find the student by email
        $student = Student::where('email', $email)->first();

        // Use the "students" password broker
        $response = Password::broker('students')->sendResetLink(
            ['email' => $email],
            function ($student, $token) use ($resetUrlBase) {
                // Create the full reset URL
                $resetUrl = "{$resetUrlBase}?token={$token}&email={$student->email}";

                // Send the email
                Mail::to($student->email)->send(new PasswordResetMail($student, $resetUrl));
            }
        );

        if ($response == Password::RESET_LINK_SENT) {
            return response()->json([
                'status'  => __($response),
                'student' => [
                    'name'  => $student->name,
                    'email' => $student->email
                ]
            ], 200);
        }

        return response()->json(['error' => __($response)], 400);
    }

    /**
     * Reset the student's password.
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'                 => 'required',
            'email'                 => 'required|email|exists:students,email',
            'password'              => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $response = Password::broker('students')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($student, $password) {
                $student->password = Hash::make($password);
                $student->setRememberToken(Str::random(60));
                $student->save();

                event(new PasswordReset($student));
            }
        );

        return $response == Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully.'])
            : response()->json(['error' => 'Unable to reset password.'], 500);
    }
}
