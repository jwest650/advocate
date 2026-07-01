import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';
import axios from 'axios';

interface RazorpayPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
}

export function RazorpayPaymentModal({ isOpen, onClose, invoice, amount }: RazorpayPaymentModalProps) {
  const { t } = useTranslation();
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    if (!isOpen) return;
    
    // Load Razorpay script if not already loaded
    if (!(window as any).Razorpay) {
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.async = true;
      script.onerror = () => {
        toast.error(t('Failed to load Razorpay checkout. Please try again.'));
      };
      document.body.appendChild(script);
    }
  }, [isOpen]);

  const handlePayment = async () => {
    setProcessing(true);

    try {
      // Create order on the server
      const response = await axios.post(route('razorpay.create-invoice-order'), {
        invoice_token: invoice.payment_token,
        amount: amount
      });
      
      if (response.data.error) {
        toast.error(response.data.error);
        setProcessing(false);
        return;
      }
      
      const { order_id, amount: razorpayAmount, key } = response.data;
      
      if (!order_id || !razorpayAmount || !key) {
        toast.error(t('Invalid response from server'));
        setProcessing(false);
        return;
      }
      
      const options = {
        key: key,
        amount: razorpayAmount,
        currency: 'INR',
        name: 'Invoice Payment',
        description: `Payment for Invoice ${invoice.invoice_number}`,
        order_id: order_id,
        handler: function(response: any) {
          // Verify payment on server first
          axios.post(route('razorpay.verify-invoice-payment'), {
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_signature: response.razorpay_signature,
            invoice_token: invoice.payment_token,
            amount: amount
          })
          .then(() => {
            // After verification, process the payment
            router.post(route('invoice.payment.process', invoice.payment_token), {
              payment_method: 'razorpay',
              invoice_token: invoice.payment_token,
              amount: amount,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_order_id: response.razorpay_order_id
            }, {
              onSuccess: () => {
                // Toast will be shown by backend flash message
              },
              onError: (errors) => {
                toast.error(Object.values(errors).join(', '));
                setProcessing(false);
              }
            });
          })
          .catch((error) => {
            const errorMsg = error.response?.data?.error || t('Payment verification failed');
            toast.error(errorMsg);
            setProcessing(false);
          });
        },
        prefill: {
          name: invoice.client?.name || '',
          email: invoice.client?.email || '',
          contact: ''
        },
        theme: {
          color: '#3B82F6'
        },
        modal: {
          ondismiss: () => {
            setProcessing(false);
            onClose();
          }
        }
      };
      
      if (!(window as any).Razorpay) {
        toast.error(t('Razorpay SDK not loaded'));
        setProcessing(false);
        return;
      }
      
      const razorpay = new (window as any).Razorpay(options);
      razorpay.open();
    } catch (error: any) {
      const errorMsg = error.response?.data?.error || t('Failed to initialize payment');
      toast.error(errorMsg);
      setProcessing(false);
      console.error('Razorpay error:', error);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CreditCard className="h-5 w-5" />
            {t('Razorpay Payment')}
          </DialogTitle>
        </DialogHeader>
        
        <div className="space-y-4">
          <Alert>
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              {t('You will be redirected to Razorpay to complete your payment securely.')}
            </AlertDescription>
          </Alert>

          <div className="bg-muted p-4 rounded-lg">
            <div className="flex justify-between items-center">
              <span className="font-medium">{t('Amount to Pay')}</span>
              <span className="text-lg font-bold">₹{amount.toFixed(2)}</span>
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
                t('Pay with Razorpay')
              )}
            </Button>
          </div>

          <div className="text-xs text-muted-foreground text-center">
            {t('Powered by Razorpay - Secure payment processing')}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

declare global {
  interface Window {
    Razorpay?: any;
  }
}