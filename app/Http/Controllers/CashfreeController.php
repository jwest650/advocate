<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\PaymentSetting;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CashfreeController extends Controller
{
    /**
     * Get Cashfree API credentials and configuration
     */
    private function getCashfreeCredentials($userId = null)
    {
        if ($userId) {
            // For invoice payments - get user-specific settings
            $paymentSettings = PaymentSetting::where('user_id', $userId)
                ->whereIn('key', ['cashfree_public_key', 'cashfree_secret_key', 'cashfree_mode', 'is_cashfree_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            // Check if Cashfree is enabled for this user
            if (empty($paymentSettings['is_cashfree_enabled']) || $paymentSettings['is_cashfree_enabled'] !== '1') {
                Log::warning('Cashfree not enabled for user', ['user_id' => $userId]);
                return ['app_id' => null, 'secret_key' => null, 'mode' => 'sandbox', 'base_url' => '', 'currency' => 'INR'];
            }

            $modeValue = $paymentSettings['cashfree_mode'] ?? 'sandbox';
            $mode = ($modeValue === 0 || $modeValue === '0' || $modeValue === 'sandbox') ? 'sandbox' : 'production';

            return [
                'app_id' => trim($paymentSettings['cashfree_public_key'] ?? ''),
                'secret_key' => trim($paymentSettings['cashfree_secret_key'] ?? ''),
                'mode' => $mode,
                'base_url' => $mode === 'sandbox'
                    ? 'https://sandbox.cashfree.com/pg'
                    : 'https://api.cashfree.com/pg',
                'currency' => 'INR'
            ];
        }

        // For plan payments - get global settings
        $settings = getPaymentGatewaySettings();
        $modeValue = $settings['payment_settings']['cashfree_mode'] ?? 'sandbox';

        $mode = ($modeValue === 0 || $modeValue === '0' || $modeValue === 'sandbox') ? 'sandbox' : 'production';
        $baseUrl = $mode === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';

        return [
            'app_id' => trim($settings['payment_settings']['cashfree_public_key'] ?? ''),
            'secret_key' => trim($settings['payment_settings']['cashfree_secret_key'] ?? ''),
            'mode' => $mode,
            'base_url' => $baseUrl,
            'currency' => $settings['general_settings']['defaultCurrency'] ?? 'INR'
        ];
    }

    /**
     * Make Cashfree API call
     */
    private function makeCashfreeApiCall($method, $endpoint, $data = null, $credentials = null)
    {
        if (!$credentials) {
            $credentials = $this->getCashfreeCredentials();
        }

        if (!$credentials['app_id'] || !$credentials['secret_key']) {
            throw new \Exception('Cashfree API credentials not found');
        }

        $headers = [
            'x-client-id' => $credentials['app_id'],
            'x-client-secret' => $credentials['secret_key'],
            'x-api-version' => '2023-08-01'
        ];

        if ($data) {
            $headers['Content-Type'] = 'application/json';
        }

        $url = $credentials['base_url'] . $endpoint;

        $response = Http::withHeaders($headers)->$method($url, $data);

        if (!$response->successful()) {
            throw new \Exception('API Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create a Cashfree payment session for plans
     */
    public function createPaymentSession(Request $request)
    {
        $validated = validatePaymentRequest($request);
        $credentials = null;

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);

            $credentials = $this->getCashfreeCredentials();

            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                throw new \Exception(__('Cashfree API credentials not found'));
            }

            $amount = (float)$pricing['final_price'];
            if ($amount < 1) {
                throw new \Exception(__('Order amount must be at least 1 INR'));
            }

            $orderId = 'plan_' . $plan->id . '_' . time() . '_' . uniqid();
            $user = auth()->user();

            // Clean phone number
            $phone = $user->phone ?: '9999999999';
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) !== 10) {
                $phone = '9999999999';
            }

            // Prepare API request data
            $orderData = [
                'order_id' => $orderId,
                'order_amount' => $amount,
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'user_' . $user->id,
                    'customer_name' => $user->name ?: 'Customer',
                    'customer_email' => $user->email ?: 'customer@example.com',
                    'customer_phone' => $phone
                ],
                'order_meta' => [
                    'return_url' => route('dashboard'),
                    'notify_url' => route('cashfree.webhook')
                ],
                'order_note' => 'Plan Subscription - ' . $plan->name,
                'order_tags' => [
                    'plan_id' => (string)$plan->id,
                    'billing_cycle' => (string)($validated['billing_cycle'] ?? 'monthly'),
                    'user_id' => (string)$user->id
                ]
            ];

            // Make API call
            $responseData = $this->makeCashfreeApiCall('post', '/orders', $orderData, $credentials);

            return response()->json([
                'payment_session_id' => $responseData['payment_session_id'],
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => 'INR',
                'mode' => $credentials['mode']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create payment session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Cashfree payment
     */
    public function verifyPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'order_id' => 'required|string',
            'cf_payment_id' => 'nullable|string'
        ]);

        try {
            $credentials = $this->getCashfreeCredentials();

            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                throw new \Exception(__('Cashfree API credentials not found'));
            }

            // Fetch order status
            $orderData = $this->makeCashfreeApiCall('get', '/orders/' . $validated['order_id'], null, $credentials);

            if ($orderData['order_status'] !== 'PAID') {
                throw new \Exception(__('Payment not completed successfully'));
            }

            // Get payment details
            $payments = $this->makeCashfreeApiCall('get', '/orders/' . $validated['order_id'] . '/payments', null, $credentials);
            $successfulPayment = null;

            foreach ($payments as $payment) {
                if ($payment['payment_status'] === 'SUCCESS') {
                    $successfulPayment = $payment;
                    break;
                }
            }

            if (!$successfulPayment) {
                throw new \Exception(__('No successful payment found for this order'));
            }

            $paymentData = [
                'user_id' => auth()->id(),
                'plan_id' => $validated['plan_id'],
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => 'cashfree',
                'coupon_code' => $validated['coupon_code'] ?? null,
                'payment_id' => $successfulPayment['cf_payment_id'],
            ];

            $planOrder = processPaymentSuccess($paymentData);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Cashfree payment verification failed', [
                'error' => $e->getMessage(),
                'order_id' => $validated['order_id'] ?? 'unknown'
            ]);
            return response()->json(['error' => __('Payment verification failed: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Create invoice payment session
     */
    public function createInvoicePayment(Request $request)
    {
        try {
            $request->validate([
                'invoice_token' => 'required|string',
                'amount' => 'required|numeric|min:0.01'
            ]);

            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            // Try company credentials first, fallback to global
            $credentials = $this->getCashfreeCredentials($invoice->created_by);
            $usingGlobal = false;

            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                Log::info('Company credentials missing, using global credentials');
                $credentials = $this->getCashfreeCredentials(); // Global credentials
                $usingGlobal = true;
            }

            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                return response()->json([
                    'error' => 'Cashfree payment not configured'
                ], 400);
            }

            Log::info('Invoice payment credentials', [
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->created_by,
                'using_global' => $usingGlobal,
                'mode' => $credentials['mode']
            ]);

            $orderId = 'invoice_' . $invoice->id . '_' . time();

            // Format phone number
            $phone = $invoice->client->phone ?? '9999999999';
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) < 10) {
                $phone = '9999999999';
            }

            $orderData = [
                'order_id' => $orderId,
                'order_amount' => (float)$request->amount,
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'client_' . $invoice->client->id,
                    'customer_name' => $invoice->client->name ?? 'Customer',
                    'customer_email' => $invoice->client->email ?? 'customer@example.com',
                    'customer_phone' => $phone
                ],
                'order_meta' => [
                    'return_url' => route('cashfree.invoice.success') . '?order_id=' . $orderId . '&invoice_token=' . $invoice->payment_token,
                    'notify_url' => route('cashfree.invoice.callback')
                ],
                'order_note' => 'Invoice Payment - ' . $invoice->invoice_id,
                'order_tags' => [
                    'invoice_id' => (string)$invoice->id,
                    'invoice_token' => $invoice->payment_token
                ]
            ];

            $responseData = $this->makeCashfreeApiCall('post', '/orders', $orderData, $credentials);

            return response()->json([
                'success' => true,
                'payment_session_id' => $responseData['payment_session_id'],
                'order_id' => $orderId,
                'amount' => $request->amount,
                'currency' => 'INR',
                'mode' => $credentials['mode']
            ]);
        } catch (\Exception $e) {
            Log::error('Invoice payment creation failed', [
                'error' => $e->getMessage(),
                'invoice_token' => $request->invoice_token ?? 'unknown'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify invoice payment
     */
    public function verifyInvoicePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'invoice_token' => 'required|string'
        ]);

        try {
            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            // Try company credentials first, fallback to global
            $credentials = $this->getCashfreeCredentials($invoice->created_by);
            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                Log::info('Company credentials missing, using global credentials for verification');
                $credentials = $this->getCashfreeCredentials(); // Global credentials
            }

            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                throw new \Exception(__('Cashfree API credentials not found'));
            }

            Log::info('Verifying Cashfree payment', [
                'order_id' => $request->order_id,
                'invoice_id' => $invoice->id,
                'mode' => $credentials['mode']
            ]);

            // Fetch order status
            $orderData = $this->makeCashfreeApiCall('get', '/orders/' . $request->order_id, null, $credentials);

            Log::info('Cashfree order status', [
                'order_id' => $request->order_id,
                'status' => $orderData['order_status'] ?? 'unknown',
                'order_data' => $orderData
            ]);

            if ($orderData['order_status'] !== 'PAID') {
                throw new \Exception(__('Payment not completed successfully. Status: ') . ($orderData['order_status'] ?? 'unknown'));
            }

            // Get payment details
            $payments = $this->makeCashfreeApiCall('get', '/orders/' . $request->order_id . '/payments', null, $credentials);
            $successfulPayment = null;

            Log::info('Cashfree payments data', [
                'order_id' => $request->order_id,
                'payments_count' => count($payments),
                'payments' => $payments
            ]);

            foreach ($payments as $payment) {
                if ($payment['payment_status'] === 'SUCCESS') {
                    $successfulPayment = $payment;
                    break;
                }
            }

            if (!$successfulPayment) {
                // If no successful payment found, but order is PAID, use order amount
                Log::warning('No successful payment found but order is PAID, using order amount');
                $invoice->createPaymentRecord(
                    $orderData['order_amount'],
                    'cashfree',
                    $request->order_id
                );
            } else {
                // Create payment record with successful payment details
                $invoice->createPaymentRecord(
                    $successfulPayment['payment_amount'],
                    'cashfree',
                    $successfulPayment['cf_payment_id']
                );
            }

            Log::info('Payment verification successful', [
                'order_id' => $request->order_id,
                'invoice_id' => $invoice->id
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Invoice payment verification failed', [
                'error' => $e->getMessage(),
                'order_id' => $request->order_id ?? 'unknown',
                'invoice_token' => $request->invoice_token ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('Payment verification failed: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Handle Cashfree webhook
     */
    public function webhook(Request $request)
    {
        try {
            $credentials = $this->getCashfreeCredentials();

            // Verify webhook signature
            $signature = $request->header('x-webhook-signature');
            $timestamp = $request->header('x-webhook-timestamp');
            $rawBody = $request->getContent();

            $expectedSignature = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $credentials['secret_key'], true));

            if (!hash_equals($expectedSignature, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = $request->json()->all();

            if ($data['type'] === 'PAYMENT_SUCCESS_WEBHOOK') {
                $paymentData = $data['data'];
                $orderTags = $paymentData['order']['order_tags'] ?? [];

                if (isset($orderTags['plan_id']) && isset($orderTags['user_id'])) {
                    processPaymentSuccess([
                        'user_id' => $orderTags['user_id'],
                        'plan_id' => $orderTags['plan_id'],
                        'billing_cycle' => $orderTags['billing_cycle'] ?? 'monthly',
                        'payment_method' => 'cashfree',
                        'payment_id' => $paymentData['cf_payment_id'],
                    ]);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Webhook processing failed')], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $invoiceToken = $request->input('invoice_token');

            if (!$orderId || !$invoiceToken) {
                return redirect()->back()->with('error', 'Invalid payment parameters');
            }

            $invoice = Invoice::where('payment_token', $invoiceToken)->first();
            if (!$invoice) {
                return redirect()->back()->with('error', 'Invoice not found');
            }

            // Verify payment with Cashfree API
            $credentials = $this->getCashfreeCredentials($invoice->created_by);

            if (!$credentials['app_id'] || !$credentials['secret_key']) {
                return redirect()->back()->with('error', 'Payment verification failed');
            }

            $orderData = $this->makeCashfreeApiCall('get', '/orders/' . $orderId, null, $credentials);

            if ($orderData['order_status'] === 'PAID') {
                $invoice->createPaymentRecord($invoice->total_amount, 'cashfree', $orderId);
                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('success', 'Payment completed successfully!');
            } else {
                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('error', 'Payment was not completed');
            }
        } catch (\Exception $e) {
            Log::error('Invoice success callback failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Payment verification failed');
        }
    }

    public function invoiceCallback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $status = $request->input('order_status');

            if ($orderId && $status === 'PAID') {
                $parts = explode('_', $orderId);
                if (count($parts) >= 2 && $parts[0] === 'invoice') {
                    $invoiceId = $parts[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $invoice->createPaymentRecord($invoice->total_amount, 'cashfree', $orderId);
                    }
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }
}
