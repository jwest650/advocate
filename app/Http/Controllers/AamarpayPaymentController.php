<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class AamarpayPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'pay_status' => 'required|string',
            'mer_txnid' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['aamarpay_store_id'])) {
                return back()->withErrors(['error' => __('Aamarpay not configured')]);
            }

            if ($validated['pay_status'] === 'Successful') {
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'aamarpay',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['mer_txnid'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);
        } catch (\Exception $e) {
            return handlePaymentError($e, 'aamarpay');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['aamarpay_store_id']) || !isset($settings['payment_settings']['aamarpay_signature'])) {
                return response()->json(['error' => __('Aamarpay not configured')], 400);
            }

            $user = auth()->user();
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $currency = $settings['general_settings']['defaultCurrency'] ?? 'BDT';
            $mode = $settings['payment_settings']['aamarpay_mode'] ?? 'sandbox';
            $url = $mode === 'live' ? 'https://secure.aamarpay.com/request.php' : 'https://sandbox.aamarpay.com/request.php';

            $fields = [
                'store_id' => $settings['payment_settings']['aamarpay_store_id'],
                'amount' => $pricing['final_price'],
                'payment_type' => '',
                'currency' => $currency,
                'tran_id' => $orderID,
                'cus_name' => $user->name ?? 'Customer',
                'cus_email' => $user->email,
                'cus_add1' => '',
                'cus_add2' => '',
                'cus_city' => '',
                'cus_state' => '',
                'cus_postcode' => '',
                'cus_country' => '',
                'cus_phone' => '1234567890',
                'success_url' => route('aamarpay.success', [
                    'response' => 'success',
                    'coupon' => $validated['coupon_code'] ?? '',
                    'plan_id' => $plan->id,
                    'price' => $pricing['final_price'],
                    'order_id' => $orderID,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle']
                ]),
                'fail_url' => route('aamarpay.success', [
                    'response' => 'failure',
                    'coupon' => $validated['coupon_code'] ?? '',
                    'plan_id' => $plan->id,
                    'price' => $pricing['final_price'],
                    'order_id' => $orderID
                ]),
                'cancel_url' => route('aamarpay.success', ['response' => 'cancel']),
                'signature_key' => $settings['payment_settings']['aamarpay_signature'],
                'desc' => 'Plan: ' . $plan->name,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            \Log::info('AamarPay API Response', [
                'http_code' => $httpCode,
                'response' => $response,
                'fields' => $fields
            ]);

            if ($response && $httpCode == 200) {
                $url_forward = trim(str_replace(['"', "'"], '', stripslashes($response)));
                if (!empty($url_forward) && !str_contains($url_forward, 'error')) {
                    $baseUrl = $mode === 'live' ? 'https://secure.aamarpay.com/' : 'https://sandbox.aamarpay.com/';
                    $redirectUrl = str_starts_with($url_forward, 'http') ? $url_forward : $baseUrl . ltrim($url_forward, '/');
                    return redirect($redirectUrl);
                }
            }

            return response()->json(['error' => __('Payment creation failed')], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }



    public function success(Request $request)
    {
        try {
            \Log::info('AamarPay success callback received', $request->all());

            $response = $request->input('response');
            $planId = $request->input('plan_id');
            $userId = $request->input('user_id');
            $coupon = $request->input('coupon');
            $billingCycle = $request->input('billing_cycle', 'monthly');
            $orderId = $request->input('order_id');

            if ($response === 'success' && $planId && $userId) {
                $plan = Plan::find($planId);
                $user = User::find($userId);

                if ($plan && $user) {
                    \Log::info('Processing AamarPay payment success', [
                        'plan_id' => $plan->id,
                        'user_id' => $user->id,
                        'billing_cycle' => $billingCycle,
                        'order_id' => $orderId
                    ]);

                    processPaymentSuccess([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'payment_method' => 'aamarpay',
                        'coupon_code' => $coupon,
                        'payment_id' => $orderId,
                    ]);

                    // Log the user in if not already authenticated
                    if (!auth()->check()) {
                        auth()->login($user);
                    }

                    return redirect()->route('plans.index')->with('success', __('Payment completed successfully and plan activated'));
                }
            }

            \Log::warning('AamarPay payment failed or cancelled', [
                'response' => $response,
                'plan_id' => $planId,
                'user_id' => $userId
            ]);

            return redirect()->route('plans.index')->with('error', __('Payment failed or cancelled'));
        } catch (\Exception $e) {
            \Log::error('AamarPay payment processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('plans.index')->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('mer_txnid');
            $status = $request->input('pay_status');

            if ($transactionId && $status === 'Successful') {
                $parts = explode('_', $transactionId);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = Plan::find($planId);
                    $user = User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => 'monthly',
                            'payment_method' => 'aamarpay',
                            'payment_id' => $request->input('pg_txnid'),
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }

    public function createInvoicePayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $validated['invoice_token'])->firstOrFail();

            $paymentSettings = $invoice->getPaymentSettings('aamarpay');

            if (empty($paymentSettings['aamarpay_store_id']) || $paymentSettings['is_aamarpay_enabled'] !== '1') {
                return response()->json(['error' => 'AamarPay payment not configured'], 400);
            }

            $orderId = 'invoice_' . $invoice->id . '_' . time();
            $amount = number_format($validated['amount'], 2, '.', '');
            $mode = $paymentSettings['aamarpay_mode'] ?? 'sandbox';
            $url = $mode === 'live' ? 'https://secure.aamarpay.com/request.php' : 'https://sandbox.aamarpay.com/request.php';

            $successUrl = route('aamarpay.invoice.success', [
                'response' => 'success',
                'order_id' => $orderId,
                'invoice_token' => $validated['invoice_token'],
                'amount' => $amount
            ]);

            $failUrl = route('aamarpay.invoice.success', [
                'response' => 'failure',
                'order_id' => $orderId,
                'invoice_token' => $validated['invoice_token']
            ]);

            // , [
            //                     'response' => 'success',
            //                     'order_id' => $orderId,
            //                     'invoice_token' => $validated['invoice_token'],
            //                     'amount' => $amount
            //     ]

            $cancelUrl = route('aamarpay.invoice.success', [
                'response' => 'cancel',
                'invoice_token' => $validated['invoice_token']
            ]);

            // Get company currency settings
            $companySettings = \App\Models\Setting::where('user_id', $invoice->created_by)
                ->where('key', 'defaultCurrency')
                ->first();
            $currency = $companySettings ? $companySettings->value : 'BDT';

            $fields = [
                'store_id' => $paymentSettings['aamarpay_store_id'],
                'tran_id' => $orderId,
                'success_url' => route('aamarpay.invoice.success').'?response=success'.'&order_id='.$orderId.'&invoice_token='.$validated['invoice_token'].'&amount='.$amount,
                // 'success_url' => route('aamarpay.invoice.success').'?response=success'.'&order_id='.$orderId.'&invoice_token='.$validated['invoice_token'].'&amount='.$amount,
                'fail_url' => $failUrl,
                'cancel_url' => $cancelUrl,
                'amount' => $amount,
                'currency' => $currency,
                'signature_key' => $paymentSettings['aamarpay_signature'],
                'desc' => 'Invoice Payment - ' . $invoice->invoice_number,
                'cus_name' => $invoice->client->name ?? 'Customer',
                'cus_email' => $invoice->client->email ?? 'customer@example.com',
                'cus_add1' => $invoice->client->address ?? 'Address',
                'cus_city' => 'City',
                'cus_country' => 'Bangladesh',
                'cus_phone' => $invoice->client->phone ?? '01700000000'
            ];

            $fields_string = http_build_query($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $url_forward = str_replace('"', '', stripslashes($response));
            curl_close($ch);
           \Log::info($url_forward );

            if ($url_forward) {
                return $this->redirectToMerchant($url_forward);
            }

            return response()->json(['error' => __('Payment creation failed')], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }
    private function redirectToMerchant($url)
    {

        $token = csrf_token();
        $redirectUrl = 'https://sandbox.aamarpay.com/' . $url;

        return response(view('aamarpay-redirect', compact('redirectUrl', 'token')));
    }

    public function invoiceSuccess(Request $request)
    {
        dd(19);
        try {
            \Log::info('AamarPay invoice success callback received', $request->all());

            $response = $request->input('response');
            $invoiceToken = $request->input('invoice_token');
            $amount = $request->input('amount');
            $orderId = $request->input('order_id');

            if ($response === 'success' && $invoiceToken && $amount) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    \Log::info('Processing AamarPay invoice payment', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'order_id' => $orderId
                    ]);

                    $invoice->createPaymentRecord($amount, 'aamarpay', $orderId ?: 'aamarpay_' . time());
                    return redirect()->route('invoice.payment', $invoiceToken)
                        ->with('success', __('Payment successful!'));
                }
            }

            \Log::warning('AamarPay invoice payment failed', [
                'response' => $response,
                'invoice_token' => $invoiceToken,
                'amount' => $amount
            ]);


            return redirect()->route('invoice.payment', $invoiceToken ?: 'invalid')
                ->withErrors(['error' => __('Payment failed or cancelled')]);
        } catch (\Exception $e) {
            \Log::error('AamarPay invoice payment processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_token' => $request->input('invoice_token')
            ]);

            return redirect()->route('invoice.payment', $request->input('invoice_token', 'invalid'))
                ->withErrors(['error' => 'Payment processing failed']);
        }
    }

    public function invoiceCallback(Request $request)
    {
        try {
            \Log::info('AamarPay invoice callback received', $request->all());

            $transactionId = $request->input('mer_txnid');
            $status = $request->input('pay_status');
            $amount = $request->input('amount');

            if ($transactionId && $status === 'Successful') {
                // Extract invoice info from transaction ID
                if (str_contains($transactionId, 'invoice_')) {
                    $parts = explode('_', $transactionId);
                    if (count($parts) >= 2) {
                        $invoiceId = $parts[1];
                        $invoice = \App\Models\Invoice::find($invoiceId);

                        if ($invoice && $amount) {
                            $invoice->createPaymentRecord($amount, 'aamarpay', $transactionId);
                        }
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('AamarPay invoice callback error', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }
}
