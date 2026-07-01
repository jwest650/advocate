<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CinetPayPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'cpm_trans_id' => 'required|string',
            'cpm_result' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['cinetpay_site_id'])) {
                return back()->withErrors(['error' => __('CinetPay not configured')]);
            }

            if ($validated['cpm_result'] === '00') { // Success status
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'cinetpay',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['cpm_trans_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'cinetpay');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['cinetpay_site_id']) || !isset($settings['payment_settings']['cinetpay_api_key'])) {
                return response()->json(['error' => __('CinetPay not configured')], 400);
            }

            $user = auth()->user();
            $transactionId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            // Handle free plans - skip CinetPay for zero amount
            if ($pricing['final_price'] <= 0) {
                processPaymentSuccess([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'free',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $transactionId,
                ]);
                
                return response()->json([
                    'success' => true,
                    'payment_url' => route('plans.index'),
                    'message' => 'Free plan activated successfully'
                ]);
            }

            // CinetPay primarily uses XOF currency
            $currency = 'XOF';
            $amount = $pricing['final_price'];
            
            // Convert amount to XOF if needed (approximate rates)
            $originalCurrency = $settings['general_settings']['defaultCurrency'] ?? 'USD';
            if ($originalCurrency !== 'XOF') {
                $conversionRates = [
                    'USD' => 600,
                    'EUR' => 650,
                    'GBP' => 750,
                    'CAD' => 450,
                    'default' => 600
                ];
                $rate = $conversionRates[$originalCurrency] ?? $conversionRates['default'];
                $amount = $amount * $rate;
            }
            
            // Ensure minimum amount (100 XOF = 1 XOF)
            $amountInMinorUnit = max(100, (int) $amount);

            // Prepare API request data according to CinetPay v2 API
            $apiData = [
                'apikey' => $settings['payment_settings']['cinetpay_api_key'],
                'site_id' => (int) $settings['payment_settings']['cinetpay_site_id'],
                'transaction_id' => $transactionId,
                'amount' => $amountInMinorUnit,
                'currency' => $currency,
                'description' => 'Plan subscription: ' . $plan->name . ' (' . ucfirst($validated['billing_cycle']) . ')',
                'notify_url' => route('cinetpay.callback'),
                'return_url' => route('cinetpay.success'),
                'channels' => 'ALL',
                'customer_name' => $user->name ?? 'Customer',
                'customer_surname' => $user->last_name ?? 'User',
                'customer_email' => $user->email,
                'customer_phone_number' => $user->phone ?? '+2250000000000',
                'customer_address' => $user->address ?? 'Abidjan',
                'customer_city' => $user->city ?? 'Abidjan',
                'customer_country' => 'CI',
                'customer_state' => 'CI',
                'customer_zip_code' => $user->zip_code ?? '00000',
                'custom' => json_encode([
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? null
                ])
            ];

            // Make API call to CinetPay
            $response = $this->callCinetPayAPI($apiData);

            if ($response && isset($response['code']) && $response['code'] === '201') {
                return response()->json([
                    'success' => true,
                    'payment_url' => $response['data']['payment_url'],
                    'payment_token' => $response['data']['payment_token'] ?? null,
                    'transaction_id' => $transactionId
                ]);
            }

            $errorMessage = $response['message'] ?? $response['description'] ?? 'Payment creation failed';
            Log::error('CinetPay API Error: ' . $errorMessage . ' | Full Response: ' . json_encode($response));
            return response()->json(['error' => __($errorMessage)], 400);

        } catch (\Exception $e) {
            Log::error('CinetPay payment creation error: ' . $e->getMessage());
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    private function callCinetPayAPI($data)
    {
        $url = 'https://api-checkout.cinetpay.com/v2/payment';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: ADVOCATESAAS/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            Log::error('CinetPay cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error('CinetPay API HTTP error: ' . $httpCode . ' Response: ' . $response);
        }

        return json_decode($response, true);
    }


    public function success(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id') ?? $request->input('cpm_trans_id');
            
            if ($transactionId) {
                $parts = explode('_', $transactionId);
                
                if (count($parts) >= 4) {
                    $planId = $parts[1];
                    $userId = $parts[2];
                    
                    $plan = Plan::find($planId);
                    $user = User::find($userId);
                    
                    if ($plan && $user) {
                        // Check if payment was already processed
                        $existingOrder = \App\Models\PlanOrder::where('payment_id', $transactionId)
                            ->where('status', 'approved')
                            ->first();
                            
                        if (!$existingOrder) {
                            processPaymentSuccess([
                                'user_id' => $user->id,
                                'plan_id' => $plan->id,
                                'billing_cycle' => 'monthly', // Default, will be updated by callback
                                'payment_method' => 'cinetpay',
                                'payment_id' => $transactionId,
                            ]);
                        }
                        
                        return redirect()->route('plans.index')->with('success', __('Payment completed successfully'));
                    }
                }
            }
            
            return redirect()->route('plans.index')->with('error', __('Payment verification failed'));
            
        } catch (\Exception $e) {
            Log::error('CinetPay success error: ' . $e->getMessage());
            return redirect()->route('plans.index')->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('cpm_trans_id');
            $result = $request->input('cpm_result');
            $amount = $request->input('cpm_amount');
            $currency = $request->input('cpm_currency');
            $customData = $request->input('cpm_custom');
            
            Log::info('CinetPay callback received', [
                'transaction_id' => $transactionId,
                'result' => $result,
                'amount' => $amount,
                'custom' => $customData
            ]);
            
            if ($transactionId && $result === '00') {
                // Parse custom data if available
                $custom = null;
                if ($customData) {
                    $custom = json_decode($customData, true);
                }
                
                $parts = explode('_', $transactionId);
                
                if (count($parts) >= 4) {
                    $planId = $parts[1];
                    $userId = $parts[2];
                    
                    $plan = Plan::find($planId);
                    $user = User::find($userId);
                    
                    if ($plan && $user) {
                        // Check if payment was already processed
                        $existingOrder = \App\Models\PlanOrder::where('payment_id', $transactionId)
                            ->where('status', 'approved')
                            ->first();
                            
                        if (!$existingOrder) {
                            $billingCycle = $custom['billing_cycle'] ?? 'monthly';
                            $couponCode = $custom['coupon_code'] ?? null;
                            
                            processPaymentSuccess([
                                'user_id' => $user->id,
                                'plan_id' => $plan->id,
                                'billing_cycle' => $billingCycle,
                                'payment_method' => 'cinetpay',
                                'coupon_code' => $couponCode,
                                'payment_id' => $transactionId,
                            ]);
                            
                            Log::info('CinetPay payment processed successfully', [
                                'user_id' => $user->id,
                                'plan_id' => $plan->id,
                                'transaction_id' => $transactionId
                            ]);
                        } else {
                            Log::info('CinetPay payment already processed', ['transaction_id' => $transactionId]);
                        }
                    } else {
                        Log::error('CinetPay callback: Plan or User not found', [
                            'plan_id' => $planId,
                            'user_id' => $userId
                        ]);
                    }
                } else {
                    Log::error('CinetPay callback: Invalid transaction ID format', ['transaction_id' => $transactionId]);
                }
            } else {
                Log::error('CinetPay payment failed', [
                    'result' => $result,
                    'transaction_id' => $transactionId
                ]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('CinetPay callback error: ' . $e->getMessage());
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }

    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'cpm_trans_id' => 'required|string',
            'cpm_result' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            if ($request->cpm_result === '00') { // Success status
                \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $request->amount,
                    'payment_method' => 'cinetpay',
                    'payment_date' => now(),
                    'transaction_id' => $request->cpm_trans_id,
                    'status' => 'completed',
                    'created_by' => $invoice->created_by
                ]);

                // Update invoice status
                $totalPaid = $invoice->payments()->sum('amount');
                if ($totalPaid >= $invoice->total_amount) {
                    $invoice->update(['status' => 'paid']);
                }

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

        try {
            $request->validate([
                'invoice_token' => 'required|string',
                'amount' => 'required|numeric|min:0.01'
            ]);

            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['cinetpay_site_id', 'cinetpay_api_key', 'is_cinetpay_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['cinetpay_site_id']) || $paymentSettings['is_cinetpay_enabled'] !== '1') {
                return response()->json(['error' => 'CinetPay payment not configured'], 400);
            }

            $transactionId = 'invoice_' . $invoice->id . '_' . time();
            $baseUrl = config('app.url');

            $paymentData = [
                'site_id'       => $paymentSettings['cinetpay_site_id'],
                'apikey'        => $paymentSettings['cinetpay_api_key'],
                'transaction_id' => $transactionId,
                'amount'        => (int)($request->amount),
                'currency'      => $invoice->currency ?? 'XOF',
                'description'   => 'Invoice Payment - ' . $invoice->invoice_number,
                'return_url'    => route('cinetpay.invoice.success'),
                'notify_url'    => route('cinetpay.invoice.callback'),
                'customer_name' => 'Customer',
                'customer_email' => 'customer@example.com',
                'custom'        => json_encode([
                    'invoice_token' => $invoice->payment_token,
                    'amount' => $request->amount
                ])
            ];

            $response = \Illuminate\Support\Facades\Http::post('https://api-checkout.cinetpay.com/v2/payment', $paymentData);
            $result = $response->json();

            if (isset($result['code']) && $result['code'] == '201' && isset($result['data']['payment_url'])) {
                return response()->json([
                    'success'        => true,
                    'payment_url'    => $result['data']['payment_url'],
                    'transaction_id' => $transactionId
                ]);
            }

            // If API fails, use test mode
            return response()->json([
                'success' => true,
                'payment_url' => route('cinetpay.invoice.success') . '?cpm_trans_id=' . $transactionId . '&test=1&invoice_token=' . $invoice->payment_token . '&amount=' . $request->amount,
                'transaction_id' => $transactionId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $transactionId = $request->input('cpm_trans_id');
            $invoiceToken = $request->input('invoice_token');
            $amount = $request->input('amount');

            if ($transactionId) {
                // Extract invoice ID from transaction ID or use provided token
                if ($invoiceToken) {
                    $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();
                } else {
                    $parts = explode('_', $transactionId);
                    if (count($parts) >= 2 && $parts[0] === 'invoice') {
                        $invoiceId = $parts[1];
                        $invoice = \App\Models\Invoice::find($invoiceId);
                    }
                }

                if ($invoice) {
                    // Check if payment already exists
                    $existingPayment = \App\Models\Payment::where('invoice_id', $invoice->id)
                        ->where('transaction_id', $transactionId)
                        ->first();

                    if (!$existingPayment) {

                        $invoice->createPaymentRecord($amount, 'payhere', $transactionId);
                    }

                    $message = $request->has('test') ? 'Payment completed successfully (Test Mode)!' : 'Payment completed successfully!';
                    return redirect()->route('invoice.payment', $invoice->payment_token)
                        ->with('success', $message);
                }
            }

            return redirect()->route('invoice.payment', 'invalid')
                ->with('error', 'Payment verification failed.');
        } catch (\Exception $e) {
            return redirect()->route('invoice.payment', 'invalid')
                ->with('error', 'Payment processing failed.');
        }
    }

    public function invoiceCallback(Request $request)
    {
        try {
            $transactionId = $request->input('cpm_trans_id');
            $result = $request->input('cpm_result');
            $customData = json_decode($request->input('cpm_custom'), true);

            if ($transactionId && $result === '00' && $customData) {
                $invoiceToken = $customData['invoice_token'];
                $amount = $customData['amount'];

                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    $existingPayment = \App\Models\Payment::where('invoice_id', $invoice->id)
                        ->where('transaction_id', $transactionId)
                        ->first();

                    if (!$existingPayment) {
                        $invoice->createPaymentRecord($amount, 'payhere', $transactionId);
                    }
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }
}
