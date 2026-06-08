<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PaymentWallPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
                'billing_cycle' => 'required|in:monthly,yearly',
                'coupon_code' => 'nullable|string',
                'brick_token' => 'required|string',
                'brick_fingerprint' => 'required|string',
            ]);

            $plan = Plan::findOrFail($validated['plan_id']);
                $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['paymentwall_private_key'])) {
                return back()->withErrors(['error' => __('PaymentWall not configured')]);
            }

            $user = auth()->user();
            $currency = $settings['general_settings']['currency'] ?? 'USD';
            $isTestMode = ($settings['payment_settings']['paymentwall_mode'] ?? 'sandbox') === 'sandbox';

            // Prepare charge data for PaymentWall Brick API
            $chargeData = [
                'token' => $validated['brick_token'],
                'fingerprint' => $validated['brick_fingerprint'],
                'amount' => $pricing['final_price'],
                'currency' => $currency,
                'email' => $user->email,
                'history[registration_date]' => $user->created_at->timestamp,
                'description' => 'Plan: ' . $plan->name,
                'uid' => $user->id,
                'test_mode' => $isTestMode ? 1 : 0,
            ];

            // Make API call to PaymentWall to process the charge
            $response = $this->processCharge($chargeData, $settings['payment_settings']['paymentwall_private_key']);

            if ($response && isset($response['type']) && $response['type'] === 'Charge' && $response['captured']) {
                // Payment successful
                processPaymentSuccess([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'paymentwall',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $response['id'] ?? 'brick_' . time(),
                ]);

                return redirect()->route('plans.index')->with('success', __('Payment successful and plan activated'));
            } else {
                $errorMessage = $response['error'] ?? __('Payment processing failed');
                return back()->withErrors(['error' => $errorMessage]);
            }
        } catch (\Exception $e) {
            return handlePaymentError($e, 'paymentwall');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
                $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['paymentwall_public_key'])) {
                return response()->json(['error' => __('PaymentWall not configured')], 400);
            }

            $user = auth()->user();
            $currency = $settings['general_settings']['currency'] ?? 'USD';

            $isTestMode = ($settings['payment_settings']['paymentwall_mode'] ?? 'sandbox') === 'sandbox';

            // Return Brick.js configuration
            return response()->json([
                'success' => true,
                'brick_config' => [
                    'public_key' => $settings['payment_settings']['paymentwall_public_key'],
                    'amount' => $pricing['final_price'],
                    'currency' => $currency,
                    'plan_name' => $plan->name,
                    'success_url' => route('paymentwall.success'),
                    'action_url' => route('paymentwall.process'),
                    'plan_id' => $plan->id,
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'billing_cycle' => $validated['billing_cycle'],
                    'test_mode' => $isTestMode
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function success(Request $request)
    {
        return redirect()->route('plans.index')->with('success', __('Payment completed successfully'));
    }

    public function callback(Request $request)
    {
        try {
            $settings = getPaymentGatewaySettings();
            $privateKey = $settings['payment_settings']['paymentwall_private_key'] ?? '';

            // Validate pingback signature
            if (!$this->validatePingback($request->all(), $privateKey)) {
                return response('Invalid signature', 400);
            }

            $userId = $request->input('uid');
            $type = $request->input('type');
            $ref = $request->input('ref');
            $externalId = $request->input('goodsid');

            // Type 0 = payment successful, Type 1 = payment pending, Type 2 = payment failed
            if ($userId && $type === '0') {
                $user = \App\Models\User::find($userId);

                if ($user && $externalId) {
                    // Extract plan ID from external_id (format: plan_X_timestamp)
                    if (preg_match('/^plan_(\d+)_/', $externalId, $matches)) {
                        $planId = $matches[1];
                        $plan = Plan::find($planId);

                        if ($plan) {
                            // Check if this payment was already processed
                            $existingOrder = \App\Models\PlanOrder::where('payment_id', $ref)
                                ->where('user_id', $user->id)
                                ->first();

                            if (!$existingOrder) {
                                processPaymentSuccess([
                                    'user_id' => $user->id,
                                    'plan_id' => $plan->id,
                                    'billing_cycle' => 'monthly', // Default to monthly
                                    'payment_method' => 'paymentwall',
                                    'payment_id' => $ref,
                                ]);
                            }
                        }
                    }
                }
            }

            return response('OK');
        } catch (\Exception $e) {
            return response(__('Error processing callback'), 500);
        }
    }



    private function validatePingback($params, $secretKey)
    {
        $signature = $params['sig'] ?? '';
        unset($params['sig']);

        $str = '';
        ksort($params);
        foreach ($params as $key => $value) {
            $str .= $key . '=' . $value;
        }
        $str .= $secretKey;

        return md5($str) === $signature;
    }

    private function processCharge($chargeData, $privateKey)
    {
        try {
            $url = 'https://api.paymentwall.com/api/brick/charge';

            // Add private key to the data
            $chargeData['key'] = $privateKey;

            // Make HTTP request to PaymentWall Brick API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($chargeData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return null;
            }

            $responseData = json_decode($response, true);

            return $responseData;
        } catch (\Exception $e) {
            return null;
        }
    }
    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'brick_token' => 'required|string',
            'brick_fingerprint' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            // Get PaymentWall settings for this invoice creator
            $settings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->pluck('value', 'key')
                ->toArray();

            if (!isset($settings['paymentwall_private_key'])) {
                return back()->withErrors(['error' => __('PaymentWall not configured')]);
            }

            $chargeData = [
                'token' => $request->brick_token,
                'fingerprint' => $request->brick_fingerprint,
                'amount' => $request->amount,
                'currency' => 'USD',
                'email' => $invoice->client->email ?? 'customer@example.com',
                'description' => 'Invoice Payment - #' . $invoice->invoice_number,
                'uid' => $invoice->client_id,
                'test_mode' => ($settings['paymentwall_mode'] ?? 'sandbox') === 'sandbox' ? 1 : 0,
            ];

            $response = $this->processCharge($chargeData, $settings['paymentwall_private_key']);

            if ($response && isset($response['type']) && $response['type'] === 'Charge' && $response['captured']) {
                $invoice->createPaymentRecord($request->amount, 'paymentwall', $response['id'] ?? 'brick_' . time());
                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('success', __('Payment successful!'));
            }

            $errorMessage = $response['error'] ?? __('Payment processing failed');
            return back()->withErrors(['error' => $errorMessage]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors(['error' => __('Invoice not found. Please check the link and try again.')]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
        }
    }

    public function createInvoicePayment(Request $request)
    {

        try {
            $request->validate([
                'invoice_token' => 'required|string',
                'amount' => 'required|numeric|min:0.01'
            ]);

            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $paymentSettings = $invoice->getPaymentSettings('paymentwall');

            if (empty($paymentSettings['paymentwall_public_key']) || empty($paymentSettings['paymentwall_private_key']) || $paymentSettings['is_paymentwall_enabled'] !== '1') {
                throw new \Exception('PaymentWall payment not configured');
            }

            $orderId = 'invoice_' . $invoice->id . '_' . time();
            $isTestMode = ($paymentSettings['paymentwall_mode'] ?? 'sandbox') === 'sandbox';

            // Return Brick.js configuration for invoice payment
            return response()->json([
                'success' => true,
                'brick_config' => [
                    'public_key' => $paymentSettings['paymentwall_public_key'],
                    'amount' => $request->amount,
                    'currency' => 'USD',
                    'description' => 'Invoice Payment - ' . $invoice->invoice_number,
                    'success_url' => route('paymentwall.invoice.success', [
                        'response' => 'success',
                        'order_id' => $orderId,
                        'invoice_token' => $request->invoice_token,
                        'amount' => $request->amount
                    ]),
                    'action_url' => route('paymentwall.process.invoice'),
                    'invoice_token' => $request->invoice_token,
                    'order_id' => $orderId,
                    'test_mode' => $isTestMode
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {

        try {
            $response = $request->input('response');
            $orderId = $request->input('order_id');
            $invoiceToken = $request->input('invoice_token');
            $amount = $request->input('amount');

            if ($response === 'success' && $orderId && $invoiceToken) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    // Check if payment already exists
                    $existingPayment = \App\Models\Payment::where('invoice_id', $invoice->id)
                        ->where('transaction_id', $orderId)
                        ->first();

                    if (!$existingPayment && $amount) {
                        $invoice->createPaymentRecord($amount, 'paymentwall', $request->input('ref') ?? $orderId);
                    }

                    return redirect()->route('invoice.payment', $invoice->payment_token)
                        ->with('success', __('Payment successful!'));
                }
            }

            return redirect()->route('invoice.payment', $invoiceToken ?: 'invalid')
                ->withErrors(['error' => __('Payment failed or cancelled')]);
        } catch (\Exception $e) {

            return redirect()->route('invoice.payment', $request->input('invoice_token', 'invalid'))
                ->withErrors(['error' => 'Payment processing failed']);
        }
    }

    public function invoiceCallback(Request $request)
    {
        try {
            $orderId = $request->input('goodsid'); // PaymentWall uses goodsid
            $type = $request->input('type'); // 0 = success, 1 = pending, 2 = failed

            if ($orderId && $type === '0') {
                // Extract invoice info from order ID
                $parts = explode('_', $orderId);
                if (count($parts) >= 3) {
                    $invoiceId = $parts[1];
                    $invoice = \App\Models\Invoice::find($invoiceId);

                    if ($invoice) {
                        // Get payment amount from request or use remaining amount as fallback
                        $paymentAmount = $request->input('amount') ?: $invoice->remaining_amount;
                        $invoice->createPaymentRecord($paymentAmount, 'paymentwall', $request->input('ref') ?? $orderId);
                    }
                }
            }

            return response('OK');
        } catch (\Exception $e) {
            return response('FAILED', 500);
        }
    }
}
