<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Coupon\CouponController;
use App\Http\Controllers\Api\User\Chat\ChatApiController;
use App\Http\Controllers\Api\Auth\User\AuthUserController;
use App\Http\Controllers\Api\Auth\User\VerificationController;
use App\Http\Controllers\Api\Admin\Chat\AdminChatApiController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\Auth\User\UserPasswordResetController;
use App\Http\Controllers\Api\Admin\Transitions\AdminPaymentController;
use App\Http\Controllers\Api\User\UserManagement\UserProfileController;
use App\Http\Controllers\Api\User\Package\UserPurchasedHistoryController;
use App\Http\Controllers\Api\User\SupportTicket\SupportTicketApiController;
use App\Http\Controllers\Api\User\SocialMedia\UserSocialMediaLinkController;
use App\Http\Controllers\Api\Admin\ServicePurchased\ServicePurchasedController;
use App\Http\Controllers\Api\Admin\SupportTicket\AdminSupportTicketApiController;
use App\Http\Controllers\Api\User\ServicePurchased\UserServicePurchasedController;
use App\Http\Controllers\Api\Admin\ServicePurchased\ServicePurchasedFileController;



Route::prefix('auth/user')->group(function () {
    Route::post('login', [AuthUserController::class, 'login'])->name('login');
    Route::post('register', [AuthUserController::class, 'register']);

    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user middleware
        Route::post('logout', [AuthUserController::class, 'logout']);
        Route::get('me', [AuthUserController::class, 'me']);
        Route::post('change-password', [AuthUserController::class, 'changePassword']);
        Route::get('check-token', [AuthUserController::class, 'checkToken']);
    });
});

Route::prefix('user')->group(function () {
    Route::middleware(AuthenticateUser::class)->group(function () {

////// auth routes

        Route::get('/matrix', [UserProfileController::class, 'getUserMatrix']);


        Route::get('/profile', [UserProfileController::class, 'getProfile']);
        Route::post('/profile', [UserProfileController::class, 'updateProfile']);



        Route::post('package/subscribe', [UserPackageController::class, 'packagePurchase']);

            // Get active packages
    Route::get('/active/package', [UserPurchasedHistoryController::class, 'activePackages']);
    // Get package history
    Route::get('/package/history', [UserPurchasedHistoryController::class, 'packageHistory']);



        // Support tickets
        Route::get('/support', [SupportTicketApiController::class, 'index']);
        Route::post('/support', [SupportTicketApiController::class, 'store']);
        Route::get('/support/{ticket}', [SupportTicketApiController::class, 'show']);
        Route::post('/support/{ticket}/reply', [AdminSupportTicketApiController::class, 'reply']);


        // Chat routes
        Route::get('/chats', [ChatApiController::class, 'index']); // Get all chats
        Route::post('/chats', [ChatApiController::class, 'store']); // Create a new chat
        Route::get('/chats/{chat}', [ChatApiController::class, 'show']); // View a specific chat
        Route::post('/chats/{chat}/send-message', [AdminChatApiController::class, 'sendMessage']); // Send a message in a chat



        Route::get('/packages/history', [UserPurchasedHistoryController::class, 'getPurchasedHistory']);
        Route::get('/packages/history/{id}', [UserPurchasedHistoryController::class, 'getSinglePurchasedHistory']);



        Route::post('service-purchased/checkout', [UserServicePurchasedController::class, 'createStripeCheckoutSession']);

        Route::prefix('/service-purchased')->group(function () {
            Route::get('/', [ServicePurchasedController::class, 'index']); // List all records
            Route::get('/{id}', [ServicePurchasedController::class, 'show']); // View a single record
            Route::post('/upload-file', [ServicePurchasedFileController::class, 'uploadFiles']);

        });


        Route::prefix('billings')->group(function () {
            Route::get('/billing-history', [AdminPaymentController::class, 'getAllTransactionHistory'])
                ->name('user.transitions.transaction-history');

            Route::get('/billing-single/{id}', [AdminPaymentController::class, 'getTransactionById']);
        });


        Route::get('/documents', [ServicePurchasedFileController::class, 'getFilesGroupedByFolder']);
        Route::delete('/documents', [ServicePurchasedFileController::class, 'deleteFile']);





    });

});


Route::prefix('social-media')->group(function () {
    // Get all social media links
    Route::get('links', [UserSocialMediaLinkController::class, 'index'])->name('socialMediaLinks.index');

    // Get a specific social media link
    Route::get('links/{id}', [UserSocialMediaLinkController::class, 'show'])->name('socialMediaLinks.show');
});

Route::prefix('coupons')->group(function () {
    Route::post('/apply', [CouponController::class, 'apply']);
    Route::post('/check', [CouponController::class, 'checkCoupon']);

});


// Password reset routes
Route::post('user/password/email', [UserPasswordResetController::class, 'sendResetLinkEmail']);
Route::post('user/password/reset', [UserPasswordResetController::class, 'reset']);



Route::post('/verify-otp', [VerificationController::class, 'verifyOtp']);
Route::post('/resend/otp', [VerificationController::class, 'resendOtp']);
Route::get('/email/verify/{hash}', [VerificationController::class, 'verifyEmail']);
Route::post('/resend/verification-link', [VerificationController::class, 'resendVerificationLink']);
