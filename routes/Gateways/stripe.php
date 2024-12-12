<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Gateway\Stripe\StripeController;

Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);


Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
Route::post('/stripe/confirm-payment-intent', [StripeController::class, 'confirmPaymentIntent']);
