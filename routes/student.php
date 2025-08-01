<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Student\Course\CoursePurchaseController;




Route::middleware(AuthenticateUser::class)->prefix('student')->group(function () {
    Route::post('/courses/purchase', [CoursePurchaseController::class, 'purchase']);



            Route::get('/course-purchases', [CoursePurchaseController::class, 'index']);
            Route::get('/course-purchases/{id}', [CoursePurchaseController::class, 'show']);
            Route::get('/course-purchases/{id}/payments', [CoursePurchaseController::class, 'payments']);



});

Route::post('/courses/webhook', [CoursePurchaseController::class, 'webhook']);
