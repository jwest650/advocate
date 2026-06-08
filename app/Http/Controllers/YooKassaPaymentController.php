<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class YooKassaPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['yookassa_shop_id'])) {
                return response()->json(['error' => 'YooKassa not configured'], 400);
            }

            $client = new \YooKassa\Client();
            $client->setAuth((int)$settings['payment_settings']['yookassa_shop_id'], $settings['payment_settings']['yookassa_secret_key']);

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $user = auth()->user();

            $payment = $client->createPayment([
                'amount' => [
                    'value' => number_format($pricing['final_price'], 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('yookassa.success', [
                        'plan_id' => $plan->id,
                        'order_id' => $orderID,
                        'billing_cycle' => $validated['billing_cycle'],
                        'coupon_code' => $validated['coupon_code'] ?? null
                    ]),
                ],
                'capture' => true,
                'description' => 'Plan: ' . $plan->name,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'order_id' => $orderID
                ]
            ], uniqid('', true));

            if ($payment['confirmation']['confirmation_url'] != null) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $payment['confirmation']['confirmation_url'],
                    'payment_id' => $payment['id']
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
        try {
            $planId = $request->input('plan_id');
            $billingCycle = $request->input('billing_cycle');
            $couponCode = $request->input('coupon_code');
            $orderId = $request->input('order_id');

            if ($planId && $orderId) {
                $plan = Plan::find($planId);

                // Find user by session or create temporary assignment
                $user = null;
                if (auth()->check()) {
                    $user = auth()->user();
                } else {
                    // Try to find user from recent plan orders
                    $recentOrder = \App\Models\PlanOrder::where('payment_id', 'like', '%' . substr($orderId, -8))
                        ->where('created_at', '>=', now()->subHours(1))
                        ->first();
                    if ($recentOrder) {
                        $user = \App\Models\User::find($recentOrder->user_id);
                    }
                }

                if ($plan && $user) {
                    // Assign plan to user immediately
                    $user->plan_id = $plan->id;
                    $user->plan_expire_date = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
                    $user->save();

                    // Create plan order record
                    processPaymentSuccess([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'payment_method' => 'yookassa',
                        'coupon_code' => $couponCode,
                        'payment_id' => $orderId,
                    ]);

                    return redirect()->route('plans.index')->with('success', 'Payment successful and plan activated');
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
            $paymentId = $request->input('object.id');
            $status = $request->input('object.status');
            $metadata = $request->input('object.metadata');

            if ($paymentId && $status === 'succeeded' && $metadata) {
                $planId = $metadata['plan_id'];
                $userId = $metadata['user_id'];

                $plan = Plan::find($planId);
                $user = \App\Models\User::find($userId);

                if ($plan && $user) {
                    // Assign plan to user
                    $user->plan_id = $plan->id;
                    $user->plan_expire_date = $metadata['billing_cycle'] === 'yearly' ? now()->addYear() : now()->addMonth();
                    $user->save();

                    processPaymentSuccess([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $metadata['billing_cycle'] ?? 'monthly',
                        'payment_method' => 'yookassa',
                        'coupon_code' => $metadata['coupon_code'] ?? null,
                        'payment_id' => $paymentId,
                    ]);
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
            'payment_id' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $invoice->createPaymentRecord($request->amount, 'yookassa', $request->payment_id);

            return redirect()->route('invoice.payment', $invoice->payment_token)
                ->with('success', __('Payment successful'));

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

            $paymentSettings = $invoice->getPaymentSettings('yookassa');

            if (empty($paymentSettings['yookassa_shop_id']) || empty($paymentSettings['yookassa_secret_key']) || $paymentSettings['is_yookassa_enabled'] !== '1') {
                throw new \Exception('YooKassa payment not configured');
            }

            $client = new \YooKassa\Client();
            $client->setAuth((int)$paymentSettings['yookassa_shop_id'], $paymentSettings['yookassa_secret_key']);

            $orderId = 'invoice_' . $invoice->id . '_' . time();

            $payment = $client->createPayment([
                'amount' => [
                    'value' => number_format($request->amount, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('yookassa.invoice.success') . '?order_id=' . $orderId . '&invoice_token=' . $request->invoice_token . '&test=1',
                ],
                'capture' => true,
                'description' => 'Invoice Payment - ' . $invoice->invoice_number,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_token' => $request->invoice_token,
                    'order_id' => $orderId,
                    'amount' => $request->amount
                ]
            ], uniqid('', true));

            if ($payment['confirmation']['confirmation_url'] != null) {
                $result = [
                    'success' => true,
                    'redirect_url' => $payment['confirmation']['confirmation_url'],
                    'payment_id' => $payment['id']
                ];
            } else {
                throw new \Exception('Payment creation failed');
            }

            return response()->json($result);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $invoiceToken = $request->input('invoice_token');
            $isTest = $request->input('test');

            if ($orderId && $invoiceToken) {
                $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();

                if ($invoice) {
                    // Get payment amount from YooKassa API or use remaining amount as fallback
                    $paymentAmount = $invoice->remaining_amount;
                    
                    // Try to get the actual payment amount from YooKassa API
                    try {
                        $paymentSettings = $invoice->getPaymentSettings('yookassa');
                        if (!empty($paymentSettings['yookassa_shop_id']) && !empty($paymentSettings['yookassa_secret_key'])) {
                            $client = new \YooKassa\Client();
                            $client->setAuth((int)$paymentSettings['yookassa_shop_id'], $paymentSettings['yookassa_secret_key']);
                            
                            // Find payment by order_id in metadata
                            $payments = $client->getPayments(['limit' => 100]);
                            foreach ($payments->getItems() as $payment) {
                                if (isset($payment->metadata['order_id']) && $payment->metadata['order_id'] === $orderId) {
                                    $paymentAmount = (float)$payment->amount->value;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Fallback to remaining amount if API call fails
                    }
                    
                    $invoice->createPaymentRecord($paymentAmount, 'yookassa', $orderId);
                    return redirect()->route('invoice.payment', $invoice->payment_token)
                        ->with('success', __('Payment successful'));
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
            $paymentId = $request->input('object.id');
            $status = $request->input('object.status');
            $metadata = $request->input('object.metadata');

            if ($paymentId && $status === 'succeeded' && $metadata) {
                $invoiceId = $metadata['invoice_id'];
                $orderId = $metadata['order_id'];

                $invoice = \App\Models\Invoice::find($invoiceId);

                if ($invoice) {
                    // Get payment amount from metadata or payment object
                    $paymentAmount = isset($metadata['amount']) ? (float)$metadata['amount'] : $invoice->remaining_amount;
                    
                    // Try to get amount from the payment object in request
                    if (!isset($metadata['amount']) && $request->has('object.amount.value')) {
                        $paymentAmount = (float)$request->input('object.amount.value');
                    }
                    
                    $invoice->createPaymentRecord($paymentAmount, 'yookassa', $paymentId);
                    return response('OK', 200);
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('FAILED', 400);
        }
    }
}
