<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class PaiementPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'transaction_id' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['paiement_merchant_id'])) {
                return back()->withErrors(['error' => __('Paiement Pro not configured')]);
            }

            if ($validated['status'] === 'success') {
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'paiement',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['transaction_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'paiement');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null, $validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['paiement_merchant_id'])) {
                return response()->json(['error' => __('Paiement Pro not configured')], 400);
            }

            // Check if Paiement Pro is enabled
            if (!isset($settings['payment_settings']['is_paiement_enabled']) || $settings['payment_settings']['is_paiement_enabled'] !== '1') {
                return response()->json(['error' => __('Paiement Pro is temporarily unavailable')], 400);
            }

            $user = auth()->user();
            $transactionId = 'REF-' . time();

            $data = [
                'merchantId' => $settings['payment_settings']['paiement_merchant_id'],
                'amount' => (int)($pricing['final_price'] * 549),
                'description' => $plan->name,
                'channel' => 'CARD',
                'countryCurrencyCode' => '952',
                'referenceNumber' => $transactionId,
                'customerEmail' => $user->email,
                'customerFirstName' => $user->name ?? 'Customer',
                'customerLastname' => $user->lastname ?? 'User',
                'customerPhoneNumber' => $user->phone ?? '01234567',
                'notificationURL' => route('paiement.callback'),
                'returnURL' => route('paiement.success'),
                'returnContext' => json_encode(['plan_id' => $plan->id, 'user_id' => $user->id])
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $responseData = json_decode($response, true);
                return response()->json([
                    'success' => true,
                    'payment_response' => $responseData,
                    'transaction_id' => $transactionId
                ]);
            }

            return response()->json(['error' => __('Payment initialization failed')], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function redirect(Request $request)
    {
        try {
            $encodedData = $request->input('data');

            if (!$encodedData) {
                return redirect()->route('plans.index')->with('error', __('Invalid payment data'));
            }

            $decodedData = base64_decode($encodedData);
            if (!$decodedData) {
                return redirect()->route('plans.index')->with('error', __('Invalid payment data'));
            }

            $data = json_decode($decodedData, true);
            if (!$data || !is_array($data)) {
                return redirect()->route('plans.index')->with('error', __('Invalid payment data'));
            }

            return view('payment.paiement-redirect', compact('data'));

        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment redirect failed'));
        }
    }

    private function generatePaymentForm($data)
    {
        $form = '<form id="paiement-form" method="POST" action="https://www.paiementpro.net/webservice/onlinepayment/init/">';
        foreach ($data as $key => $value) {
            $form .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
        }
        $form .= '</form>';
        $form .= '<script>document.getElementById("paiement-form").submit();</script>';

        return $form;
    }

    public function success(Request $request)
    {
        try {
            $returnContext = $request->input('returnContext');
            $reference = $request->input('referenceNumber');
            
            if ($returnContext) {
                $context = json_decode($returnContext, true);
                
                if (isset($context['plan_id']) && isset($context['user_id'])) {
                    $plan = Plan::find($context['plan_id']);
                    $user = User::find($context['user_id']);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => 'monthly',
                            'payment_method' => 'paiement',
                            'payment_id' => $reference,
                        ]);

                        return redirect()->route('plans.index')->with('success', __('Payment completed successfully!'));
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
            $returnContext = $request->input('returnContext');
            $reference = $request->input('referenceNumber');
            $status = $request->input('status');

            if ($returnContext && $status === 'success') {
                $context = json_decode($returnContext, true);
                
                if (isset($context['plan_id']) && isset($context['user_id'])) {
                    $plan = Plan::find($context['plan_id']);
                    $user = User::find($context['user_id']);

                    if ($plan && $user) {
                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => 'monthly',
                            'payment_method' => 'paiement',
                            'payment_id' => $reference,
                        ]);
                    }
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }
    public function processInvoicePayment(Request $request)
    {
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'transaction_id' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            // Check if Paiement Pro is configured for this user
            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['paiement_merchant_id', 'is_paiement_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['paiement_merchant_id']) || $paymentSettings['is_paiement_enabled'] !== '1') {
                return back()->withErrors(['error' => __('Paiement Pro payment method is not enabled or configured')]);
            }

            if ($request->status === 'success') {
                \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $request->amount,
                    'payment_method' => 'paiement',
                    'payment_date' => now(),
                    'transaction_id' => $request->transaction_id,
                    'status' => 'completed',
                    'created_by' => $invoice->created_by
                ]);

                // Update invoice status
                $totalPaid = $invoice->payments()->sum('amount');
                if ($totalPaid >= $invoice->total_amount) {
                    $invoice->update(['status' => 'paid']);
                }
                  $invoice->createPaymentRecord(
                            $request->amount,
                            'paiement',
                             $request->transaction_id
                        );

                return redirect()->route('invoice.payment', $invoice->payment_token)
                    ->with('success', __('Payment successful!'));
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
        $request->validate([
            'invoice_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $invoice = \App\Models\Invoice::where('payment_token', $request->invoice_token)->firstOrFail();

            $paymentSettings = \App\Models\PaymentSetting::where('user_id', $invoice->created_by)
                ->whereIn('key', ['paiement_merchant_id', 'is_paiement_enabled'])
                ->pluck('value', 'key')
                ->toArray();

            if (empty($paymentSettings['paiement_merchant_id']) || $paymentSettings['is_paiement_enabled'] !== '1') {
                return response()->json(['error' => 'Paiement Pro payment not configured'], 400);
            }

            $transactionId = 'INV-' . $invoice->id . '-' . time();
            $client = $invoice->client;

            $data = [
                'merchantId' => $paymentSettings['paiement_merchant_id'],
                'amount' => (int)($request->amount * 549),
                'description' => 'Invoice #' . $invoice->invoice_number,
                'channel' => 'CARD',
                'countryCurrencyCode' => '952',
                'referenceNumber' => $transactionId,
                'customerEmail' => $client->email ?? 'customer@example.com',
                'customerFirstName' => $client->name ?? 'Customer',
                'customerLastname' => 'User',
                'customerPhoneNumber' => $client->phone ?? '01234567',
                'notificationURL' => route('paiement.invoice.callback'),
                'returnURL' => route('paiement.invoice.success'),
                'returnContext' => json_encode(['invoice_id' => $invoice->id, 'amount' => $request->amount])
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $responseData = json_decode($response, true);
                return response()->json([
                    'success' => true,
                    'payment_response' => $responseData,
                    'transaction_id' => $transactionId
                ]);
            }

            return response()->json(['error' => 'Payment initialization failed'], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function invoiceSuccess(Request $request)
    {
        try {
            $returnContext = $request->input('returnContext');
            $reference = $request->input('referenceNumber');
            
            if ($returnContext) {
                $context = json_decode($returnContext, true);
                
                if (isset($context['invoice_id']) && isset($context['amount'])) {
                    $invoice = \App\Models\Invoice::find($context['invoice_id']);

                    if ($invoice) {
                        $invoice->createPaymentRecord(
                            $context['amount'],
                            'paiement',
                            $reference
                        );

                        return redirect()->route('invoice.payment', $invoice->payment_token)
                            ->with('success', 'Payment completed successfully!');
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

    public function invoiceCallback(Request $request)
    {
        try {
            $returnContext = $request->input('returnContext');
            $reference = $request->input('referenceNumber');
            $status = $request->input('status');

            if ($returnContext && $status === 'success') {
                $context = json_decode($returnContext, true);
                
                if (isset($context['invoice_id']) && isset($context['amount'])) {
                    $invoice = \App\Models\Invoice::find($context['invoice_id']);

                    if ($invoice) {
                        $invoice->createPaymentRecord(
                            $context['amount'],
                            'paiement',
                            $reference
                        );
                    }
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('ERROR', 500);
        }
    }


}
