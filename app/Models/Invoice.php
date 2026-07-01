<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\AutoApplyPermissionCheck;

class Invoice extends BaseModel
{
    use HasFactory, AutoApplyPermissionCheck;

    protected $fillable = [
        'created_by',
        'client_id',
        'case_id',
        'currency_id',
        'invoice_number',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'invoice_date',
        'due_date',
        'notes',
        'line_items',
        'payment_token',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'line_items' => 'array',
    ];

    protected static function booted()
    {
        // Removed global scope to use AutoApplyPermissionCheck trait instead

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = 'INV-' . date('Y') . '-' . $invoice->created_by . '-' . str_pad(
                    Invoice::where('created_by', $invoice->created_by)
                        ->whereYear('created_at', date('Y'))->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
            if (!$invoice->payment_token) {
                $invoice->payment_token = \Str::random(32);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(ClientBillingCurrency::class, 'currency_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'partial_paid' => 'Partial Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'sent' => 'default',
            'paid' => 'default',
            'partial_paid' => 'warning',
            'overdue' => 'destructive',
            'cancelled' => 'secondary',
            default => 'default'
        };
    }

    public function calculateTotals()
    {
        if (!$this->line_items) {
            $this->update([
                'subtotal' => 0,
                'total_amount' => $this->tax_amount ?? 0
            ]);
            return;
        }

        $subtotal = collect($this->line_items)->sum('amount');
        $total = $subtotal + ($this->tax_amount ?? 0);

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $total
        ]);
    }

    public function addLineItem($description, $quantity = 1, $rate = 0, $amount = null)
    {
        $amount = $amount ?? ($quantity * $rate);

        $lineItems = $this->line_items ?? [];
        $lineItems[] = [
            'description' => $description,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount
        ];

        $this->line_items = $lineItems;
        $this->save();
        $this->calculateTotals();
    }

    public function getPaymentUrlAttribute(): string
    {
        if (!$this->payment_token) {
            $this->payment_token = \Str::random(32);
            $this->save();
        }
        return route('invoice.payment', $this->payment_token);
    }

    public function getRemainingAmountAttribute(): float
    {
        $totalPaid = $this->payments()->sum('amount');
        return max(0, $this->total_amount - $totalPaid);
    }

    public function recalculateFromTimeEntries()
    {
        $this->calculateTotals();
        return $this;
    }

    /**
     * Update invoice status based on payments and due date
     */
    /**
     * Get payment settings for a specific gateway
     */
    public function getPaymentSettings($gateway)
    {
        $keyMap = [
            'yookassa' => ['yookassa_shop_id', 'yookassa_secret_key', 'is_yookassa_enabled'],
            'midtrans' => ['midtrans_secret_key', 'midtrans_client_key', 'midtrans_mode', 'is_midtrans_enabled'],
            'fedapay' => ['fedapay_secret_key', 'fedapay_mode', 'is_fedapay_enabled'],
            'paystack' => ['paystack_secret_key', 'is_paystack_enabled'],
            'paytabs' => ['paytabs_profile_id', 'paytabs_server_key', 'paytabs_region', 'is_paytabs_enabled'],
            'authorizenet' => ['authorizenet_merchant_id', 'authorizenet_transaction_key', 'is_authorizenet_enabled'],
            'skrill' => ['skrill_merchant_id', 'skrill_secret_word', 'is_skrill_enabled'],
            'razorpay' => [
                'razorpay_key',
                'razorpay_secret',
                'is_razorpay_enabled'
            ],
            'paymentwall' => [
                'paymentwall_public_key',
                'paymentwall_private_key',
                'is_paymentwall_enabled'
            ],
            'aamarpay' => [
                'aamarpay_store_id',
                'aamarpay_signature',
                'aamarpay_mode',
                'is_aamarpay_enabled'
            ],
        ];

        if (!isset($keyMap[$gateway])) {
            throw new \Exception('Unsupported payment gateway: ' . $gateway);
        }

        return \App\Models\PaymentSetting::where('user_id', $this->created_by)
            ->whereIn('key', $keyMap[$gateway])
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Create payment record if it doesn't exist
     */
    public function createPaymentRecord($amount, $paymentMethod, $transactionId)
    {
        \Log::info('createPaymentRecord called', [
            'invoice_id' => $this->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'invoice_total' => $this->total_amount,
             'current_paid' => $this->payments()->sum('amount'),
            'remaining_amount' => $this->remaining_amount,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $existingPayment = \App\Models\Payment::where('invoice_id', $this->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$existingPayment) {
            $payment = \App\Models\Payment::create([
                'invoice_id' => $this->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'payment_date' => now(),
                'created_by' => $this->created_by
            ]);

            \Log::info('Payment record created', [
                'payment_id' => $payment->id,
                'amount_recorded' => $payment->amount
            ]);

            // updatePaymentStatus() is automatically called by Payment model's created event
        } else {
            \Log::info('Payment already exists, skipping creation', [
                'existing_payment_id' => $existingPayment->id,
                'existing_amount' => $existingPayment->amount
            ]);
        }

        return $existingPayment === null;
    }

    /**
     * Update invoice status based on payment amount
     */
    public function updatePaymentStatus()
    {
        $totalPaid = $this->payments()->sum('amount');
        $oldStatus = $this->status;

        \Log::info('updatePaymentStatus called', [
            'invoice_id' => $this->id,
            'total_paid' => $totalPaid,
            'invoice_total' => $this->total_amount,
            'current_status' => $oldStatus
        ]);

        if ($totalPaid >= $this->total_amount) {
            $this->update(['status' => 'paid']);
            \Log::info('Invoice status updated to paid', ['invoice_id' => $this->id]);
        } elseif ($totalPaid > 0) {
            $this->update(['status' => 'partial_paid']);
            \Log::info('Invoice status updated to partial_paid', ['invoice_id' => $this->id]);
        }
    }
}
