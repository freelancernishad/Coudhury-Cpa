<?php
use Stripe\Stripe;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PackageAddon;
use Stripe\Checkout\Session;
use App\Models\StripeCustomer;
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
    $addonIds = $data['addon_ids'] ?? [];
    $billingInterval = $data['billing_interval'] ?? 'one_time'; // Default to one-time payment
    $baseSuccessUrl = $data['success_url'] ?? 'http://localhost:8000/stripe/payment/success';
    $baseCancelUrl = $data['cancel_url'] ?? 'http://localhost:8000/stripe/payment/cancel';

    $discount = 0;
    $finalAmount = $amount;

    // Handle coupon discount
    if ($couponId) {
        $coupon = Coupon::find($couponId);
        if ($coupon && $coupon->isValid()) {
            $discount = $coupon->getDiscountAmount($amount);
            $finalAmount -= $discount;
        } else {
            return response()->json(['error' => 'Invalid or expired coupon'], 400);
        }
    }

    if ($finalAmount <= 0) {
        return response()->json(['error' => 'Payment amount must be greater than zero'], 400);
    }

    // Create the payment record
    $payment = Payment::create([
        'user_id' => $userId,
        'gateway' => 'stripe',
        'amount' => $finalAmount,
        'currency' => $currency,
        'status' => 'pending',
        'transaction_id' => uniqid(),
        'payable_type' => $payableType,
        'payable_id' => $payableId,
        'coupon_id' => $couponId,
        'billing_interval' => $billingInterval, // Store the billing interval
    ]);

    try {
        Stripe::setApiKey(config('STRIPE_SECRET'));

        // Success and Cancel URLs
        $successUrl = "{$baseSuccessUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl = "{$baseCancelUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";

        // Check if the user already has a Stripe Customer ID
        $stripeCustomerId = null;
        $stripeCustomer = StripeCustomer::where('user_id', $userId)->first();

        if ($stripeCustomer) {
            // Use existing Stripe Customer ID
            $stripeCustomerId = $stripeCustomer->stripe_customer_id;
        } else {
            // Create a new Stripe Customer
            $customer = \Stripe\Customer::create([
                'email' => auth()->user()->email, // Use the authenticated user's email
                'name' => auth()->user()->name, // Use the authenticated user's name
                'metadata' => [
                    'user_id' => $userId, // Store your internal user ID in metadata
                ],
            ]);

            // Save the Stripe Customer ID to your database
            StripeCustomer::create([
                'user_id' => $userId,
                'stripe_customer_id' => $customer->id,
            ]);

            $stripeCustomerId = $customer->id;
        }

        // Product details
        $productName = 'Payment';
        $lineItems = [];

        // If payable_type is a package, adjust product name
        if ($payableType === 'Package' && $payableId) {
            $payable = Package::find($payableId);
            if ($payable) {
                $productName = $payable->name;
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $productName,
                        ],
                        'unit_amount' => $finalAmount * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ];
            }
        } else {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'tax payment',
                    ],
                    'unit_amount' => $finalAmount * 100, // Amount in cents
                ],
                'quantity' => 1,
            ];
        }

        // Handle addons
        $addonTotal = 0;
        if (!empty($addonIds)) {
            foreach ($addonIds as $addonId) {
                $addon = PackageAddon::find($addonId);
                if ($addon) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => $addon->addon_name,
                            ],
                            'unit_amount' => $addon->price * 100, // Addon price in cents
                        ],
                        'quantity' => 1,
                    ];
                    $addonTotal += $addon->price;
                }
            }

            $finalAmount += $addonTotal;
            createUserPackageAddons($userId, $payableId, $addonIds, $payment->id);
        }

        // Update the payment record with the final amount
        $payment->update(['amount' => $finalAmount]);

        // Create Stripe session based on billing interval
        if ($billingInterval === 'one_time') {
            // One-time payment
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer' => $stripeCustomerId, // Associate the session with the Stripe Customer
            ]);
        } else {
            // Recurring payment (monthly or yearly)
            $stripeProduct = \Stripe\Product::create([
                'name' => $productName,
            ]);

            $stripePrice = \Stripe\Price::create([
                'product' => $stripeProduct->id,
                'unit_amount' => $finalAmount * 100,
                'currency' => $currency,
                'recurring' => [
                    'interval' => $billingInterval, // 'month' or 'year'
                ],
            ]);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
                'line_items' => [
                    [
                        'price' => $stripePrice->id,
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer' => $stripeCustomerId, // Associate the session with the Stripe Customer
            ]);
        }

        // Update the payment with the transaction ID and Stripe session ID
        $payment->update([
            'transaction_id' => uniqid(),
            'stripe_session' => $session->id, // Add this line
        ]);

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
