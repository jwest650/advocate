<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class XenditPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
               $validated = validatePaymentRequest($request);


        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['xendit_api_key'])) {
                return response()->json(['error' => __('Xendit not configured')], 400);
            }

            $user = auth()->user();
            $externalId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            $invoiceData = [
                'external_id' => $externalId,
                'amount' => $pricing['final_price'],
                'description' => 'Plan Subscription: ' . $plan->name,
                'invoice_duration' => 86400,
                'currency' => 'PHP',
                'customer' => [
                    'given_names' => $user->name ?? 'Customer',
                    'email' => $user->email
                ],
                'success_redirect_url' => route('xendit.success', [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? ''
                ]),
                'failure_redirect_url' => route('plans.index')
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($settings['payment_settings']['xendit_api_key'] . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $invoiceData);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['invoice_url'])) {
                    return response()->json([
                        'success' => true,
                        'payment_url' => $result['invoice_url'],
                        'external_id' => $externalId
                    ]);
                }
            }

            return response()->json(['error' => __('Payment creation failed')], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $planId = $request->input('plan_id');
            $userId = $request->input('user_id');
            $billingCycle = $request->input('billing_cycle', 'monthly');
            $couponCode = $request->input('coupon_code');

            if ($planId && $userId) {
                $plan = Plan::find($planId);
                $user = \App\Models\User::find($userId);

                if ($plan && $user) {
                    processPaymentSuccess([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'payment_method' => 'xendit',
                        'coupon_code' => $couponCode,
                        'payment_id' => $request->input('external_id', 'xendit_' . time()),
                    ]);

                    if (!auth()->check()) {
                        auth()->login($user);
                    }

                    return view('payment-success', [
                        'message' => __('Payment completed successfully and plan activated'),
                        'plan' => $plan,
                        'redirectUrl' => route('plans.index')
                    ]);
                }
            }

            return redirect()->route('plans.index')->with('error', __('Payment verification failed'));

        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment processing failed'));
        }
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'coupon_code' => 'nullable|string',
        ]);

        try {
            $settings = getPaymentMethodConfig('xendit');

            if (!$settings['enabled'] || !$settings['api_key']) {
                return back()->withErrors(['error' => __('Xendit payment method is not properly configured')]);
            }

            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $user = auth()->user();
            $externalId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            $invoiceData = [
                'external_id' => $externalId,
                'amount' => $pricing['final_price'],
                'description' => 'Plan Subscription - ' . $plan->name,
                'invoice_duration' => 86400,
                'currency' => 'IDR',
                'customer' => [
                    'given_names' => $user->name ?? 'Customer',
                    'email' => $user->email
                ],
                'success_redirect_url' => route('xendit.success', [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? ''
                ]),
                'failure_redirect_url' => route('plans.index')
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($settings['api_key'] . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $invoiceData);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['invoice_url'])) {
                    return redirect($result['invoice_url']);
                }
            }



            return back()->withErrors(['error' => __('Payment processing failed')]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Payment processing failed')]);
        }
    }

    public function processInvoicePayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $validated['invoice_token'])->firstOrFail();
            $settings = getPaymentMethodConfig('xendit', $invoice->created_by);



            if (!$settings['enabled']) {
                return back()->withErrors(['error' => __('Xendit payment method is not enabled')]);
            }

            if (!$settings['api_key']) {
                return back()->withErrors(['error' => __('Xendit API key is not configured')]);
            }

            $externalId = 'invoice_' . $invoice->id . '_' . time();

            $invoiceData = [
                'external_id' => $externalId,
                'amount' => $validated['amount'],
                'description' => 'Invoice Payment - #' . $invoice->invoice_number,
                'invoice_duration' => 86400,
                'currency' => 'PHP',
                'customer' => [
                    'given_names' => $invoice->client->name ?? 'Customer',
                    'email' => $invoice->client->email ?? 'customer@example.com'
                ],
                'success_redirect_url' => route('xendit.invoice.success', $validated['invoice_token']) . '?amount=' . $validated['amount'],
                'failure_redirect_url' => route('invoice.payment', $validated['invoice_token'])
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($settings['api_key'] . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $invoiceData);



            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['invoice_url'])) {
                    // Payment record will be created in callback/success


                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => true,
                            'redirect_url' => $result['invoice_url']
                        ]);
                    }

                    return redirect($result['invoice_url']);
                }
            } else {
                \Log::error('Xendit API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            return back()->withErrors(['error' => __('Payment processing failed')]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors(['error' => __('Invoice not found. Please check the link and try again.')]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
        }
    }

    public function callback(Request $request)
    {
        $externalId = $request->input('external_id');
        $status = $request->input('status');

        if ($status === 'PAID') {
            $planOrder = PlanOrder::where('payment_id', $externalId)->first();
            if ($planOrder && $planOrder->status === 'pending') {
                $planOrder->update(['status' => 'approved']);
                $planOrder->activateSubscription();
            }

            // Handle invoice payment
            if (str_starts_with($externalId, 'invoice_')) {
                $parts = explode('_', $externalId);
                if (count($parts) >= 2) {
                    $invoiceId = $parts[1];
                    $invoice = \App\Models\Invoice::find($invoiceId);
                    if ($invoice) {
                        // Get payment amount from Xendit callback
                        $paymentAmount = $request->input('amount') ?: $request->input('paid_amount');
                        if (!$paymentAmount) {
                            // Fallback: try to get from Xendit API
                            try {
                                $settings = getPaymentMethodConfig('xendit', $invoice->created_by);
                                if ($settings['api_key']) {
                                    $response = \Http::withHeaders([
                                        'Authorization' => 'Basic ' . base64_encode($settings['api_key'] . ':'),
                                    ])->get('https://api.xendit.co/v2/invoices?external_id=' . $externalId);
                                    
                                    if ($response->successful()) {
                                        $invoices = $response->json();
                                        if (!empty($invoices) && isset($invoices[0]['amount'])) {
                                            $paymentAmount = $invoices[0]['amount'];
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error('Xendit API call failed', ['error' => $e->getMessage()]);
                            }
                        }
                        
                        if ($paymentAmount) {
                            $invoice->createPaymentRecord($paymentAmount, 'xendit', $externalId);
                        }
                    }
                }
            }
        }

        return response('OK', 200);
    }

    public function invoiceSuccess(Request $request, $token)
    {
        try {
            $invoice = \App\Models\Invoice::where('payment_token', $token)->firstOrFail();
            
            // Get payment amount from Xendit API or use remaining amount as fallback
            $paymentAmount = $invoice->remaining_amount;
            
            // Get payment amount from URL parameter
            $paymentAmount = $request->input('amount');
            $externalId = $request->input('external_id');
            
            if (!$paymentAmount && $externalId) {
                // Try to get from Xendit API as fallback
                try {
                    $settings = getPaymentMethodConfig('xendit', $invoice->created_by);
                    if ($settings['api_key']) {
                        $response = \Http::withHeaders([
                            'Authorization' => 'Basic ' . base64_encode($settings['api_key'] . ':'),
                        ])->get('https://api.xendit.co/v2/invoices?external_id=' . $externalId);
                        
                        if ($response->successful()) {
                            $invoices = $response->json();
                            if (!empty($invoices) && isset($invoices[0]['amount'])) {
                                $paymentAmount = $invoices[0]['amount'];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Xendit API call failed in success', ['error' => $e->getMessage()]);
                }
            }
            
            $invoice->createPaymentRecord($paymentAmount, 'xendit', $externalId ?: 'xendit_' . time());
            
            return redirect()->route('invoice.payment', $token)
                ->with('success', __('Payment successful'));
                
        } catch (\Exception $e) {
            return redirect()->route('invoice.payment', $token)
                ->with('error', __('Payment processing failed'));
        }
    }
}
