<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class KhaltiPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'token' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['khalti_secret_key'])) {
                return back()->withErrors(['error' => __('Khalti not configured')]);
            }

            // Verify payment with Khalti API
            $isValid = $this->verifyKhaltiPayment($validated['token'], $validated['amount'], $settings['payment_settings']);

            if ($isValid) {
                $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'khalti',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['token'],
                    'amount' => $pricing['final_price']
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'khalti');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
                $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['khalti_public_key'])) {
                return response()->json(['error' => __('Khalti not configured')], 400);
            }

            return response()->json([
                'success' => true,
                'public_key' => $settings['payment_settings']['khalti_public_key'],
                'amount' => (int)($pricing['final_price'] * 100), // Convert to integer paisa
                'product_identity' => 'plan_' . $plan->id,
                'product_name' => $plan->name,
                'product_url' => route('plans.index'),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    private function verifyKhaltiPayment($token, $amount, $settings)
    {
        try {
            $url = 'https://khalti.com/api/v2/payment/verify/';

            $data = [
                'token' => $token,
                'amount' => (int)($amount * 100), // Convert to integer paisa
            ];

            $headers = [
                'Authorization: Key ' . $settings['khalti_secret_key'],
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 200 && isset($result['state']['name'])) {
                return $result['state']['name'] === 'Completed';
            }

            // Handle already verified transaction
            if ($httpCode === 400 && isset($result['error_key']) && $result['error_key'] === 'already_verified') {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'token' => 'required|string'
        ]);

        try {
            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();
            $settings = getPaymentGatewaySettings();

            $isValid = $this->verifyKhaltiPayment($request->token, $request->amount, $settings['payment_settings']);

            if ($isValid) {

                $invoice->createPaymentRecord($request->amount, 'khalti', $request->token);

                return back()->with('success', __('Payment successful'));
            }

            return back()->withErrors(['error' => __('Payment verification failed')]);

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
                ->whereIn('key', ['khalti_public_key', 'khalti_secret_key', 'is_khalti_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['khalti_public_key']) || $paymentSettings['is_khalti_enabled'] !== '1') {
                return response()->json(['error' => 'Khalti payment not configured'], 400);
            }

            return response()->json([
                'success' => true,
                'public_key' => $paymentSettings['khalti_public_key'],
                'amount' => (int)($request->amount * 100), // Convert to paisa
                'product_identity' => 'invoice_' . $invoice->id,
                'product_name' => 'Invoice Payment - ' . $invoice->invoice_number,
                'product_url' => route('invoice.payment', $invoice->payment_token),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $token = $request->input('token');
            $amount = $request->input('amount');
            $invoiceToken = $request->input('invoice_token');

            if ($token && $amount && $invoiceToken) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    // Get payment settings for verification
                    $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                        ->whereIn('key', ['khalti_secret_key'])
                        ->pluck('value', 'key')
                        ->toArray();

                    // Verify payment with Khalti
                    $isValid = $this->verifyKhaltiPayment($token, $amount / 100, $paymentSettings); // Convert from paisa

                    if ($isValid) {
                        $invoice->createPaymentRecord($amount / 100, 'khalti', $token);

                        return redirect()->route('invoice.payment', $invoice->payment_token)
                            ->with('success', __('Payment successful'));
                    }
                }
            }

            return redirect()->route('invoice.payment', 'invalid')
                ->with('error', 'Payment verification failed.');

        } catch (\Exception $e) {
            return redirect()->route('invoice.payment', 'invalid')
                ->with('error', 'Payment processing failed.');
        }
    }
}
