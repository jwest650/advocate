<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class EasebuzzPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'easepayid' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['easebuzz_merchant_key'])) {
                return back()->withErrors(['error' => __('Easebuzz not configured')]);
            }

            if ($validated['status'] === 'success') {
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'easebuzz',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['easepayid'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'easebuzz');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
                $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['easebuzz_merchant_key']) || !isset($settings['payment_settings']['easebuzz_salt_key'])) {
                return response()->json(['error' => __('Easebuzz not configured')], 400);
            }

            // Include Easebuzz library
            require_once app_path('Libraries/Easebuzz/easebuzz_payment_gateway.php');

            $user = auth()->user();
            $txnid = 'plan_' . $plan->id . '_' . $user->id . '_' . time();
            $environment = $settings['payment_settings']['easebuzz_environment'] === 'prod' ? 'prod' : 'test';

            // Initialize Easebuzz
            $easebuzz = new \Easebuzz(
                $settings['payment_settings']['easebuzz_merchant_key'],
                $settings['payment_settings']['easebuzz_salt_key'],
                $environment
            );

            $postData = [
                'txnid' => $txnid,
                'amount' => number_format($pricing['final_price'], 2, '.', ''),
                'productinfo' => $plan->name,
                'firstname' => $user->name ?? 'Customer',
                'email' => $user->email,
                'phone' => '9999999999',
                'surl' => route('easebuzz.success'),
                'furl' => route('plans.index'),
                'udf1' => $validated['billing_cycle'],
                'udf2' => $validated['coupon_code'] ?? '',
            ];

            // Use Easebuzz library to initiate payment
            $result = $easebuzz->initiatePaymentAPI($postData, false);

            $resultArray = json_decode($result, true);

            if ($resultArray && isset($resultArray['status']) && $resultArray['status'] == 1) {
                $accessKey = $resultArray['access_key'] ?? null;
                if ($accessKey) {
                    $baseUrl = $settings['payment_settings']['easebuzz_environment'] === 'prod'
                        ? 'https://pay.easebuzz.in'
                        : 'https://testpay.easebuzz.in';

                    return response()->json([
                        'success' => true,
                        'payment_url' => $baseUrl . '/pay/' . $accessKey,
                        'transaction_id' => $txnid
                    ]);
                }
            }

            return response()->json(['error' => 'Payment initialization failed'], 400);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            // Include Easebuzz library
            require_once app_path('Libraries/Easebuzz/easebuzz_payment_gateway.php');

            $settings = getPaymentGatewaySettings();
            $environment = $settings['payment_settings']['easebuzz_environment'] === 'prod' ? 'prod' : 'test';

            $easebuzz = new \Easebuzz(
                $settings['payment_settings']['easebuzz_merchant_key'],
                $settings['payment_settings']['easebuzz_salt_key'],
                $environment
            );

            // Verify payment response
            $result = $easebuzz->easebuzzResponse($request->all());
            $resultArray = json_decode($result, true);

            if ($resultArray && $resultArray['status'] == 1 && $request->input('status') === 'success') {
                $txnid = $request->input('txnid');
                $parts = explode('_', $txnid);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = Plan::find($planId);
                    $user = User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => $request->input('udf1', 'monthly'),
                            'payment_method' => 'easebuzz',
                            'payment_id' => $request->input('easepayid'),
                        ]);

                        // Log the user in if not already authenticated
                        if (!auth()->check()) {
                            auth()->login($user);
                        }

                        return redirect()->route('plans.index')->with('success', __('Payment completed successfully and plan activated'));
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
            $txnid = $request->input('txnid');
            $status = $request->input('status');

            if ($txnid && $status === 'success') {
                $parts = explode('_', $txnid);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = Plan::find($planId);
                    $user = \App\Models\User::find($userId);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => $request->input('udf1', 'monthly'),
                            'payment_method' => 'easebuzz',
                            'payment_id' => $request->input('easepayid'),
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }

    // Invoice payment methods based on plan implementation
    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'easepayid' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            if ($request->status === 'success') {

                $invoice->createPaymentRecord($request->amount, 'easebuzz', $request->easepayid);

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

            $settings = getPaymentGatewaySettings($invoice->created_by);

            if (!isset($settings['payment_settings']['easebuzz_merchant_key']) || !isset($settings['payment_settings']['easebuzz_salt_key'])) {
                return response()->json(['error' => __('Easebuzz not configured')], 400);
            }

            $libraryPath = app_path('Libraries/Easebuzz/easebuzz_payment_gateway.php');

            if (!file_exists($libraryPath)) {
                return response()->json(['error' => 'Easebuzz library not configured'], 500);
            }

            require_once $libraryPath;

            $txnid = 'invoice_' . $invoice->id . '_' . time();
            $environment = $settings['payment_settings']['easebuzz_environment'] === 'prod' ? 'prod' : 'test';

            $easebuzz = new \Easebuzz(
                $settings['payment_settings']['easebuzz_merchant_key'],
                $settings['payment_settings']['easebuzz_salt_key'],
                $environment
            );

            $postData = [
                'txnid' => $txnid,
                'amount' => number_format($request->amount, 2, '.', ''),
                'productinfo' => 'Invoice Payment - ' . $invoice->invoice_number,
                'firstname' => 'Customer',
                'email' => 'customer@example.com',
                'phone' => '9999999999',
                'surl' => route('easebuzz.invoice.success'),
                'furl' => route('invoice.payment', $invoice->payment_token),
                'udf1' => $invoice->payment_token,
                'udf2' => $request->amount,
            ];

            $result = $easebuzz->initiatePaymentAPI($postData, false);

            $resultArray = json_decode($result, true);

            if ($resultArray && isset($resultArray['status']) && $resultArray['status'] == 1) {
                $accessKey = $resultArray['access_key'] ?? null;
                if ($accessKey) {
                    $baseUrl = $settings['payment_settings']['easebuzz_environment'] === 'prod'
                        ? 'https://pay.easebuzz.in'
                        : 'https://testpay.easebuzz.in';

                    return response()->json([
                        'success' => true,
                        'payment_url' => $baseUrl . '/pay/' . $accessKey,
                        'transaction_id' => $txnid
                    ]);
                }
            }

            return response()->json(['error' => 'Payment initialization failed'], 400);

        } catch (\Exception $e) {

            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $invoiceToken = $request->input('udf1');
            $amount = $request->input('udf2');
            $status = $request->input('status');
            $easepayid = $request->input('easepayid');

            if (!$invoiceToken) {
                return redirect()->route('invoice.payment', 'invalid')
                    ->with('error', __('Invalid payment token'));
            }

            $invoice = \App\Models\Invoice::where('payment_token', $invoiceToken)->first();
            if (!$invoice) {
                return redirect()->route('invoice.payment', $invoiceToken)
                    ->with('error', __('Invoice not found'));
            }

            // Include Easebuzz library
            require_once app_path('Libraries/Easebuzz/easebuzz_payment_gateway.php');

            $settings = getPaymentGatewaySettings($invoice->created_by);
            $environment = $settings['payment_settings']['easebuzz_environment'] === 'prod' ? 'prod' : 'test';

            $easebuzz = new \Easebuzz(
                $settings['payment_settings']['easebuzz_merchant_key'],
                $settings['payment_settings']['easebuzz_salt_key'],
                $environment
            );

            // Verify payment response
            $result = $easebuzz->easebuzzResponse($request->all());
            $resultArray = json_decode($result, true);

            if ($resultArray && $resultArray['status'] == 1 && $status === 'success') {
                // Create payment record
                \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_method' => 'easebuzz',
                    'payment_date' => now(),
                    'transaction_id' => $easepayid,
                    'status' => 'completed',
                    'created_by' => $invoice->created_by
                ]);

                // Update invoice status if fully paid
                $totalPaid = $invoice->payments()->sum('amount');
                if ($totalPaid >= $invoice->total_amount) {
                    $invoice->update(['status' => 'paid']);
                }

                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('success', __('Payment successful'));
            }

            return redirect()->route('invoice.payment', $invoiceToken)
                ->with('error', __('Payment verification failed'));

        } catch (\Exception $e) {
            return redirect()->route('invoice.payment', $request->input('udf1', 'invalid'))
                ->with('error', __('Payment processing failed'));
        }
    }
}
