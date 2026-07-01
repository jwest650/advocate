<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\PaymentSetting;

class PayHerePaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'coupon_code' => 'nullable|string'
        ]);

        try {
            $plan = \App\Models\Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (
                !isset($settings['payment_settings']['payhere_merchant_id']) ||
                $settings['payment_settings']['is_payhere_enabled'] !== '1'
            ) {
                return response()->json(['error' => 'PayHere not configured'], 400);
            }

            $amount = $validated['billing_cycle'] === 'yearly' ? $plan->yearly_price : $plan->price;

            if (!$amount || $amount <= 0) {
                return response()->json(['error' => 'Invalid plan price for selected billing cycle'], 400);
            }

            if ($validated['coupon_code']) {
                $coupon = \App\Models\Coupon::where('code', $validated['coupon_code'])
                    ->where('is_active', true)
                    ->first();
                if ($coupon) {
                    $amount = $coupon->discount_type === 'percentage'
                        ? $amount * (1 - $coupon->discount_value / 100)
                        : $amount - $coupon->discount_value;
                }
            }

            $user = auth()->user();
            $orderId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            $paymentData = [
                'merchant_id' => $settings['payment_settings']['payhere_merchant_id'],
                'return_url' => route('payhere.success'),
                'cancel_url' => route('plans.index'),
                'notify_url' => route('payhere.callback'),
                'order_id' => $orderId,
                'items' => $plan->name . ' - ' . ucfirst($validated['billing_cycle']),
                'currency' => 'USD',
                'amount' => number_format($amount, 2, '.', ''),
                'first_name' => $user->name ?? 'Customer',
                'last_name' => 'User',
                'email' => $user->email,
                'phone' => '+1234567890',
                'address' => '123 Main Street',
                'city' => 'New York',
                'country' => 'United States',
            ];

            $hash = $this->generatePayHereHash($paymentData, $settings['payment_settings']['payhere_merchant_secret']);
            $paymentData['hash'] = $hash;

            return response()->json([
                'success' => true,
                'payment_data' => $paymentData,
                'action_url' => ($settings['payment_settings']['payhere_mode'] ?? 'sandbox') === 'sandbox'
                    ? 'https://sandbox.payhere.lk/pay/checkout'
                    : 'https://www.payhere.lk/pay/checkout'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment creation failed'], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $orderId = $request->input('order_id') ?? $request->input('merchant_order_id');
            $statusCode = $request->input('status_code') ?? $request->input('payhere_status_code');
            $paymentId = $request->input('payment_id') ?? $request->input('payhere_payment_id');

            if ($orderId) {
                $parts = explode('_', $orderId);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = \App\Models\Plan::find($planId);
                    $user = \App\Models\User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => 'monthly',
                            'payment_method' => 'payhere',
                            'payment_id' => $paymentId ?? $orderId,
                        ]);

                        return redirect()->route('plans.index')->with('success', __('Payment completed successfully and plan activated!'));
                    }
                }
            }

            return redirect()->route('plans.index')->with('error', __('Payment verification failed'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $statusCode = $request->input('status_code');

            if ($orderId && $statusCode === '2') {
                $parts = explode('_', $orderId);

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
                            'payment_method' => 'payhere',
                            'payment_id' => $request->input('payment_id'),
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }

    public function processInvoicePayment(Request $request)
    {
        return $this->createInvoicePayment($request);
    }

    public function createInvoicePayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = Invoice::where('payment_token', $validated['invoice_token'])->firstOrFail();

            $paymentSettings = PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['payhere_merchant_id', 'payhere_merchant_secret', 'payhere_mode', 'is_payhere_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['payhere_merchant_id']) || $paymentSettings['is_payhere_enabled'] !== '1') {
                return response()->json(['error' => 'PayHere payment not configured'], 400);
            }

            if (empty($paymentSettings['payhere_merchant_secret'])) {
                return response()->json(['error' => 'PayHere merchant secret not configured'], 400);
            }

            $orderId = 'invoice_' . $invoice->id . '_' . $validated['amount'] . '_' . time();

            $paymentData = [
                'merchant_id' => $paymentSettings['payhere_merchant_id'],
                'return_url' => route('payhere.invoice.success', $validated['invoice_token']),
                'cancel_url' => route('invoice.payment', $validated['invoice_token']),
                'notify_url' => route('payhere.invoice.notify'),
                'order_id' => $orderId,
                'items' => 'Invoice #' . $invoice->invoice_number,
                'currency' => 'USD',
                'amount' => number_format($validated['amount'], 2, '.', ''),
                'first_name' => $invoice->client->name ?? 'Customer',
                'last_name' => 'User',
                'email' => $invoice->client->email ?? 'customer@example.com',
                'phone' => '+1234567890',
                'address' => '123 Main Street',
                'city' => 'New York',
                'country' => 'United States',
            ];

            $hash = $this->generatePayHereHash($paymentData, $paymentSettings['payhere_merchant_secret']);
            $paymentData['hash'] = $hash;

            return response()->json([
                'success' => true,
                'payment_data' => $paymentData,
                'action_url' => $paymentSettings['payhere_mode'] === 'sandbox'
                    ? 'https://sandbox.payhere.lk/pay/checkout'
                    : 'https://www.payhere.lk/pay/checkout'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment creation failed'], 500);
        }
    }

    public function invoiceSuccess(Request $request, $token)
    {
        $orderId = $request->input('order_id');
        $lockFile = storage_path('app/payhere_locks/' . $orderId . '.lock');

        // Create lock directory if it doesn't exist
        if (!file_exists(dirname($lockFile))) {
            mkdir(dirname($lockFile), 0755, true);
        }

        // Try to acquire lock
        $lockHandle = fopen($lockFile, 'w');
        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            \Log::info('PayHere: Another process is handling this payment, skipping', [
                'order_id' => $orderId,
                'token' => $token
            ]);
            fclose($lockHandle);
            return redirect()->route('invoice.payment', $token)
                ->with('success', __('Payment successful!'));
        }

        try {
            $invoice = Invoice::where('payment_token', $token)->first();

            if (!$invoice) {
                return redirect()->route('invoice.payment', $token)
                    ->with('error', __('Invoice not found'));
            }

            $payherePaymentId = $request->input('payment_id');
            $transactionId = $orderId;

            // Check if payment already exists
            $existingPayment = \App\Models\Payment::where('invoice_id', $invoice->id)
                ->where('transaction_id', $transactionId)
                ->first();

            if ($existingPayment) {

                return redirect()->route('invoice.payment', $token)
                    ->with('success', __('Payment successful!'));
            }
            // Get payment amount from PayHere response or extract from order ID
            $paidAmount = $request->input('payment_amount');


            if (!$paidAmount && $orderId) {
                // Try to extract amount from order ID format: invoice_{id}_{amount}_{timestamp}
                $parts = explode('_', $orderId);

                if (count($parts) >= 3) {
                    $paidAmount = (float)$parts[2];
                }
            }

            // Validate amount is reasonable (not more than remaining amount)
            if ($paidAmount > $invoice->remaining_amount) {

                return redirect()->route('invoice.payment', $token)
                    ->with('error', __('Payment amount validation failed'));
            }

            if (!$paidAmount) {

                return redirect()->route('invoice.payment', $token)
                    ->with('info', __('Payment is being processed. Please wait for confirmation.'));
            }

            // Create payment record in transaction to prevent race conditions
            \DB::transaction(function () use ($invoice, $paidAmount, $transactionId) {
                $invoice->createPaymentRecord($paidAmount, 'payhere', $transactionId);
            });



            return redirect()->route('invoice.payment', $token)
                ->with('success', __('Payment successful!'));
        } catch (\Exception $e) {
            return redirect()->route('invoice.payment', $token)
                ->with('error', __('Payment processing failed. Please try again.'));
        } finally {
            // Release lock and cleanup
            if (isset($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                if (file_exists($lockFile)) {
                    unlink($lockFile);
                }
            }
        }
    }

    public function invoiceNotify(Request $request)
    {
        // Like Tap invoiceCallback - just return OK without creating payments
        return response('OK', 200);
    }

    private function generatePayHereHash($data, $secret)
    {
        $hashString = $data['merchant_id'] . $data['order_id'] . $data['amount'] . $data['currency'] . strtoupper(md5($secret));
        return strtoupper(md5($hashString));
    }

    private function verifyPayHerePayment($request, $invoice)
    {
        try {
            $paymentSettings = PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['payhere_merchant_id', 'payhere_merchant_secret'])
                ->pluck('value', 'key')
                ->toArray();

            $merchantId = $request->input('merchant_id');
            $orderId = $request->input('order_id');
            $paymentAmount = $request->input('payment_amount');
            $currency = $request->input('currency');
            $statusCode = $request->input('status_code');
            $hash = $request->input('md5sig');

            $localHash = strtoupper(md5(
                $merchantId . $orderId . $paymentAmount . $currency . $statusCode .
                    strtoupper(md5($paymentSettings['payhere_merchant_secret']))
            ));

            return $hash === $localHash && $statusCode == 2;
        } catch (\Exception $e) {
            return false;
        }
    }
}
