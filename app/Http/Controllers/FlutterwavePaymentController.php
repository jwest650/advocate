<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class FlutterwavePaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        try {
            $validated = validatePaymentRequest($request, [
                'payment_id' => 'required',
                'tx_ref' => 'required|string',
            ]);

            // Convert payment_id to string if it's numeric
            $validated['payment_id'] = (string) $validated['payment_id'];
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Validation failed: ' . $e->getMessage()]);
        }

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['flutterwave_secret_key'])) {
                return back()->withErrors(['error' => __('Flutterwave not configured')]);
            }

            // Verify payment with Flutterwave API
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . $validated['payment_id'] . "/verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $settings['payment_settings']['flutterwave_secret_key'],
                    "Content-Type: application/json",
                ],
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode !== 200) {
                return back()->withErrors(['error' => __('Payment verification failed - API error')]);
            }

            $result = json_decode($response, true);

            if (!$result) {
                return back()->withErrors(['error' => __('Payment verification failed - Invalid response')]);
            }

            if ($result['status'] === 'success' && $result['data']['status'] === 'successful') {
                // Check if payment amount matches expected amount
                $expectedAmount = $pricing['final_price'];
                $paidAmount = $result['data']['amount'];

                if (abs($paidAmount - $expectedAmount) > 0.01) {
                    return back()->withErrors(['error' => __('Payment amount verification failed')]);
                }

                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'flutterwave',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['payment_id'],
                ]);

                return back()->with('success', __('Payment successful! Your plan has been activated.'));
            }

            return back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'flutterwave');
        }
    }

    public function processInvoicePayment(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|string',
                'tx_ref' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'invoice_token' => 'required|string'
            ]);

            $invoice = Invoice::where('payment_token', $request->invoice_token)->firstOrFail();
            
            // Get Flutterwave settings for this invoice creator
            $settings = PaymentSetting::where('user_id', $invoice->created_by)
                ->pluck('value', 'key')
                ->toArray();

            if (!isset($settings['flutterwave_secret_key']) || empty($settings['flutterwave_secret_key'])) {
                return back()->withErrors(['error' => __('Flutterwave not configured')]);
            }

            // Verify payment with Flutterwave API
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . $request->payment_id . "/verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $settings['flutterwave_secret_key'],
                    "Content-Type: application/json",
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode !== 200) {
                return back()->withErrors(['error' => __('Payment verification failed')]);
            }

            $result = json_decode($response, true);

            if (!$result || $result['status'] !== 'success' || $result['data']['status'] !== 'successful') {
                return back()->withErrors(['error' => __('Payment verification failed')]);
            }

            // Verify amount
            $paidAmount = $result['data']['amount'];
            if (abs($paidAmount - $request->amount) > 0.01) {
                return back()->withErrors(['error' => __('Payment amount mismatch')]);
            }

            $invoice->createPaymentRecord($request->amount, 'flutterwave', $request->payment_id);

            return back()->with('success', __('Payment successful!'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors(['error' => __('Invoice not found. Please check the link and try again.')]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
        }
    }
}
