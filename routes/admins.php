<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Api\AllowedOriginController;
use App\Http\Controllers\Api\Coupon\CouponController;
use App\Http\Controllers\Api\Admin\Users\UserController;
use App\Http\Controllers\Api\Auth\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\Chat\AdminChatApiController;
use App\Http\Controllers\Api\Admin\Package\AdminPackageController;
use App\Http\Controllers\Api\Admin\Services\AdminServicesController;
use App\Http\Controllers\Api\SystemSettings\SystemSettingController;
use App\Http\Controllers\Api\Admin\Blogs\Articles\ArticlesController;
use App\Http\Controllers\Api\Admin\Blogs\Category\CategoryController;
use App\Http\Controllers\Api\Auth\Admin\AdminResetPasswordController;
use App\Http\Controllers\Api\Admin\Transitions\AdminPaymentController;
use App\Http\Controllers\Api\Admin\Package\AdminPurchasedHistoryController;
use App\Http\Controllers\Api\Admin\PackageAddon\AdminPackageAddonController;
use App\Http\Controllers\Api\Admin\DashboardMetrics\AdminDashboardController;
use App\Http\Controllers\Api\Admin\SocialMedia\AdminSocialMediaLinkController;
use App\Http\Controllers\Api\Admin\ServicePurchased\ServicePurchasedController;
use App\Http\Controllers\Api\Admin\SupportTicket\AdminSupportTicketApiController;

Route::prefix('auth/admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login');
    Route::post('register', [AdminAuthController::class, 'register']);

    Route::middleware(AuthenticateAdmin::class)->group(function () { // Applying admin middleware
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
        Route::get('check-token', [AdminAuthController::class, 'checkToken']);

    });
});

Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () { // Applying admin middleware
        Route::post('/system-setting', [SystemSettingController::class, 'storeOrUpdate']);
        Route::get('/allowed-origins', [AllowedOriginController::class, 'index']);
        Route::post('/allowed-origins', [AllowedOriginController::class, 'store']);
        Route::put('/allowed-origins/{id}', [AllowedOriginController::class, 'update']);
        Route::delete('/allowed-origins/{id}', [AllowedOriginController::class, 'destroy']);




        // Dashboard
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);          // List users
            Route::post('/', [UserController::class, 'store']);         // Create user
            Route::get('/{user}', [UserController::class, 'show']);     // Show user details
            Route::put('/{user}', [UserController::class, 'update']);   // Update user
            Route::delete('/{user}', [UserController::class, 'destroy']); // Delete user
        });

        Route::prefix('coupons')->group(function () {
            Route::get('/', [CouponController::class, 'index']);
            Route::post('/', [CouponController::class, 'store']);
            Route::post('/{id}', [CouponController::class, 'update']);
            Route::delete('/{id}', [CouponController::class, 'destroy']);
        });

        Route::prefix('transitions')->group(function () {
            Route::get('/transaction-history', [AdminPaymentController::class, 'getAllTransactionHistory'])
                ->name('admin.transitions.transaction-history');
        });


        Route::prefix('social-media')->group(function () {
            Route::get('links', [AdminSocialMediaLinkController::class, 'index'])->name('admin.socialMediaLinks.index');
            Route::get('links/{id}', [AdminSocialMediaLinkController::class, 'show'])->name('admin.socialMediaLinks.show');
            Route::post('links', [AdminSocialMediaLinkController::class, 'store'])->name('admin.socialMediaLinks.store');
            Route::post('links/{id}', [AdminSocialMediaLinkController::class, 'update'])->name('admin.socialMediaLinks.update');
            Route::delete('links/{id}', [AdminSocialMediaLinkController::class, 'destroy'])->name('admin.socialMediaLinks.destroy');
            Route::patch('links/{id}/toggle-status', [AdminSocialMediaLinkController::class, 'toggleStatus']);
            Route::patch('links/{id}/update-index-no', [AdminSocialMediaLinkController::class, 'updateIndexNo']);
        });

        Route::prefix('/')->group(function () {
            Route::get('packages', [AdminPackageController::class, 'index']);
            Route::get('packages/{id}', [AdminPackageController::class, 'show']);
            Route::post('packages', [AdminPackageController::class, 'store']);
            Route::put('packages/{id}', [AdminPackageController::class, 'update']);
            Route::delete('packages/{id}', [AdminPackageController::class, 'destroy']);
        });


        Route::prefix('/')->group(function () {
            Route::get('package-addons/', [AdminPackageAddonController::class, 'index']); // List all addons
            Route::post('package-addons/', [AdminPackageAddonController::class, 'store']); // Create a new addon
            Route::get('package-addons/{id}', [AdminPackageAddonController::class, 'show']); // Get a specific addon
            Route::put('package-addons/{id}', [AdminPackageAddonController::class, 'update']); // Update an addon
            Route::delete('package-addons/{id}', [AdminPackageAddonController::class, 'destroy']); // Delete an addon
        });


        // Support ticket routes
        Route::get('/support', [AdminSupportTicketApiController::class, 'index']);
        Route::get('/support/{ticket}', [AdminSupportTicketApiController::class, 'show']);
        Route::post('/support/{ticket}/reply', [AdminSupportTicketApiController::class, 'reply']);
        Route::patch('/support/{ticket}/status', [AdminSupportTicketApiController::class, 'updateStatus']);



        // Chat routes
        Route::get('/chats', [AdminChatApiController::class, 'index']); // Get all chats
        Route::get('/chats/{chat}', [AdminChatApiController::class, 'show']); // View a specific chat
        Route::post('/chats/{chat}/send-message', [AdminChatApiController::class, 'sendMessage']); // Send a message in a chat
        Route::patch('/chats/{chat}/status', [AdminChatApiController::class, 'updateStatus']); // Update chat status





        Route::get('/package/purchased-history', [AdminPurchasedHistoryController::class, 'getAllHistory']);
        Route::get('/package/purchased-history/{id}', [AdminPurchasedHistoryController::class, 'getSingleHistory']);






    // Admin routes for blog categories
    Route::group(['prefix' => 'blogs/categories',], function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
        Route::get('/all/list', [CategoryController::class, 'list']);
        Route::put('/reassign-update/{id}', [CategoryController::class, 'reassignAndUpdateParent']);
    });



    Route::prefix('blogs/articles')->group(function () {
        Route::get('/', [ArticlesController::class, 'index']);
        Route::post('/', [ArticlesController::class, 'store']);
        Route::get('{id}', [ArticlesController::class, 'show']);
        Route::post('{id}', [ArticlesController::class, 'update']);
        Route::delete('{id}', [ArticlesController::class, 'destroy']);

        // Add or remove categories to/from articles
        Route::post('{id}/add-category', [ArticlesController::class, 'addCategory']);
        Route::post('{id}/remove-category', [ArticlesController::class, 'removeCategory']);

        Route::get('/by-category/with-child-articles', [ArticlesController::class, 'getArticlesByCategory']);

    });



    // Admin routes for services
    Route::group(['prefix' => 'services'], function () {
        Route::get('/', [AdminServicesController::class, 'index']);
        Route::post('/', [AdminServicesController::class, 'store']);
        Route::get('/{id}', [AdminServicesController::class, 'show']);
        Route::put('/{id}', [AdminServicesController::class, 'update']);
        Route::delete('/{id}', [AdminServicesController::class, 'destroy']);
        Route::get('/all/list', [AdminServicesController::class, 'list']);
        Route::put('/reassign-update/{id}', [AdminServicesController::class, 'reassignAndUpdateParent']);
    });


    Route::prefix('/service-purchased')->group(function () {
        Route::get('/', [ServicePurchasedController::class, 'index']); // List all records
        Route::get('/{id}', [ServicePurchasedController::class, 'show']); // View a single record
        Route::delete('/{id}', [ServicePurchasedController::class, 'destroy']); // Delete a record
    });






    });
});



