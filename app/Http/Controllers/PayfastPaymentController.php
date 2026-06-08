<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOrder;
use Illuminate\Http\Request;

class PayfastPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
                'billing_cycle' => 'required|in:monthly,yearly',
                'coupon_code' => 'nullable|string',
                'customer_details' => 'required|array',
                'customer_details.firstName' => 'required|string',
                'customer_details.lastName' => 'required|string',
                'customer_details.email' => 'required|email',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'error' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        try {
            $settings = getPaymentMethodConfig('payfast');
            $isLive = ($settings['mode'] ?? 'sandbox') === 'live';

            if (!$settings['merchant_id'] || !$settings['merchant_key']) {
                return response()->json(['success' => false, 'error' => __('PayFast not configured')]);
            }

            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);

            if ($pricing['final_price'] < 5.00) {
                return response()->json(['success' => false, 'error' => __('Minimum amount is R5.00')]);
            }

            $paymentId = 'pf_' . $plan->id . '_' . time() . '_' . uniqid();

            createPlanOrder([
                'user_id' => auth()->id(),
                'plan_id' => $validated['plan_id'],
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => 'payfast',
                'coupon_code' => $validated['coupon_code'] ?? null,
                'payment_id' => $paymentId,
                'status' => 'pending'
            ]);

            $data = [
                'merchant_id' => $settings['merchant_id'],
                'merchant_key' => $settings['merchant_key'],
                'return_url' => route('payfast.success'),
                'cancel_url' => route('plans.index'),
                'notify_url' => route('payfast.callback'),
                'name_first' => $validated['customer_details']['firstName'],
                'name_last' => $validated['customer_details']['lastName'],
                'email_address' => $validated['customer_details']['email'],
                'm_payment_id' => $paymentId,
                'amount' => number_format($pricing['final_price'], 2, '.', ''),
                'item_name' => $plan->name,
            ];

            $passphrase = $settings['passphrase'] ?? '';
            $signature = $this->generateSignature($data, $passphrase);
            $data['signature'] = $signature;

            $htmlForm = '';
            foreach ($data as $name => $value) {
                $htmlForm .= '<input name="' . $name . '" type="hidden" value="' . $value . '" />';
            }

            $endpoint = $isLive
                ? 'https://www.payfast.co.za/eng/process'
                : 'https://sandbox.payfast.co.za/eng/process';

            return response()->json([
                'success' => true,
                'inputs' => $htmlForm,
                'action' => $endpoint
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => __('Payment failed: ') . $e->getMessage()]);
        }
    }

    public function generateSignature($data, $passPhrase = null)
    {
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }
        return md5($getString);
    }

    public function callback(Request $request)
    {
        try {
            // Validate IP address (only for live mode)
            $settings = getPaymentMethodConfig('payfast');

            // Get callback data
            $pfData = $request->all();
            $paymentId = $pfData['m_payment_id'] ?? null;
            $paymentStatus = $pfData['payment_status'] ?? null;

            if (!$paymentId) {
                return response(__('Missing payment ID'), 400);
            }

            // Find the plan order
            $planOrder = PlanOrder::where('payment_id', $paymentId)->first();

            if (!$planOrder) {
                return response(__('Order not found'), 404);
            }

            // Verify signature
            if (!$this->verifyPayfastSignature($pfData, $settings['passphrase'] ?? '')) {
                return response(__('Invalid signature'), 400);
            }

            // Verify amount
            if (!$this->verifyAmount($pfData, $planOrder)) {
                return response(__('Amount mismatch'), 400);
            }

            // Process payment based on status
            if ($paymentStatus === 'COMPLETE') {
                if ($planOrder->status === 'pending') {
                    // Update order status
                    $planOrder->update([
                        'status' => 'approved',
                        'processed_at' => now()
                    ]);

                    // Assign plan to user
                    $user = $planOrder->user;
                    $plan = $planOrder->plan;
                    $expiresAt = $planOrder->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth();

                    $user->update([
                        'plan_id' => $plan->id,
                        'plan_expires_at' => $expiresAt,
                    ]);
                }
            } else {
                if (in_array($paymentStatus, ['CANCELLED', 'FAILED'])) {
                    $planOrder->update(['status' => 'rejected']);
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }


    private function verifyPayfastSignature($pfData, $passphrase = '')
    {
        $signature = $pfData['signature'] ?? '';
        unset($pfData['signature']);

        $expectedSignature = $this->generateSignature($pfData, $passphrase);

        return hash_equals($expectedSignature, $signature);
    }

    private function verifyAmount($pfData, $planOrder)
    {
        $receivedAmount = floatval($pfData['amount_gross'] ?? 0);
        $expectedAmount = floatval($planOrder->final_price);

        // Allow small floating point differences
        return abs($receivedAmount - $expectedAmount) < 0.01;
    }

    public function success(Request $request)
    {
        try {
            // Try different parameter names PayFast might use
            $paymentId = $request->get('m_payment_id') ?? $request->get('pf_payment_id') ?? $request->get('payment_id');

            if (!$paymentId && auth()->check()) {
                // If no payment ID, find the most recent pending order for this user
                $planOrder = PlanOrder::where('user_id', auth()->id())
                    ->where('payment_method', 'payfast')
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                $planOrder = PlanOrder::where('payment_id', $paymentId)->first();
            }

            if ($planOrder && $planOrder->status === 'pending') {
                // Process payment success using helper function like PayPal
                processPaymentSuccess([
                    'user_id' => $planOrder->user_id,
                    'plan_id' => $planOrder->plan_id,
                    'billing_cycle' => $planOrder->billing_cycle,
                    'payment_method' => 'payfast',
                    'coupon_code' => $planOrder->coupon_code,
                    'payment_id' => $planOrder->payment_id,
                ]);

                return redirect()->route('plans.index')->with('success', __('Payment successful and plan activated'));
            }

            return redirect()->route('plans.index')->with('success', __('Payment completed successfully'));

        } catch (\Exception $e) {
            return handlePaymentError($e, 'payfast');
        }
    }
    public function processInvoicePayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'm_payment_id' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $validated['invoice_token'])->firstOrFail();

            $invoice->createPaymentRecord($validated['amount'], 'payfast', $validated['m_payment_id']);

            return redirect()->route('invoice.payment', $validated['invoice_token'])
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
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['payfast_merchant_id', 'payfast_merchant_key', 'payfast_passphrase', 'payfast_mode', 'is_payfast_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['payfast_merchant_id']) || $paymentSettings['is_payfast_enabled'] !== '1') {
                return response()->json(['error' => 'PayFast payment not configured'], 400);
            }

            // Use user-entered amount
            $amount = $request->amount;

            // Debug log to verify amount

            $paymentId = 'inv_' . $invoice->id . '_' . time() . '_' . uniqid();
            $isLive = ($paymentSettings['payfast_mode'] ?? 'sandbox') === 'live';

            $data = [
                'merchant_id' => $paymentSettings['payfast_merchant_id'],
                'merchant_key' => $paymentSettings['payfast_merchant_key'] ?? '',
                'return_url' => url('/payfast/invoice/success?token=' . $invoice->payment_token . '&amount=' . $amount . '&m_payment_id=' . $paymentId),
                'cancel_url' => route('invoice.payment', $invoice->payment_token),
                'notify_url' => route('payfast.invoice.callback'),
                'name_first' => $invoice->client->first_name ?? 'Customer',
                'name_last' => $invoice->client->last_name ?? '',
                'email_address' => $invoice->client->email ?? 'customer@example.com',
                'm_payment_id' => $paymentId,
                'amount' => number_format($amount, 2, '.', ''),
                'item_name' => 'Invoice #' . $invoice->invoice_number,
            ];

            $passphrase = $paymentSettings['payfast_passphrase'] ?? '';
            $signature = $this->generateSignature($data, $passphrase);
            $data['signature'] = $signature;

            $htmlForm = '';
            foreach ($data as $name => $value) {
                $htmlForm .= '<input name="' . $name . '" type="hidden" value="' . $value . '" />';
            }

            $endpoint = $isLive
                ? 'https://www.payfast.co.za/eng/process'
                : 'https://sandbox.payfast.co.za/eng/process';

            return response()->json([
                'success' => true,
                'inputs' => $htmlForm,
                'action' => $endpoint,
                'payment_id' => $paymentId
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {

        try {
            $token = $request->get('token');
            $paymentId = $request->get('m_payment_id');

            if ($paymentId && str_starts_with($paymentId, 'inv_')) {
                $parts = explode('_', $paymentId);
                if (count($parts) >= 3) {
                    $invoiceId = $parts[1];
                    $invoice = \App\Models\Invoice::find($invoiceId);

                    // Get amount from URL parameter
                    $paymentAmount = $request->get('amount');

                    if ($invoice && $paymentAmount) {
                        $invoice->createPaymentRecord($paymentAmount, 'payfast', $paymentId);

                        return redirect()->route('invoice.payment', $invoice->payment_token)
                            ->with('success', __('Payment successful'));
                    }
                }
            }

            if ($token) {
                $invoice = \App\Models\Invoice::where('payment_token', $token)->first();
                if ($invoice) {
                    // Try to get amount from PayFast parameters or use a default transaction ID
                    $paymentAmount = $request->get('amount_gross') ? (float)$request->get('amount_gross') : null;
                    $transactionId = 'payfast_' . time();

                    // If no amount from PayFast, this might be a redirect without proper callback
                    if (!$paymentAmount) {
                        \Log::warning('PayFast success: No amount_gross provided, payment may need manual verification', [
                            'token' => $token,
                            'all_params' => $request->all()
                        ]);
                        return redirect()->route('invoice.payment', $token)
                            ->with('info', __('Payment is being processed. Please wait for confirmation.'));
                    }

                    $invoice->createPaymentRecord($paymentAmount, 'payfast', $transactionId);
                    return redirect()->route('invoice.payment', $token)
                        ->with('success', __('Payment successful'));
                }
            }

            return redirect()->route('home')
                ->with('error', __('Payment verification failed'));

        } catch (\Exception $e) {
            return redirect()->route('home')
                ->with('error', __('Payment processing failed'));
        }
    }

    public function invoiceCallback(Request $request)
    {


        try {
            $pfData = $request->all();
            $paymentId = $pfData['m_payment_id'] ?? null;
            $paymentStatus = $pfData['payment_status'] ?? null;

            if (!$paymentId || !str_starts_with($paymentId, 'inv_')) {
                return response('Invalid payment ID', 400);
            }

            // Extract invoice ID from payment ID
            $parts = explode('_', $paymentId);
            if (count($parts) < 3) {
                return response('Invalid payment ID format', 400);
            }

            $invoiceId = $parts[1];
            $invoice = \App\Models\Invoice::find($invoiceId);

            if (!$invoice) {
                return response('Invoice not found', 404);
            }

            // Get payment settings for verification
            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['payfast_passphrase'])
                ->pluck('value', 'key')
                ->toArray();

            // Verify signature
            if (!$this->verifyPayfastSignature($pfData, $paymentSettings['payfast_passphrase'] ?? '')) {
                return response('Invalid signature', 400);
            }

            // Process payment based on status
            if ($paymentStatus === 'COMPLETE') {
                // Use amount from PayFast callback
                $amount = floatval($pfData['amount_gross'] ?? 0);
                $transactionId = $pfData['pf_payment_id'] ?? $paymentId;

                if ($amount > 0) {
                    // Debug log to verify amount being recorded

                    // Create payment record with PayFast amount
                    $invoice->createPaymentRecord($amount, 'payfast', $transactionId);
                } else {
                    return response('Invalid amount', 400);
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }
}
