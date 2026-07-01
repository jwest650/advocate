<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class SSPayPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'status_id' => 'required|string',
            'order_id' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['sspay_secret_key'])) {
                return back()->withErrors(['error' => __('SSPay not configured')]);
            }

            if ($validated['status_id'] === '1') { // Success status
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'sspay',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['order_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'sspay');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['sspay_secret_key'])) {
                return response()->json(['error' => __('SSPay not configured')], 400);
            }

            $user = auth()->user();
            $orderId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            $paymentData = [
                'userSecretKey' => $settings['payment_settings']['sspay_secret_key'],
                'categoryCode' => $settings['payment_settings']['sspay_category_code'],
                'billName' => $plan->name,
                'billDescription' => 'Plan: ' . $plan->name,
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => $pricing['final_price'] * 100, // Convert to cents
                'billReturnUrl' => route('sspay.success'),
                'billCallbackUrl' => route('sspay.callback'),
                'billExternalReferenceNo' => $orderId,
                'billTo' => $user->email,
                'billEmail' => $user->email,
                'billPhone' => '60123456789',
                'billAddrLine1' => 'Address Line 1',
                'billAddrLine2' => 'Address Line 2',
                'billPostcode' => '12345',
                'billCity' => 'Kuala Lumpur',
                'billState' => 'Selangor',
                'billCountry' => 'MY',
            ];

            return response()->json([
                'success' => true,
                'payment_url' => 'https://sspay.my/index.php/api/createBill',
                'payment_data' => $paymentData,
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
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
                            'payment_method' => 'sspay',
                            'payment_id' => $orderId,
                        ]);

                        $message = __('Payment completed successfully!');
                        return redirect()->route('plans.index')->with('success', $message);
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
            $orderId = $request->input('billExternalReferenceNo');
            $statusId = $request->input('status_id');

            if ($orderId && $statusId === '1') {
                $parts = explode('_', $orderId);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = Plan::find($planId);
                    $user = \App\Models\User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => 'monthly',
                            'payment_method' => 'sspay',
                            'payment_id' => $request->input('billcode'),
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    } public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'status_id' => 'required|string',
            'order_id' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            if ($request->status_id === '1') {
                $invoice->createPaymentRecord(
                    $request->amount,
                    'sspay',
                    $request->order_id
                );

                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('success', __('Payment successful'));
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

            // Check if SSPay is configured for this user
            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['sspay_secret_key', 'sspay_category_code', 'is_sspay_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if ($paymentSettings['is_sspay_enabled'] !== '1') {
                return response()->json(['error' => 'SSPay payment method is not enabled'], 400);
            }

            if (empty($paymentSettings['sspay_secret_key']) || empty($paymentSettings['sspay_category_code'])) {
                return response()->json(['error' => 'SSPay credentials are not configured or invalid'], 400);
            }

            // Simulate SSPay test environment (like other payment methods)
            $orderId = 'invoice_' . $invoice->id . '_' . time();

            return response()->json([
                'success' => true,
                'simulate_payment' => true,
                'order_id' => $orderId,
                'message' => 'SSPay test payment will be processed'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        $orderId = $request->input('order_id');
        $invoiceToken = $request->input('invoice_token');

        if ($invoiceToken) {
            $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();
            if ($invoice) {
                $invoice->createPaymentRecord($invoice->total_amount, 'sspay', $orderId);
            }
        }

        return response('SUCCESS');
    }

    public function invoiceCallback(Request $request)
    {

        try {
            $orderId = $request->input('billExternalReferenceNo');
            $statusId = $request->input('status_id');

            if ($orderId && $statusId === '1') {
                $parts = explode('_', $orderId);
                if (count($parts) >= 2 && $parts[0] === 'invoice') {
                    $invoiceId = $parts[1];
                    $invoice = \App\Models\Invoice::find($invoiceId);

                    if ($invoice) {
                        return $invoice->handlePaymentCallback(
                            $orderId,
                            $statusId,
                            'sspay',
                            $request->input('billcode') ?: $orderId
                        );
                    }
                }
            }

            return response('FAILED', 400);

        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }


}
