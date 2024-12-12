<?php

namespace App\Http\Controllers\Api\User\Package;

use App\Models\Coupon;
use App\Models\Package;
use Illuminate\Http\Request;
use App\Models\UserPackageAddon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class UserPackageController extends Controller
{
    /**
     * Get a list of packages with features and applicable discount based on duration (months).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get the list of all packages with features, discount rate, and discounted price
        $packages = Package::all()->makeHidden(['discounts']);

        // Return the list of packages with calculated discount details
        return response()->json($packages);
    }

    /**
     * Get a single package's details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the package by ID
        $package = Package::find($id)->makeHidden(['discounts']);

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Return the package details with calculated discount rate and discounted price
        return response()->json($package);
    }





    public function packagePurchase(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'currency' => 'nullable|string|in:USD,EUR,GBP', // Add other currencies if needed
            'payable_type' => 'required|string|in:Package', // Ensure payable type is Package (extend as necessary)
            'payable_id' => 'required|exists:packages,id', // Ensure the package exists
            'addon_ids' => 'nullable|array', // Addon IDs should be an array if present
            'addon_ids.*' => 'exists:package_addons,id', // Each addon ID should exist in package_addons table
            'coupon_id' => 'nullable|exists:coupons,id', // Ensure the coupon exists (if provided)
            'success_url' => 'nullable|url', // Success URL validation
            'cancel_url' => 'nullable|url', // Cancel URL validation
            'discount_months' => 'nullable|integer|min:1', // Discount months (e.g., 1 for monthly, 12 for yearly)
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Extract validated data
        $userId = auth()->id(); // Use authenticated user's ID
        $currency = $request->currency ?? 'USD'; // Default currency to USD
        $payableType = $request->payable_type;
        $payableId = $request->payable_id;
        $addonIds = $request->addon_ids ?? []; // Default to empty array if no addon IDs are provided
        $couponId = $request->coupon_id ?? null;
        $discountMonths = $request->discount_months ?? 0; // Default to 0 if no discount months are provided
        $successUrl = $request->success_url ?? 'http://localhost:8000/stripe/payment/success';
        $cancelUrl = $request->cancel_url ?? 'http://localhost:8000/stripe/payment/cancel';

        // Retrieve package and ensure it's valid
        $package = Package::find($payableId);
        if (!$package) {
            return response()->json(['error' => 'Package not found'], 404);
        }

        // Get the discounted price based on the provided duration (discount_months)
        $amount = $package->getDiscountedPriceAttribute($discountMonths); // Apply discount calculation

        // Ensure amount is greater than zero after discount
        if ($amount <= 0) {
            return response()->json(['error' => 'Payment amount must be greater than zero'], 400);
        }

        // Call createStripeCheckoutSession to handle the payment and UserPackageAddon creation
        try {
            $paymentResult = createStripeCheckoutSession([
                'user_id' => $userId,
                'amount' => $amount,
                'currency' => $currency,
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'addon_ids' => $addonIds,
                'coupon_id' => $couponId, // Pass coupon_id here
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            // Return success response
            return $paymentResult;

        } catch (\Exception $e) {
            return ['error' => 'Payment processing error: ' . $e->getMessage()];
        }
    }




}
