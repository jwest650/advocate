<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class OzowPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'transaction_id' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['ozow_site_key'])) {
                return back()->withErrors(['error' => __('Ozow not configured')]);
            }

            if ($validated['status'] === 'Complete') {
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'ozow',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['transaction_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);
        } catch (\Exception $e) {
            return handlePaymentError($e, 'ozow');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
                $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['ozow_site_key']) || !isset($settings['payment_settings']['ozow_private_key']) || !isset($settings['payment_settings']['ozow_api_key'])) {
                return response()->json(['error' => __('Ozow not configured')], 400);
            }

            $siteCode = $settings['payment_settings']['ozow_site_key'];
            $privateKey = $settings['payment_settings']['ozow_private_key'];
            $apiKey = $settings['payment_settings']['ozow_api_key'];
            $isTest = $settings['payment_settings']['ozow_mode'] == 'sandbox' ? 'true' : 'false';
            $amount = $pricing['final_price'];
            $cancelUrl = route('plans.index');
            $successUrl = route('ozow.success');
            $bankReference = time() . 'FKU';
            $transactionReference = time();
            $countryCode = 'ZA';
            $currency = 'ZAR';

            $inputString = $siteCode . $countryCode . $currency . $amount . $transactionReference . $bankReference . $cancelUrl . $successUrl . $successUrl . $successUrl . $isTest . $privateKey;
            $hashCheck = hash('sha512', strtolower($inputString));

            $data = [
                'countryCode' => $countryCode,
                'amount' => $amount,
                'transactionReference' => $transactionReference,
                'bankReference' => $bankReference,
                'cancelUrl' => $cancelUrl,
                'currencyCode' => $currency,
                'errorUrl' => $successUrl,
                'isTest' => $isTest,
                'notifyUrl' => $successUrl,
                'siteCode' => $siteCode,
                'successUrl' => $successUrl,
                'hashCheck' => $hashCheck,
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.ozow.com/postpaymentrequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'ApiKey: ' . $apiKey,
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);
            $json_attendance = json_decode($response);

            if (isset($json_attendance->url) && $json_attendance->url != null) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $json_attendance->url,
                    'transaction_id' => $transactionReference
                ]);
            } else {
                return response()->json(['error' => __('Payment creation failed')], 500);
            }
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
            $transactionId = $request->input('TransactionReference');
            $status = $request->input('Status');

            if ($transactionId && $status === 'Complete') {
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
                            'payment_method' => 'ozow',
                            'payment_id' => $transactionId,
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }
    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'transaction_id' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            if ($request->status === 'Complete') {
                $invoice->createPaymentRecord($request->amount, 'ozow', $request->transaction_id);

                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('success', __('Payment successful!'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);
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
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['ozow_site_key', 'ozow_private_key', 'ozow_api_key', 'ozow_mode', 'is_ozow_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (
                empty($paymentSettings['ozow_site_key']) ||
                empty($paymentSettings['ozow_private_key']) ||
                empty($paymentSettings['ozow_api_key']) ||
                $paymentSettings['is_ozow_enabled'] !== '1'
            ) {
                return response()->json(['error' => 'Ozow payment not configured'], 400);
            }

            $siteCode = $paymentSettings['ozow_site_key'];
            $privateKey = $paymentSettings['ozow_private_key'];
            $apiKey = $paymentSettings['ozow_api_key'];
            $isTest = ($paymentSettings['ozow_mode'] ?? 'sandbox') == 'sandbox' ? 'true' : 'false';
            $amount = $request->amount;
            $cancelUrl = route('invoice.payment', $request->invoice_token);
            $successUrl = route('ozow.invoice.success') . '?invoice_token=' . $request->invoice_token;
            $notifyUrl = route('ozow.invoice.callback');
            $bankReference = time() . 'INV';
            $transactionReference = time() . '_' . $invoice->id;
            $countryCode = 'ZA';
            $currency = 'ZAR';

            $inputString = $siteCode . $countryCode . $currency . $amount . $transactionReference . $bankReference . $cancelUrl . $successUrl . $successUrl . $notifyUrl . $isTest . $privateKey;
            $hashCheck = hash('sha512', strtolower($inputString));

            $data = [
                'countryCode' => $countryCode,
                'amount' => $amount,
                'transactionReference' => $transactionReference,
                'bankReference' => $bankReference,
                'cancelUrl' => $cancelUrl,
                'currencyCode' => $currency,
                'errorUrl' => $successUrl,
                'isTest' => $isTest,
                'notifyUrl' => $notifyUrl,
                'siteCode' => $siteCode,
                'successUrl' => $successUrl,
                'hashCheck' => $hashCheck,
                'invoice_token' => $request->invoice_token
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.ozow.com/postpaymentrequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'ApiKey: ' . $apiKey,
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);
            $json_response = json_decode($response);

            if (isset($json_response->url) && $json_response->url != null) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $json_response->url,
                    'transaction_id' => $transactionReference
                ]);
            } else {
                return response()->json(['error' => __('Payment creation failed')], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $transactionId = $request->input('TransactionReference');
            $status = $request->input('Status');
            $invoiceToken = $request->input('invoice_token') ?? $request->route('token');

            if ($invoiceToken) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();
                if ($invoice) {
                    return redirect()->route('invoice.payment', $invoiceToken)
                        ->with('success', __('Payment completed successfully'));
                }
            }

            return redirect()->route('invoice.payment', $invoiceToken ?? 'unknown')
                ->with('success', __('Payment completed successfully'));
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', __('Payment processing failed'));
        }
    }

    public function invoiceCallback(Request $request)
    {
        try {
            $transactionId = $request->input('TransactionReference');
            $status = $request->input('Status');
            $amount = $request->input('Amount');
            $invoiceToken = $request->input('invoice_token');

            if ($transactionId && $status === 'Complete' && $invoiceToken) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    $invoice->createPaymentRecord($amount, 'ozow', $transactionId);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }
}
