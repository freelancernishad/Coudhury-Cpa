<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Student\Course\CourseNoteController;
use App\Http\Controllers\Api\Student\Course\CoursePurchaseController;




Route::middleware(AuthenticateUser::class)->prefix('student')->group(function () {


    Route::post('/courses/purchase', [CoursePurchaseController::class, 'purchase']);



            Route::get('/course-purchases', [CoursePurchaseController::class, 'index']);
            Route::get('/course-purchases/{id}', [CoursePurchaseController::class, 'show']);
            Route::get('/course-purchases/{id}/payments', [CoursePurchaseController::class, 'payments']);
            Route::get('/all/payments/list', [CoursePurchaseController::class, 'studentPayments']);


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
