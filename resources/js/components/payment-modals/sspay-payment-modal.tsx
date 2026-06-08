import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Loader } from '@/components/ui/loader';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';

interface SSPayPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
}

export function SSPayPaymentModal({ isOpen, onClose, invoice, amount }: SSPayPaymentModalProps) {
  const { t } = useTranslation();
  const [loading, setLoading] = useState(false);

  const handlePayment = async () => {
    setLoading(true);

    try {
      console.log('🔍 Starting SSPay payment request...');

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const response = await fetch(route('sspay.create-invoice-payment'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          invoice_token: invoice.payment_token,
          amount: amount,
        }),
      });


      const responseText = await response.text();
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: Server returned HTML instead of JSON`);
      }

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {

        throw new Error('Server returned invalid JSON response');
      }

      if (data.success && data.simulate_payment) {
        // Simulate SSPay payment processing
        setTimeout(() => {
          router.post(route('invoice.payment.process', invoice.payment_token), {
            payment_method: 'sspay',
            invoice_token: invoice.payment_token,
            amount: amount,
            status_id: '1',
            order_id: data.order_id
          }, {
            onSuccess: () => {
              // Toast will be shown by backend flash message
              onClose();
            },
            onError: (errors) => {
              toast.error(Object.values(errors).join(', '));
              setLoading(false);
            }
          });
        }, 2000);
      } else {
        throw new Error(data.error || t('Payment creation failed'));
      }
    } catch (error: any) {
      toast.error(error.message || t('Payment failed'));
      setLoading(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <span className="text-2xl">💳</span>
            {t('SSPay Payment')}
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

          <div className="bg-yellow-50 p-3 rounded-lg border border-yellow-200">
            <p className="text-sm text-yellow-800">
              <strong>{t('Test Mode')}:</strong> {t('This will simulate SSPay payment processing')}
            </p>
          </div>

          <div className="flex gap-3">
            <Button
              onClick={handlePayment}
              disabled={loading}
              className="flex-1 bg-blue-600 hover:bg-blue-700"
            >
              {loading ? (
                <>
                  <Loader className="mr-2 h-4 w-4" />
                  {t('Processing...')}
                </>
              ) : (
                t('Pay with SSPay (Test)')
              )}
            </Button>
            <Button variant="outline" onClick={onClose} disabled={loading}>
              {t('Cancel')}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
