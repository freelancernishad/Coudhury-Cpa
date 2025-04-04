<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Server\ServerStatusController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\Admin\Services\AdminServicesController;
use App\Http\Controllers\Api\User\UserManagement\UserProfileController;
use App\Http\Controllers\Api\User\PackageAddon\UserPackageAddonController;
use App\Http\Controllers\Api\User\ServicePurchased\ServicePurchasedDuePaymentController;

// Load users and admins route files
if (file_exists($userRoutes = __DIR__.'/example.php')) {
    require $userRoutes;
}


if (file_exists($userRoutes = __DIR__.'/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/admins.php')) {
    require $adminRoutes;
}

if (file_exists($stripeRoutes = __DIR__.'/Gateways/stripe.php')) {
    require $stripeRoutes;
}



// Route::get('/updateClientIds', [UserProfileController::class, 'updateClientIds']);

Route::get('/server-status', [ServerStatusController::class, 'checkStatus']);






// Route to get all packages with discounts (query params for discount_months)
Route::get('global/packages', [UserPackageController::class, 'index']);

// Route to get a single package by ID with discounts
Route::get('global/package/{id}', [UserPackageController::class, 'show']);

Route::prefix('global/')->group(function () {
    Route::get('package-addons/', [UserPackageAddonController::class, 'index']); // List all addons
    Route::get('package-addons/{id}', [UserPackageAddonController::class, 'show']); // Get a specific addon
    Route::get('services', [AdminServicesController::class, 'index']);


    Route::get('get/all/services', [AdminServicesController::class, 'allservices']);


    // Route to get ServicePurchased list with due amounts
    Route::get('/service-purchased/due-list', [ServicePurchasedDuePaymentController::class, 'getServicePurchasedList']);

    // Route to create a payment for a specific ServicePurchased record
    Route::post('/service-purchased/create-payment', [ServicePurchasedDuePaymentController::class, 'createPayment']);




});



