import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Loader } from '@/components/ui/loader';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';

interface MolliePaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
}

export function MolliePaymentModal({ isOpen, onClose, invoice, amount }: MolliePaymentModalProps) {
  const { t } = useTranslation();
  const [loading, setLoading] = useState(false);

  const handlePayment = async () => {

    setLoading(true);

    try {

      const payload = {
        invoice_token: invoice.payment_token,
        amount: amount,
      };


     const response = await fetch(route('mollie.create-invoice-payment'), {


        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });


      // Check if response is ok
      if (!response.ok) {
        const text = await response.text();
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        throw new Error('Server returned non-JSON response');
      }

      const data = await response.json();
      console.log('Response data:', data);

      if (data.success && data.payment_url) {
        window.location.href = data.payment_url;
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
            {t('Mollie Payment')}
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

          <div className="flex gap-3">
            <Button
              onClick={handlePayment}
              disabled={loading}
              className="flex-1"
            >
              {loading ? (
                <>
                  <Loader className="mr-2 h-4 w-4" />
                  {t('Redirecting...')}
                </>
              ) : (
                t('Pay with Mollie')
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
