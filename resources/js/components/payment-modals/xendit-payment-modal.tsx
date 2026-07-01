import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';
import { Loader2, CreditCard, AlertCircle, ExternalLink } from 'lucide-react';

interface XenditPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
}

export function XenditPaymentModal({ isOpen, onClose, invoice, amount }: XenditPaymentModalProps) {
  const { t } = useTranslation();
  const [processing, setProcessing] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setProcessing(true);

    try {
      const response = await fetch(route('invoice.payment.process', invoice.payment_token), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          payment_method: 'xendit',
          invoice_token: invoice.payment_token,
          amount: amount
        })
      });

      const data = await response.json();

      if (data.success && data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        throw new Error(data.message || 'Payment failed');
      }
    } catch (error) {
      toast.error(error.message || 'Payment processing failed');
      setProcessing(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CreditCard className="h-5 w-5" />
            {t('Xendit Payment')}
          </DialogTitle>
        </DialogHeader>
        
        <div className="space-y-4">
          <Alert>
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              {t('You will be redirected to Xendit to complete your payment securely.')}
            </AlertDescription>
          </Alert>

          <div className="bg-muted p-4 rounded-lg">
            <div className="flex justify-between items-center">
              <span className="font-medium">{t('Amount to Pay')}</span>
              <span className="text-lg font-bold">{formatCurrencyForCompany(amount.toFixed(2))}</span>
            </div>
          </div>

          <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <h4 className="font-medium text-blue-900 mb-2">{t('Supported Payment Methods')}</h4>
            <ul className="text-sm text-blue-800 space-y-1">
              <li>• Credit/Debit Cards</li>
              <li>• Bank Transfer</li>
              <li>• E-Wallets (OVO, DANA, LinkAja)</li>
              <li>• Virtual Accounts</li>
              <li>• Retail Outlets</li>
            </ul>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="flex gap-3 pt-4">
              <Button type="button" variant="outline" onClick={onClose} className="flex-1" disabled={processing}>
                {t('Cancel')}
              </Button>
              <Button type="submit" disabled={processing} className="flex-1">
                {processing ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    {t('Redirecting...')}
                  </>
                ) : (
                  <>
                    <ExternalLink className="mr-2 h-4 w-4" />
                    {t('Pay with Xendit')}
                  </>
                )}
              </Button>
            </div>
          </form>

          <div className="text-xs text-muted-foreground text-center">
            {t('Powered by Xendit - Secure payment processing')}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}