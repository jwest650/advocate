<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PaystackPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'payment_id' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['paystack_secret_key'])) {
                return back()->withErrors(['error' => __('Paystack not configured')]);
            }

            // Verify payment with Paystack API
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $validated['payment_id'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $settings['payment_settings']['paystack_secret_key'],
                    "Cache-Control: no-cache",
                ],
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response, true);

            if ($result['status'] && $result['data']['status'] === 'success') {
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'paystack',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['payment_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'paystack');
        }
    }

    public function processInvoicePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice_token' => 'required|string',
                'payment_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
            ]);

            $invoice = \App\Models\Invoice::where('payment_token', $validated['invoice_token'])->firstOrFail();
            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->pluck('value', 'key')
                ->toArray();

            if (($paymentSettings['is_paystack_enabled'] ?? '0') !== '1') {
                return back()->withErrors(['error' => __('Paystack payment method is not enabled')]);
            }

            $secretKey = $paymentSettings['paystack_secret_key'] ?? null;
            if (!$secretKey) {
                return back()->withErrors(['error' => __('Payment method not configured. Please contact support.')]);
            }

            // Verify payment with Paystack API
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $validated['payment_id'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $secretKey,
                    "Cache-Control: no-cache",
                ],
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response, true);

            if ($result['status'] && $result['data']['status'] === 'success') {
                $invoice->createPaymentRecord($validated['amount'], 'paystack', $validated['payment_id']);
                return redirect()->route('invoice.payment', $validated['invoice_token'])->with('success', __('Payment successful'));
            }

            return back()->withErrors(['error' => __('Payment verification failed. Please try again.')]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors(['error' => __('Invoice not found. Please check the link and try again.')]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
        }
    }
}
