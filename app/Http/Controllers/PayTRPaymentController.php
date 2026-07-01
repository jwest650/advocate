<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayTRPaymentController extends Controller
{
    private function getPayTRCredentials($userId = null)
    {
        if ($userId) {
            $settings = \App\Models\PaymentSetting::where('user_id', $userId)
                ->whereIn('key', ['paytr_merchant_id', 'paytr_merchant_key', 'paytr_merchant_salt'])
                ->pluck('value', 'key')
                ->toArray();

            return [
                'merchant_id' => $settings['paytr_merchant_id'] ?? null,
                'merchant_key' => $settings['paytr_merchant_key'] ?? null,
                'merchant_salt' => $settings['paytr_merchant_salt'] ?? null,
                'currency' => 'TRY'
            ];
        }

        $settings = getPaymentGatewaySettings();

        return [
            'merchant_id' => $settings['payment_settings']['paytr_merchant_id'] ?? null,
            'merchant_key' => $settings['payment_settings']['paytr_merchant_key'] ?? null,
            'merchant_salt' => $settings['payment_settings']['paytr_merchant_salt'] ?? null,
            'currency' => 'TRY'
        ];
    }

    public function createPaymentToken(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'user_name' => 'required|string',
            'user_email' => 'required|email',
            'user_phone' => 'required|string',
            'user_address' => 'nullable|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $credentials = $this->getPayTRCredentials();

            if (!$credentials['merchant_id'] || !$credentials['merchant_key'] || !$credentials['merchant_salt']) {
                throw new \Exception(__('PayTR credentials not configured'));
            }

            $merchant_oid = 'plan_' . $plan->id . '_' . time() . '_' . uniqid();
            $payment_amount = intval($pricing['final_price'] * 100); // Convert to kuruş
            $user_basket = json_encode([[
                $plan->name . ' - ' . ucfirst($validated['billing_cycle']),
                number_format($pricing['final_price'], 2),
                1
            ]]);

            // Create pending order
            createPlanOrder([
                'user_id' => auth()->id(),
                'plan_id' => $plan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => 'paytr',
                'coupon_code' => $validated['coupon_code'] ?? null,
                'payment_id' => $merchant_oid,
                'status' => 'pending'
            ]);

            // Generate hash according to PayTR documentation
            $hashStr = $credentials['merchant_id'] .
                      $request->ip() .
                      $merchant_oid .
                      $validated['user_email'] .
                      $payment_amount .
                      $user_basket .
                      '1' . // no_installment
                      '0' . // max_installment
                      $credentials['currency'] .
                      '1' . // test_mode
                      $credentials['merchant_salt'];

            $paytr_token = base64_encode(hash_hmac('sha256', $hashStr, $credentials['merchant_key'], true));

            $post_data = [
                'merchant_id' => $credentials['merchant_id'],
                'user_ip' => $request->ip(),
                'merchant_oid' => $merchant_oid,
                'email' => $validated['user_email'],
                'payment_amount' => $payment_amount,
                'paytr_token' => $paytr_token,
                'user_basket' => $user_basket,
                'no_installment' => 1,
                'max_installment' => 0,
                'user_name' => $validated['user_name'],
                'user_address' => $validated['user_address'] ?? 'Turkey',
                'user_phone' => $validated['user_phone'],
                'merchant_ok_url' => route('paytr.success'),
                'merchant_fail_url' => route('paytr.failure'),
                'timeout_limit' => 30,
                'currency' => $credentials['currency'],
                'test_mode' => 1
            ];

            $response = Http::asForm()->timeout(40)->post('https://www.paytr.com/odeme/api/get-token', $post_data);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['status'] == 'success') {
                    return response()->json([
                        'success' => true,
                        'token' => $result['token'],
                        'iframe_url' => 'https://www.paytr.com/odeme/guvenli/' . $result['token']
                    ]);
                } else {
                    throw new \Exception($result['reason'] ?? __('Token generation failed'));
                }
            } else {
                throw new \Exception(__('PayTR API connection failed'));
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        return redirect()->route('plans.index')->with('success', __('Payment completed successfully!'));
    }

    public function failure(Request $request)
    {
        return redirect()->route('plans.index')->with('error', __('Payment failed. Please try again.'));
    }

    public function callback(Request $request)
    {
        try {
            $merchant_oid = $request->input('merchant_oid');
            $status = $request->input('status');
            $total_amount = $request->input('total_amount');
            $hash = $request->input('hash');

            $credentials = $this->getPayTRCredentials();

            // Verify hash for security
            $hashStr = $merchant_oid . $credentials['merchant_salt'] . $status . $total_amount;
            $calculatedHash = base64_encode(hash_hmac('sha256', $hashStr, $credentials['merchant_key'], true));

            if ($hash === $calculatedHash && $status === 'success') {
                $planOrder = \App\Models\PlanOrder::where('payment_id', $merchant_oid)->first();

                if ($planOrder && $planOrder->status === 'pending') {
                    processPaymentSuccess([
                        'user_id' => $planOrder->user_id,
                        'plan_id' => $planOrder->plan_id,
                        'billing_cycle' => $planOrder->billing_cycle,
                        'payment_method' => 'paytr',
                        'coupon_code' => $planOrder->coupon_code,
                        'payment_id' => $merchant_oid,
                    ]);
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }
    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'merchant_oid' => 'required|string',
            'status' => 'required|string',
            'hash' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            // Verify hash for security
            $credentials = $this->getPayTRCredentials();
            $hashStr = $request->merchant_oid . $credentials['merchant_salt'] . $request->status . ($request->amount * 100);
            $calculatedHash = base64_encode(hash_hmac('sha256', $hashStr, $credentials['merchant_key'], true));

            if ($request->hash !== $calculatedHash) {
                return back()->withErrors(['error' => __('Payment verification failed')]);
            }

            if ($request->status === 'success') {
                // Use common function like other payment methods
                $invoice->createPaymentRecord($request->amount, 'paytr', $request->merchant_oid);

                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ;
            }

            return back()->withErrors(['error' => __('Payment failed')]);

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
        $validated = $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $validated['invoice_token'])->firstOrFail();

            $credentials = $this->getPayTRCredentials($invoice->created_by);

            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['is_paytr_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (!$credentials['merchant_id'] || $paymentSettings['is_paytr_enabled'] !== '1') {
                return response()->json(['error' => 'PayTR payment not configured'], 400);
            }

            $merchant_oid = 'invoice_' . $invoice->id . '_' . time() . '_' . uniqid();
            $payment_amount = intval($validated['amount'] * 100);
            $user_basket = json_encode([[
                'Invoice #' . $invoice->invoice_number,
                number_format($validated['amount'], 2),
                1
            ]]);

            $hashStr = $credentials['merchant_id'] .
                      $request->ip() .
                      $merchant_oid .
                      ($invoice->client->email ?? 'customer@example.com') .
                      $payment_amount .
                      $user_basket .
                      '1' . '0' . 'TRY' . '1' .
                      $credentials['merchant_salt'];

            $paytr_token = base64_encode(hash_hmac('sha256', $hashStr, $credentials['merchant_key'], true));

            $post_data = [
                'merchant_id' => $credentials['merchant_id'],
                'user_ip' => $request->ip(),
                'merchant_oid' => $merchant_oid,
                'email' => $invoice->client->email ?? 'customer@example.com',
                'payment_amount' => $payment_amount,
                'paytr_token' => $paytr_token,
                'user_basket' => $user_basket,
                'no_installment' => 1,
                'max_installment' => 0,
                'user_name' => $invoice->client->name ?? 'Customer',
                'user_address' => 'Turkey',
                'user_phone' => $invoice->client->phone ?? '5555555555',
                'merchant_ok_url' => route('paytr.invoice.success'),
                'merchant_fail_url' => route('invoice.payment', $validated['invoice_token']),
                'timeout_limit' => 30,
                'currency' => 'TRY',
                'test_mode' => 1
            ];

            $response = Http::asForm()->timeout(40)->post('https://www.paytr.com/odeme/api/get-token', $post_data);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['status'] == 'success') {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => 'https://www.paytr.com/odeme/guvenli/' . $result['token']
                    ]);
                } else {
                    return response()->json(['error' => $result['reason'] ?? 'Token generation failed'], 500);
                }
            }

            return response()->json(['error' => 'PayTR API connection failed'], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $invoiceToken = $request->input('invoice_token');
            $amount = $request->input('amount');

            if ($invoiceToken && $amount) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    // Process payment immediately like other methods
                    $invoice->createPaymentRecord($amount, 'paytr', 'paytr_' . time());

                    return redirect()->route('invoice.payment', $invoiceToken)
                        ;
                }
            }

            return redirect()->route('home')
                ->with('error', __('Payment verification failed'));

        } catch (\Exception $e) {
            return redirect()->route('home')
                ->with('error', __('Payment processing failed'));
        }
    }
}
