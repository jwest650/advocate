<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\PaymentSetting;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    /**
     * Get Razorpay API credentials
     *
     * @return array
     */
    private function getRazorpayCredentials()
    {
        $settings = getPaymentGatewaySettings();

        return [
            'key' => $settings['payment_settings']['razorpay_key'] ?? null,
            'secret' => $settings['payment_settings']['razorpay_secret'] ?? null,
            'currency' => $settings['general_settings']['defaultCurrency'] ?? 'INR'
        ];
    }

    /**
     * Create a Razorpay order
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);

            $amountInSmallestUnit = $pricing['final_price'] * 100;

            // Get Razorpay credentials
            $credentials = $this->getRazorpayCredentials();

            if (!$credentials['key'] || !$credentials['secret']) {
                throw new \Exception(__('Razorpay API credentials not found'));
            }

            $api = new Api($credentials['key'], $credentials['secret']);

            $orderData = [
                'receipt' => 'plan_' . $plan->id . '_' . time(),
                'amount' => (int)$amountInSmallestUnit,
                'currency' => $credentials['currency'],
                'notes' => [
                    'plan_id' => $plan->id,
                    'billing_cycle' => $request->billing_cycle,
                ]
            ];

            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'order_id' => $razorpayOrder->id,
                'amount' => (int)$amountInSmallestUnit,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to create payment order: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Verify Razorpay payment
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $credentials = $this->getRazorpayCredentials();

            if (!$credentials['key'] || !$credentials['secret']) {
                throw new \Exception(__('Razorpay API credentials not found'));
            }

            $api = new Api($credentials['key'], $credentials['secret']);
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature' => $validated['razorpay_signature']
            ]);

            processPaymentSuccess([
                'user_id' => auth()->id(),
                'plan_id' => $validated['plan_id'],
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => 'razorpay',
                'coupon_code' => $validated['coupon_code'] ?? null,
                'payment_id' => $validated['razorpay_payment_id'],
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment verification failed: ') . $e->getMessage()], 500);
        }
    }

    public function createInvoiceOrder(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();
            $paymentSettings = $invoice->getPaymentSettings('razorpay');

            if (empty($paymentSettings['razorpay_key']) || empty($paymentSettings['razorpay_secret']) || $paymentSettings['is_razorpay_enabled'] !== '1') {
                return response()->json(['error' => 'Razorpay payment not configured'], 400);
            }

            $api = new Api($paymentSettings['razorpay_key'], $paymentSettings['razorpay_secret']);

            $orderData = [
                'receipt' => 'invoice_' . $invoice->id . '_' . time(),
                'amount' => (int)($request->amount * 100),
                'currency' => 'INR',
                'notes' => [
                    'invoice_id' => $invoice->id,
                    'invoice_token' => $request->invoice_token,
                ]
            ];

            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'order_id' => $razorpayOrder->id,
                'amount' => (int)($request->amount * 100),
                'key' => $paymentSettings['razorpay_key']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();
            $paymentSettings = $invoice->getPaymentSettings('razorpay');

            $api = new Api($paymentSettings['razorpay_key'], $paymentSettings['razorpay_secret']);
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment verification failed: ' . $e->getMessage()], 500);
        }
    }

    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
        ]);

        try {
            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $invoice->createPaymentRecord($request->amount, 'razorpay', $request->razorpay_payment_id);

            return redirect()->route('invoice.payment', $invoice->payment_token);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
    }
}
