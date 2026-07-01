<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class TapPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['tap_secret_key'])) {
                return response()->json(['error' => __('Tap not configured')], 400);
            }

            $user = auth()->user();
            $transactionId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();
            $baseUrl = config('app.url');

            // Initialize Tap Payment library
            require_once app_path('Libraries/Tap/Tap.php');
            require_once app_path('Libraries/Tap/Reference.php');
            require_once app_path('Libraries/Tap/Payment.php');

            $tap = new \App\Package\Payment([
                'company_tap_secret_key' => $settings['payment_settings']['tap_secret_key']
            ]);

            $chargeData = [
                'amount' => $pricing['final_price'],
                'currency' => 'USD',
                'threeDSecure' => true,
                'description' => 'Plan: ' . $plan->name . ' - ' . ucfirst($validated['billing_cycle']),
                'statement_descriptor' => 'Plan Subscription',
                'customer' => [
                    'first_name' => $user->name ?? 'Customer',
                    'email' => $user->email,
                ],
                'source' => ['id' => 'src_card'],
                'post' => ['url' => $baseUrl . '/payments/tap/callback'],
                'redirect' => ['url' => $baseUrl . '/payments/tap/success?' . http_build_query([
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? ''
                ])]
            ];

            $result = $tap->charge($chargeData, false);

            if (isset($result->transaction->url)) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $result->transaction->url,
                    'charge_id' => $result->id
                ]);
            }

            return response()->json(['error' => __('Payment creation failed')], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $chargeId = $request->input('tap_id');
            $planId = $request->input('plan_id');
            $userId = $request->input('user_id');
            $billingCycle = $request->input('billing_cycle', 'monthly');
            $couponCode = $request->input('coupon_code');

            if ($chargeId && $planId && $userId) {
                $plan = Plan::find($planId);
                $user = User::find($userId);

                if ($plan && $user) {
                    processPaymentSuccess([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'payment_method' => 'tap',
                        'coupon_code' => $couponCode,
                        'payment_id' => $chargeId,
                    ]);

                    if (!auth()->check()) {
                        auth()->login($user);
                    }

                    return redirect()->route('plans.index')->with('success', __('Payment completed successfully and plan activated'));
                }
            }

            return redirect()->route('plans.index')->with('error', __('Payment verification failed'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment processing failed: ' . $e->getMessage()));
        }
    }

    public function callback(Request $request)
    {
        try {
            $chargeId = $request->input('tap_id');
            $status = $request->input('status');
            return response('OK', 200);
        } catch (\Exception $e) {
            return response('Error', 500);
        }
    }

    public function createInvoicePayment(Request $request)
    {
        try {
            $request->validate([
                'invoice_token' => 'required|string',
                'amount' => 'required|numeric|min:0.01'
            ]);

            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            // Validate amount doesn't exceed remaining balance
            if ($request->amount > $invoice->remaining_amount) {
                return response()->json(['error' => 'Amount exceeds remaining balance'], 400);
            }

            $paymentSettings = PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['tap_secret_key', 'is_tap_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['tap_secret_key'])) {
                return response()->json(['error' => 'Tap API key not configured'], 400);
            }

            if (($paymentSettings['is_tap_enabled'] ?? '0') !== '1') {
                return response()->json(['error' => 'Tap payment not enabled'], 400);
            }

            $tapPath = app_path('Libraries/Tap/Tap.php');
            $referencePath = app_path('Libraries/Tap/Reference.php');
            $paymentPath = app_path('Libraries/Tap/Payment.php');

            if (!file_exists($tapPath) || !file_exists($referencePath) || !file_exists($paymentPath)) {
                return response()->json(['error' => 'Tap library not configured'], 500);
            }

            require_once $tapPath;
            require_once $referencePath;
            require_once $paymentPath;

            $tap = new \App\Package\Payment([
                'company_tap_secret_key' => $paymentSettings['tap_secret_key']
            ]);

            $chargeData = [
                'amount' => $request->amount,
                'currency' => 'USD',
                'threeDSecure' => true,
                'description' => 'Invoice Payment - #' . $invoice->invoice_number,
                'statement_descriptor' => 'Invoice Payment',
                'customer' => [
                    'first_name' => $invoice->client->name ?? 'Customer',
                    'email' => $invoice->client->email ?? 'customer@example.com',
                ],
                'source' => ['id' => 'src_card'],
                'post' => ['url' => route('tap.invoice.callback')],
                'redirect' => ['url' => route('tap.invoice.success', $request->invoice_token) . '?amount=' . $request->amount]
            ];

            $result = $tap->charge($chargeData, false);

            if (isset($result->transaction->url)) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $result->transaction->url
                ]);
            }

            return response()->json(['error' => 'Payment creation failed'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function invoiceSuccess(Request $request, $token)
    {
        try {
            $chargeId = $request->input('tap_id');
            $amount = $request->input('amount');
            $invoice = Invoice::where('payment_token', $token)->firstOrFail();

            if ($chargeId) {
                // Use the amount from URL parameter if available, otherwise try to get from Tap API
                if ($amount && is_numeric($amount)) {
                    $actualAmount = floatval($amount);
                } else {
                    // Get the actual payment amount from Tap API
                    $paymentSettings = PaymentSetting::where('user_id', $invoice->created_by)
                        ->where('key', 'tap_secret_key')
                        ->value('value');
                    
                    if ($paymentSettings) {
                        require_once app_path('Libraries/Tap/Tap.php');
                        require_once app_path('Libraries/Tap/Reference.php');
                        require_once app_path('Libraries/Tap/Payment.php');
                        
                        $tap = new \App\Package\Payment([
                            'company_tap_secret_key' => $paymentSettings
                        ]);
                        
                        // Retrieve charge details to get actual amount
                        $chargeDetails = $tap->retrieve($chargeId);
                        $actualAmount = $chargeDetails->amount ?? $invoice->remaining_amount;
                    } else {
                        // Fallback to remaining amount if API call fails
                        $actualAmount = $invoice->remaining_amount;
                    }
                }
                
                $invoice->createPaymentRecord($actualAmount, 'tap', $chargeId);
            }

            return redirect()->route('invoice.payment', $token)
                ->with('success', 'Payment successful');
        } catch (\Exception $e) {
            return redirect()->route('invoice.payment', $token)
                ->with('error', 'Payment verification failed.');
        }
    }

    public function invoiceCallback(Request $request)
    {
        return response('OK', 200);
    }

    public function processInvoicePayment(Request $request)
    {
        try {
            $request->validate([
                'invoice_token' => 'required|string',
                'amount' => 'required|numeric|min:0.01'
            ]);

            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $paymentSettings = PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['tap_secret_key', 'is_tap_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['tap_secret_key']) || ($paymentSettings['is_tap_enabled'] ?? '0') !== '1') {
                return back()->withErrors(['error' => 'Tap payment not configured']);
            }

            require_once app_path('Libraries/Tap/Tap.php');
            require_once app_path('Libraries/Tap/Reference.php');
            require_once app_path('Libraries/Tap/Payment.php');

            $tap = new \App\Package\Payment([
                'company_tap_secret_key' => $paymentSettings['tap_secret_key']
            ]);

            $chargeData = [
                'amount' => $request->amount,
                'currency' => 'USD',
                'threeDSecure' => true,
                'description' => 'Invoice Payment - #' . $invoice->invoice_number,
                'statement_descriptor' => 'Invoice Payment',
                'customer' => [
                    'first_name' => $invoice->client->name ?? 'Customer',
                    'email' => $invoice->client->email ?? 'customer@example.com',
                ],
                'source' => ['id' => 'src_card'],
                'post' => ['url' => route('tap.invoice.callback')],
                'redirect' => ['url' => route('tap.invoice.success', $request->invoice_token) . '?amount=' . $request->amount]
            ];

            $result = $tap->charge($chargeData, false);

            if (isset($result->transaction->url)) {
                return redirect($result->transaction->url);
            }

            return back()->withErrors(['error' => 'Payment creation failed']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
