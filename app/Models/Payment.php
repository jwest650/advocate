<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\AutoApplyPermissionCheck;

class Payment extends BaseModel
{
    use HasFactory, AutoApplyPermissionCheck;

    protected $fillable = [
        'created_by',
        'invoice_id',
        'payment_method',
        'amount',
        'payment_date',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function booted()
    {
        // Removed global scope to use AutoApplyPermissionCheck trait instead

        static::created(function ($payment) {
          
            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            } else {
                \Log::warning('Payment created but invoice not found', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id
                ]);
            }
        });

        static::updated(function ($payment) {
            \Log::info('Payment model updated event triggered', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'amount' => $payment->amount
            ]);
            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            } else {
                \Log::warning('Payment updated but invoice not found', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id
                ]);
            }
        });

        static::deleted(function ($payment) {

            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            } else {
                \Log::warning('Payment deleted but invoice not found', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id
                ]);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client()
    {
        return $this->hasOneThrough(Client::class, Invoice::class, 'id', 'id', 'invoice_id', 'client_id');
    }

    public function getPaymentMethodDisplayAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'check' => 'Check',
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'online' => 'Online Payment',
            'bank' => 'Bank Transfer',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'razorpay' => 'Razorpay',
            'paystack' => 'Paystack',
            'flutterwave' => 'Flutterwave',
            'paytabs' => 'PayTabs',
            'skrill' => 'Skrill',
            'coingate' => 'CoinGate',
            'payfast' => 'PayFast',
            'tap' => 'Tap',
            'xendit' => 'Xendit',
            'paytr' => 'PayTR',
            'mollie' => 'Mollie',
            'toyyibpay' => 'ToyyibPay',
            'iyzipay' => 'Iyzipay',
            'benefit' => 'Benefit',
            'ozow' => 'Ozow',
            'easebuzz' => 'Easebuzz',
            'authorizenet' => 'Authorize.Net',
            'fedapay' => 'FedaPay',
            'payhere' => 'PayHere',
            'cinetpay' => 'CinetPay',
            'paiement' => 'Paiement Pro',
            'yookassa' => 'YooKassa',
            'aamarpay' => 'AamarPay',
            'midtrans' => 'Midtrans',
            'paymentwall' => 'PaymentWall',
            'sspay' => 'SSPay',
            'cashfree' => 'Cashfree',
            'khalti' => 'Khalti',
            'nepalste' => 'NepalSte',
            default => ucfirst($this->payment_method)
        };
    }
}
