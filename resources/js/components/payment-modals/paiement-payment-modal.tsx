import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';

interface PaiementPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
}

export function PaiementPaymentModal({ isOpen, onClose, invoice, amount }: PaiementPaymentModalProps) {
  const { t } = useTranslation();
  const [processing, setProcessing] = useState(false);

  const handlePayment = async () => {
    setProcessing(true);

    try {
      const response = await fetch(route('paiement.create-invoice-payment'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          invoice_token: invoice.payment_token,
          amount: amount
        })
      });

      const data = await response.json();

      if (data.success) {
        const url = data.payment_response?.url;
        if (url) {
          window.location.href = url;
        } else {
          toast.error(t('Payment URL not received'));
          setProcessing(false);
        }
      } else {
        toast.error(data.error || t('Payment method not configured'));
        setProcessing(false);
      }
    } catch (error: any) {
      toast.error(t('Payment failed'));
      setProcessing(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CreditCard className="h-5 w-5" />
            {t('Paiement Pro Payment')}
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <Alert>
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              {t('Your payment will be processed securely through Paiement Pro.')}
            </AlertDescription>
          </Alert>

          <div className="bg-muted p-4 rounded-lg">
            <div className="flex justify-between items-center">
              <span className="font-medium">{t('Amount to Pay')}</span>
              <span className="text-lg font-bold">{formatCurrencyForCompany(amount.toFixed(2))}</span>
            </div>
          </div>

          <div className="flex gap-3 pt-4">
            <Button type="button" variant="outline" onClick={onClose} className="flex-1" disabled={processing}>
              {t('Cancel')}
            </Button>
            <Button onClick={handlePayment} disabled={processing} className="flex-1">
              {processing ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  {t('Processing...')}
                </>
              ) : (
                t('Pay with Paiement Pro')
              )}
            </Button>
          </div>

          <div className="text-xs text-muted-foreground text-center">
            {t('Powered by Paiement Pro - Secure payment processing')}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}