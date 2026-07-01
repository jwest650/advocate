import { useEffect, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';

interface FlutterwavePaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
  flutterwavePublicKey?: string;
}

declare global {
  interface Window {
    FlutterwaveCheckout?: any;
  }
}

export function FlutterwavePaymentModal({ isOpen, onClose, invoice, amount, flutterwavePublicKey }: FlutterwavePaymentModalProps) {
  const { t } = useTranslation();
  const initialized = useRef(false);

  useEffect(() => {
    if (!isOpen || !flutterwavePublicKey || initialized.current) return;

    const script = document.createElement('script');
    script.src = 'https://checkout.flutterwave.com/v3.js';
    script.async = true;

    script.onload = () => {
      initialized.current = true;

      window.FlutterwaveCheckout({
        public_key: flutterwavePublicKey,
        tx_ref: `inv_${invoice.id}_${Date.now()}`,
        amount: amount,
        currency: 'USD',
        payment_options: 'card,mobilemoney,ussd',
        customer: {
          email: invoice.client?.email || 'customer@example.com',
          phone_number: invoice.client?.phone || '',
          name: invoice.client?.name || 'Customer',
        },
        customizations: {
          title: `Invoice #${invoice.invoice_number}`,
          description: `Payment for Invoice #${invoice.invoice_number}`,
          logo: '',
        },
        callback: function (data: any) {
          if (data.status === 'successful') {
            router.post(route('invoice.payment.process', invoice.payment_token), {
              payment_method: 'flutterwave',
              invoice_token: invoice.payment_token,
              amount: amount,
              payment_id: String(data.transaction_id),
              tx_ref: data.tx_ref
            }, {
              onSuccess: () => {
                // Toast will be shown by backend flash message
                window.location.reload();
              },
              onError: (errors) => {
                toast.error(Object.values(errors).join(', '));
                onClose();
              }
            });
          } else {
            toast.error(t('Payment was not completed'));
            onClose();
          }
        },
        onclose: function () {
          onClose();
        },
      });
    };

    document.head.appendChild(script);

    return () => {
      if (document.head.contains(script)) {
        document.head.removeChild(script);
      }
    };
  }, [isOpen, flutterwavePublicKey, invoice, amount]);

  if (!flutterwavePublicKey) {
    return (
      <Dialog open={isOpen} onOpenChange={onClose}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{t('Flutterwave Payment')}</DialogTitle>
          </DialogHeader>
          <div className="p-4 text-center text-red-500">
            {t('Flutterwave not configured')}
          </div>
        </DialogContent>
      </Dialog>
    );
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <span className="text-2xl">💳</span>
            {t('Flutterwave Payment')}
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div className="bg-blue-50 p-4 rounded-lg">
            <div className="text-sm text-blue-600 font-medium">
              {t('Invoice')} #{invoice.invoice_number}
            </div>
            <div className="text-2xl font-bold text-blue-900">
             {formatCurrencyForCompany(amount.toFixed(2))}
            </div>
          </div>

          <div className="p-4 text-center">
            <p>{t('Redirecting to Flutterwave...')}</p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
