<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Middleware\AuthenticateStudent;
use App\Http\Controllers\Api\Auth\Student\AuthStudentController;
use App\Http\Controllers\Api\Auth\Student\VerificationController;
use App\Http\Controllers\Api\Student\Course\CourseNoteController;
use App\Http\Controllers\Api\StudentV2\V2CourseContentController;
use App\Http\Controllers\Api\Admin\Student\CourseContentController;
use App\Http\Controllers\Api\Student\Course\CoursePurchaseController;
use App\Http\Controllers\Api\StudentV2\Course\V2CourseNoteController;
use App\Http\Controllers\Api\Auth\Student\StudentPasswordResetController;
use App\Http\Controllers\Api\StudentV2\Course\V2CoursePurchaseController;



Route::prefix('auth/student')->group(function () {
    Route::post('login', [AuthStudentController::class, 'login'])->name('login');
    Route::post('register', [AuthStudentController::class, 'register']);

    Route::middleware(AuthenticateStudent::class)->group(function () { // Applying user middleware
        Route::post('logout', [AuthStudentController::class, 'logout']);
        Route::get('me', [AuthStudentController::class, 'me']);
        Route::post('change-password', [AuthStudentController::class, 'changePassword']);
        Route::get('check-token', [AuthStudentController::class, 'checkToken']);
    });
});

// Password reset routes
Route::post('student/password/email', [StudentPasswordResetController::class, 'sendResetLinkEmail']);
Route::post('student/password/reset', [StudentPasswordResetController::class, 'reset']);



Route::post('student/verify-otp', [VerificationController::class, 'verifyOtp']);
Route::post('student/resend/otp', [VerificationController::class, 'resendOtp']);
Route::get('student/email/verify/{hash}', [VerificationController::class, 'verifyEmail']);
Route::post('student/resend/verification-link', [VerificationController::class, 'resendVerificationLink']);



Route::middleware(AuthenticateUser::class)->prefix('student')->group(function () {
    Route::post('/courses/purchase', [CoursePurchaseController::class, 'purchase']);

            Route::get('/course-purchases', [CoursePurchaseController::class, 'index']);
            Route::get('/course-purchases/{id}', [CoursePurchaseController::class, 'show']);
            Route::get('/course-purchases/{id}/payments', [CoursePurchaseController::class, 'payments']);
            Route::get('/get/all/payments/list', [CoursePurchaseController::class, 'studentPayments']);


            Route::prefix('/course/notes')->group(function () {
                Route::get('/{course_purchase_id}', [CourseNoteController::class, 'index']); // all notes
                Route::post('/', [CourseNoteController::class, 'store']); // create
                Route::get('/note/{id}', [CourseNoteController::class, 'show']); // single
                Route::post('/note/{id}', [CourseNoteController::class, 'update']); // update
                Route::delete('/note/{id}', [CourseNoteController::class, 'destroy']); // delete
            });



});

    Route::get('/courses/list', [CoursePurchaseController::class, 'getAllCourses']);
    Route::get('/courses/detials/{id}', [CoursePurchaseController::class, 'showCourse']);
    Route::post('/courses/webhook', [CoursePurchaseController::class, 'webhook']);

    Route::get('course-contents/{course_id}', [CourseContentController::class, 'index']);







    Route::prefix('v2')->group(function () {

        Route::middleware(AuthenticateStudent::class)->prefix('student')->group(function () {
            Route::post('/courses/purchase', [V2CoursePurchaseController::class, 'purchase']);

            Route::get('/course-purchases', [V2CoursePurchaseController::class, 'index']);
            Route::get('/course-purchases/{id}', [V2CoursePurchaseController::class, 'show']);
            Route::get('/course-purchases/{id}/payments', [V2CoursePurchaseController::class, 'payments']);
            Route::get('/get/all/payments/list', [V2CoursePurchaseController::class, 'studentPayments']);

            Route::prefix('/course/notes')->group(function () {
                Route::get('/{course_purchase_id}', [V2CourseNoteController::class, 'index']); // all notes
                Route::post('/', [V2CourseNoteController::class, 'store']); // create
                Route::get('/note/{id}', [V2CourseNoteController::class, 'show']); // single
                Route::post('/note/{id}', [V2CourseNoteController::class, 'update']); // update
                Route::delete('/note/{id}', [V2CourseNoteController::class, 'destroy']); // delete
            });

        });

        Route::get('/courses/list', [V2CoursePurchaseController::class, 'getAllCourses']);
        Route::get('/courses/detials/{id}', [V2CoursePurchaseController::class, 'showCourse']);
        Route::post('/courses/webhook', [CoursePurchaseController::class, 'webhook']);

        Route::get('course-contents/{course_id}', [V2CourseContentController::class, 'index']);

    });
