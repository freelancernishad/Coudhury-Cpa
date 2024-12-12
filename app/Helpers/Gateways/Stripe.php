<?php
use Stripe\Stripe;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PackageAddon;
use Stripe\Checkout\Session;
use App\Models\UserPackageAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

function createStripeCheckoutSession(array $data): JsonResponse
{
    $amount = $data['amount'] ?? 100;
    $currency = $data['currency'] ?? 'USD';
    $userId = $data['user_id'] ?? null;
    $couponId = $data['coupon_id'] ?? null;
    $payableType = $data['payable_type'] ?? null;
    $payableId = $data['payable_id'] ?? null;
    $addonIds = $data['addon_ids'] ?? []; // Addon IDs, if provided

    $baseSuccessUrl = $data['success_url'] ?? 'http://localhost:8000/stripe/payment/success';
    $baseCancelUrl = $data['cancel_url'] ?? 'http://localhost:8000/stripe/payment/cancel';

    $discount = 0;
    $finalAmount = $amount; // Start with the base amount

    // Handle coupon discount
    if ($couponId) {
        $coupon = Coupon::find($couponId);
        if ($coupon && $coupon->isValid()) {
            $discount = $coupon->getDiscountAmount($amount);
            $finalAmount -= $discount; // Subtract discount from the final amount
        } else {
            return response()->json(['error' => 'Invalid or expired coupon'], 400);
        }
    }

    if ($finalAmount <= 0) {
        return response()->json(['error' => 'Payment amount must be greater than zero'], 400);
    }

    // Create the payment record with the final amount (after discount)
    $payment = Payment::create([
        'user_id' => $userId,
        'gateway' => 'stripe',
        'amount' => $finalAmount, // Store the final amount after discount
        'currency' => $currency,
        'status' => 'pending',
        'transaction_id' => uniqid(),
        'payable_type' => $payableType,
        'payable_id' => $payableId,
        'coupon_id' => $couponId,
    ]);

    try {
        Stripe::setApiKey(config('STRIPE_SECRET'));

        // Success and Cancel URLs for Stripe Checkout session
        $successUrl = "{$baseSuccessUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl = "{$baseCancelUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";

        // Product details
        $productName = 'Payment';
        $lineItems = [];

        // If payable_type is a package, adjust product name
        if ($payableType === 'Package' && $payableId) {
            $payable = Package::find($payableId);
            if ($payable) {
                $productName = $payable->name;
                // Add base package price to line items
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $productName, // Product name for the package
                        ],
                        'unit_amount' => $finalAmount * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ];
            }
        }

        // If there are addons, add them as additional line items
        $addonTotal = 0; // To track the total addon price
        if (!empty($addonIds)) {
            foreach ($addonIds as $addonId) {
                $addon = PackageAddon::find($addonId);
                if ($addon) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => $addon->addon_name, // Product name for the addon
                            ],
                            'unit_amount' => $addon->price * 100, // Addon price in cents
                        ],
                        'quantity' => 1,
                    ];
                    $addonTotal += $addon->price; // Add the addon price to the total
                }
            }

            // Add the addon total to the final payment amount
            $finalAmount += $addonTotal;


            createUserPackageAddons($userId, $payableId, $addonIds, $payment->id);

        }







        // Update the payment record with the final amount (base amount + addons + discount)
        $payment->update(['amount' => $finalAmount]);

        // Create the Stripe session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        // Update the payment with the transaction ID (Stripe session ID)
        $payment->update(['transaction_id' => $session->id]);

        return response()->json(['session_url' => $session->url]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}



/**
 * Create user_package_addons for a user based on selected addons.
 *
 * @param int $userId
 * @param int $packageId
 * @param array $addonIds
 * @param int $purchaseId
 * @return void
 */
function createUserPackageAddons(int $userId, int $packageId, array $addonIds, int $purchaseId): void
{
    foreach ($addonIds as $addonId) {
        // Create a record in the user_package_addons table for each addon with the associated purchase ID
        UserPackageAddon::create([
            'user_id' => $userId,
            'package_id' => $packageId,
            'addon_id' => $addonId,
            'purchase_id' => $purchaseId,  // Link the purchase (payment) to the addon
        ]);
    }
}
