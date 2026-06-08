<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class MidtransPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'transaction_status' => 'required|string',
            'order_id' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['midtrans_secret_key'])) {
                return back()->withErrors(['error' => __('Midtrans not configured')]);
            }

            if (in_array($validated['transaction_status'], ['capture', 'settlement'])) {
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'midtrans',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['order_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'midtrans');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['midtrans_secret_key'])) {
                return response()->json(['error' => __('Midtrans not configured')], 400);
            }

            $user = auth()->user();
            $orderId = auth()->id() . '_' . $plan->id . '_' . $validated['billing_cycle'] . '_' . time();

            // Convert to IDR (whole numbers only, no cents)
            $amount = intval($pricing['final_price']);

            $paymentData = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount
                ],
                'credit_card' => [
                    'secure' => true
                ],
                'customer_details' => [
                    'first_name' => $user->name ?? 'Customer',
                    'email' => $user->email,
                ],
                'item_details' => [
                    [
                        'id' => $plan->id,
                        'price' => $amount,
                        'quantity' => 1,
                        'name' => $plan->name
                    ]
                ],
                'callbacks' => [
                    'finish' => route('midtrans.success') . '?order_id=' . $orderId
                ]
            ];

            $snapResult = $this->createSnapToken($paymentData, $settings['payment_settings']);

            if ($snapResult && isset($snapResult['token'])) {
                return response()->json([
                    'success' => true,
                    'snap_token' => $snapResult['token'],
                    'redirect_url' => $snapResult['redirect_url'] ?? null,
                    'order_id' => $orderId
                ]);
            }

            throw new \Exception(__('Failed to create Midtrans snap token'));

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed: ') . $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $orderId = $request->input('order_id');

            if ($orderId) {
                $parts = explode('_', $orderId);

                if (count($parts) >= 4) {
                    $userId = $parts[0];
                    $planId = $parts[1];
                    $billingCycle = $parts[2];

                    $plan = Plan::find($planId);
                    $user = \App\Models\User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => $billingCycle,
                            'payment_method' => 'midtrans',
                            'payment_id' => $orderId,
                        ]);

                        return redirect()->route('plans.index')->with('success', __('Payment completed successfully!'));
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
            $transactionStatus = $request->input('transaction_status');

            if ($orderId && in_array($transactionStatus, ['capture', 'settlement'])) {
                $parts = explode('_', $orderId);

                if (count($parts) >= 4) {
                    $userId = $parts[0];
                    $planId = $parts[1];
                    $billingCycle = $parts[2];

                    $plan = Plan::find($planId);
                    $user = \App\Models\User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => $billingCycle,
                            'payment_method' => 'midtrans',
                            'payment_id' => $request->input('transaction_id'),
                        ]);

                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }

    private function createSnapToken($paymentData, $settings)
    {
        try {
            $baseUrl = $settings['midtrans_mode'] === 'live'
                ? 'https://app.midtrans.com'
                : 'https://app.sandbox.midtrans.com';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/snap/v1/transactions');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode($settings['midtrans_secret_key'] . ':'),
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception('cURL Error: ' . $curlError);
            }

            if ($httpCode !== 201) {
                throw new \Exception('HTTP Error: ' . $httpCode . ' - ' . $response);
            }

            $result = json_decode($response, true);

            if (!isset($result['token'])) {
                throw new \Exception('No token in response: ' . $response);
            }

            return $result;

        } catch (\Exception $e) {
            return false;
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
            $paymentSettings = $invoice->getPaymentSettings('midtrans');

            if (empty($paymentSettings['midtrans_secret_key']) || $paymentSettings['is_midtrans_enabled'] !== '1') {
                return response()->json(['error' => 'Midtrans payment not configured'], 400);
            }

            $orderId = 'invoice_' . $invoice->id . '_' . time();
            $amount = intval($request->amount);

            $paymentData = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount
                ],
                'credit_card' => ['secure' => true],
                'item_details' => [[
                    'id' => $invoice->id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Invoice Payment - ' . $invoice->invoice_number
                ]],
                'callbacks' => [
                    'finish' => route('midtrans.invoice.success') . '?order_id=' . $orderId . '&invoice_token=' . $request->invoice_token
                ]
            ];

            $snapResult = $this->createSnapToken($paymentData, $paymentSettings);

            if ($snapResult && isset($snapResult['token'])) {
                return response()->json([
                    'success' => true,
                    'snap_token' => $snapResult['token'],
                    'order_id' => $orderId
                ]);
            }

            return response()->json(['error' => 'Failed to create payment'], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $invoiceToken = $request->input('invoice_token');

            if ($orderId && $invoiceToken) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    // Get the actual payment amount from Midtrans API
                    $amount = $invoice->remaining_amount; // fallback
                    
                    try {
                        $paymentSettings = $invoice->getPaymentSettings('midtrans');
                        if (!empty($paymentSettings['midtrans_secret_key'])) {
                            $baseUrl = ($paymentSettings['midtrans_mode'] ?? 'sandbox') === 'live'
                                ? 'https://api.midtrans.com'
                                : 'https://api.sandbox.midtrans.com';
                            
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/' . $orderId . '/status');
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Basic ' . base64_encode($paymentSettings['midtrans_secret_key'] . ':'),
                                'Accept: application/json'
                            ]);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            
                            $response = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            
                            if ($httpCode === 200) {
                                $result = json_decode($response, true);
                                if (isset($result['gross_amount'])) {
                                    $amount = (float)$result['gross_amount'];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Use fallback amount if API call fails
                    }

                    $invoice->createPaymentRecord($amount, 'midtrans', $orderId);

                    return redirect()->route('invoice.payment', $invoice->payment_token)
                        ->with('success', __('Payment successful!'));
                }
            }

            return redirect()->back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->withErrors(['error' => __('Invoice not found. Please check the link and try again.')]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
        }
    }

    public function invoiceCallback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $transactionStatus = $request->input('transaction_status');

            if ($orderId && in_array($transactionStatus, ['capture', 'settlement'])) {
                // Extract invoice info from order ID
                $parts = explode('_', $orderId);
                if (count($parts) >= 3) {
                    $invoiceId = $parts[1];
                    $invoice = \App\Models\Invoice::find($invoiceId);

                    if ($invoice) {
                        // Get payment amount from the callback request
                        $amount = $request->input('gross_amount') ? (float)$request->input('gross_amount') : $invoice->remaining_amount;
                        $invoice->createPaymentRecord($amount, 'midtrans', $request->input('transaction_id') ?? $orderId);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }
}
