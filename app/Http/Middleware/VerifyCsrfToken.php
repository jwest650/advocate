<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        'easebuzz/create-invoice-payment',
        'easebuzz/*',
        'easebuzz/invoice/success',
        'mollie/create-invoice-payment',
        'mollie/*',
        'payhere/*',
        'payhere/invoice/success',
        'test-mollie',
        'skrill/*',
        'skrill/create-invoice-payment',
        'aamarpay/*',
        'payments/aamarpay/*',
        'ozow/*',
        'ozow/create-invoice-payment',
        'payments/ozow/*',
        'iyzipay/*',
        'payments/iyzipay/*',
        'invoice/pay/*',
    ];

    public function handle($request, \Closure $next)
    {
        // Skip CSRF for payment gateway routes completely
        if (str_contains($request->getPathInfo(), 'aamarpay') || str_contains($request->getPathInfo(), 'khalti') || str_contains($request->getPathInfo(), 'cashfree') || str_contains($request->getPathInfo(), 'sspay') || str_contains($request->getPathInfo(), 'ozow') || str_contains($request->getPathInfo(), 'iyzipay') || str_contains($request->getPathInfo(), 'payhere') || str_contains($request->getPathInfo(), 'invoice/pay')) {
            return $next($request);
        }

        // Disable CSRF for all payment gateway routes
        if ($request->is('skrill/*') ||
            $request->is('easebuzz/*') ||
            $request->is('mollie/*') ||
            $request->is('tap/*') ||
            $request->is('payhere/*') ||
            $request->is('cinetpay/*') ||
            $request->is('fedapay/*') ||
            $request->is('paytabs/*') ||
            $request->is('khalti/*') ||
            $request->is('paiement/*') ||
            $request->is('cashfree/*') ||
            $request->is('sspay/*') ||
            $request->is('ozow/*') ||
            $request->is('iyzipay/*') ||
            $request->is('payments/payhere/*') ||
            $request->is('payments/ozow/*') ||
            $request->is('payments/aamarpay/*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
