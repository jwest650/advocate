<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use CoinGate\Client;

class CoinGatePaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'coupon_code' => 'nullable|string'
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $user = auth()->user();

            // Get payment settings exactly like reference project
            $settings = getPaymentGatewaySettings();


            if (!$settings['payment_settings']['is_coingate_enabled'] || !$settings['payment_settings']['coingate_api_token']) {
                return redirect()->route('plans.index')->with('error', __('CoinGate payment is not available'));
            }

            if (!isset($settings['payment_settings']['coingate_api_token']) || empty($settings['payment_settings']['coingate_api_token'])) {
                return redirect()->route('plans.index')->with('error', __('CoinGate API token not configured'));
            }

            // Calculate price
            $price = $validated['billing_cycle'] === 'yearly' ? $plan->yearly_price : $plan->price;

            // Create plan order
            $orderId = time();
            $planOrder = PlanOrder::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => 'coingate',
                'coupon_code' => $validated['coupon_code'],
                'payment_id' => $orderId,
                'original_price' => $price,
                'final_price' => $price,
                'status' => 'pending'
            ]);

            // Use official CoinGate package
            $client = new Client(
                $settings['payment_settings']['coingate_api_token'],
                ($settings['payment_settings']['coingate_mode'] ?? 'sandbox') === 'sandbox'
            );

            $orderParams = [
                'order_id' => $orderId,
                'price_amount' => $price,
                'price_currency' => $settings['general_settings']['defaultCurrency'] ?? 'USD',
                'receive_currency' => $settings['general_settings']['defaultCurrency'] ?? 'USD',
                'callback_url' => route('coingate.callback'),
                'cancel_url' => route('plans.index'),
                'success_url' => route('coingate.callback'),
                'title' => 'Plan #' . $orderId,
            ];

            $orderResponse = $client->order->create($orderParams);

            if ($orderResponse && isset($orderResponse->payment_url)) {
                // Store in session like reference project
                session(['coingate_data' => $orderResponse]);

                // Store gateway response
                $planOrder->payment_id = $orderResponse->order_id;
                $planOrder->save();

                return Inertia::location($orderResponse->payment_url);
            } else {
                $planOrder->update(['status' => 'cancelled']);
                return redirect()->route('plans.index')->with('error', __('Payment initialization failed'));
            }

        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment failed: ') . $e->getMessage());
        }
    }

    public function processInvoicePayment(Request $request)
    {
        // Get token from request data or URL
        $token = $request->input('invoice_token') ?? $request->route('token');

        if (!$token) {
            return back()->withErrors(['error' => __('Invoice token is required')]);
        }

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $token)->firstOrFail();
            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->pluck('value', 'key')
                ->toArray();



            if (($paymentSettings['is_coingate_enabled'] ?? '0') !== '1') {
                return back()->withErrors(['error' => __('CoinGate payment method is not enabled')]);
            }

            $apiToken = $paymentSettings['coingate_api_token'] ?? null;
            if (!$apiToken) {
                return back()->withErrors(['error' => __('CoinGate API token is not configured')]);
            }


            $client = new Client(
                $apiToken,
                ($paymentSettings['coingate_mode'] ?? 'sandbox') === 'sandbox'
            );

            $orderId = 'invoice_' . $invoice->id . '_' . time();

            $amount = $request->input('amount', $invoice->remaining_amount);
            
            // Validate payment amount
            if ($amount > $invoice->remaining_amount) {
                return back()->withErrors(['error' => __('Payment amount cannot exceed remaining amount of :amount', ['amount' => $invoice->remaining_amount])]);
            }
            
            if ($amount <= 0) {
                return back()->withErrors(['error' => __('Payment amount must be greater than zero')]);
            }

            $orderParams = [
                'order_id' => $orderId,
                'price_amount' => $amount,
                'price_currency' => $invoice->currency->code ?? 'USD',
                'receive_currency' => $invoice->currency->code ?? 'USD',
                'callback_url' => route('coingate.callback'),
                'cancel_url' => route('invoice.payment', $token),
                'success_url' => route('coingate.callback'),
                'title' => 'Invoice Payment - ' . $invoice->invoice_number,
            ];

            $orderResponse = $client->order->create($orderParams);
            if ($orderResponse && isset($orderResponse->payment_url)) {
                session(['coingate_invoice_data' => [
                    'order_response' => $orderResponse,
                    'invoice_id' => $invoice->id,
                    'invoice_token' => $token,
                    'payment_amount' => $amount
                ]]);

                return redirect()->away($orderResponse->payment_url);
            } else {
                return back()->withErrors(['error' => __('Payment initialization failed')]);
            }

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
                ->whereIn('key', ['coingate_api_token', 'is_coingate_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['coingate_api_token']) || $paymentSettings['is_coingate_enabled'] !== '1') {
                return response()->json(['error' => 'CoinGate payment not configured'], 400);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => config('app.url') . '/coingate/invoice-form/' . $request->invoice_token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $coingateInvoiceData = session('coingate_invoice_data');
            if ($coingateInvoiceData) {
                $orderResponse = $coingateInvoiceData['order_response'];
                $orderId = is_object($orderResponse) ? $orderResponse->order_id : $orderResponse['order_id'];
                
                // Get payment amount from order response or fallback to stored amount
                $paymentAmount = is_object($orderResponse) ? $orderResponse->price_amount : $orderResponse['price_amount'];
                if (!$paymentAmount && isset($coingateInvoiceData['payment_amount'])) {
                    $paymentAmount = $coingateInvoiceData['payment_amount'];
                }

                $invoice = \App\Models\Invoice::findOrFail($coingateInvoiceData['invoice_id']);

                $invoice->createPaymentRecord($paymentAmount, 'coingate', $orderId);
                session()->forget('coingate_invoice_data');

                return redirect()->route('invoice.payment', $coingateInvoiceData['invoice_token'])
                    ->with('success', __('Payment successful'));
            }

            $user = auth()->user();
            $coingateData = session('coingate_data');

            if (!$coingateData) {
                return redirect()->route('plans.index')->with('error', __('Payment session expired'));
            }

            $orderId = is_object($coingateData) ? $coingateData->order_id : $coingateData['order_id'];
            $planOrder = PlanOrder::where('payment_id', $orderId)->first();

            if (!$planOrder) {
                return redirect()->route('plans.index')->with('error', 'Order not found');
            }

            $planOrder->update([
                'status' => 'approved',
                'processed_at' => now()
            ]);

            $planOrder->activateSubscription();
            session()->forget('coingate_data');

            return redirect()->route('plans.index')->with('success', __('Plan activated successfully!'));

        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment processing failed'));
        }
    }
}
