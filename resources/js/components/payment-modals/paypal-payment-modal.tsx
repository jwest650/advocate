import { useState, useEffect, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';

interface PayPalPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: any;
  amount: number;
  paypalClientId?: string;
}

export function PayPalPaymentModal({ isOpen, onClose, invoice, amount, paypalClientId }: PayPalPaymentModalProps) {
  const { t } = useTranslation();
  const [processing, setProcessing] = useState(false);
  const paypalRef = useRef<HTMLDivElement>(null);
  const [paypalLoaded, setPaypalLoaded] = useState(false);
  const [useSimplePayment, setUseSimplePayment] = useState(false);

  // Simple payment handler for immediate testing
  const handleSimplePayment = () => {
    setProcessing(true);

    // Generate mock PayPal transaction data
    const mockOrderId = 'ORDER_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    const mockPaymentId = 'PAY_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

    router.post(route('invoice.payment.process', invoice.payment_token), {
      payment_method: 'paypal',
      invoice_token: invoice.payment_token,
      amount: amount,
      order_id: mockOrderId,
      payment_id: mockPaymentId
    }, {
      onSuccess: () => {
        console.log('Simple PayPal payment processed successfully');
      },
      onError: (errors) => {
        console.error('Simple PayPal payment error:', errors);
        toast.error(Object.values(errors).join(', '));
        setProcessing(false);
      }
    });
  };

  useEffect(() => {
    if (!isOpen || !paypalClientId) return;

    // Clear any existing PayPal buttons
    if (paypalRef.current) {
      paypalRef.current.innerHTML = '';
    }

    // Check if PayPal SDK is already loaded
    if (window.paypal) {
      renderPayPalButtons();
      return;
    }

    // Load PayPal SDK
    const existingScript = document.querySelector(`script[src*="paypal.com/sdk/js"]`);
    if (existingScript) {
      existingScript.addEventListener('load', renderPayPalButtons);
      return;
    }

    const script = document.createElement('script');
    script.src = `https://www.paypal.com/sdk/js?client-id=${paypalClientId}&currency=USD&disable-funding=credit,card`;
    script.async = true;

    script.onload = renderPayPalButtons;
    script.onerror = () => {
      console.error('Failed to load PayPal SDK');
      toast.error(t('Failed to load PayPal. Please try again.'));
    };

    document.head.appendChild(script);
  }, [isOpen, paypalClientId]);

  const renderPayPalButtons = () => {
    if (!window.paypal || !paypalRef.current) return;

    setPaypalLoaded(true);

    window.paypal.Buttons({
      createOrder: (data: any, actions: any) => {
        console.log('Creating PayPal order for amount:', amount);
        return actions.order.create({
          purchase_units: [{
            amount: {
              value: amount.toFixed(2),
              currency_code: 'USD'
            },
            description: `Invoice ${invoice.invoice_number} Payment`
          }]
        });
      },
      onApprove: (data: any, actions: any) => {
        console.log('PayPal payment approved:', data);
        setProcessing(true);

        // Skip capture and process payment directly
        router.post(route('invoice.payment.process', invoice.payment_token), {
          payment_method: 'paypal',
          invoice_token: invoice.payment_token,
          amount: amount,
          order_id: data.orderID,
          payment_id: data.paymentID || data.orderID
        }, {
          onSuccess: () => {
            console.log('Payment processed successfully');
            toast.success(t('Payment successful'));
            window.location.reload();
          },
          onError: (errors) => {
            console.error('Payment processing error:', errors);
            toast.error(Object.values(errors).join(', '));
            setProcessing(false);
          }
        });
      },
      onError: (err: any) => {
        console.error('PayPal SDK error:', err);
        toast.error(t('PayPal error occurred. Please try again.'));
        setProcessing(false);
      },
      onCancel: () => {
        console.log('PayPal payment cancelled');
        toast.info(t('Payment cancelled'));
        onClose();
      }
    }).render(paypalRef.current).catch((error: any) => {
      console.error('PayPal render error:', error);
      toast.error(t('Failed to load PayPal buttons. Please refresh and try again.'));
    });
  };

  const handleFallbackPayment = () => {
    setProcessing(true);
    router.post(route('invoice.payment.process', invoice.payment_token), {
      payment_method: 'paypal',
      invoice_token: invoice.payment_token,
      amount: amount,
      order_id: 'MANUAL_' + Date.now(),
      payment_id: 'MANUAL_PAY_' + Date.now()
    }, {
      onSuccess: () => {
        toast.success(t('Payment request submitted'));
        onClose();
      },
      onError: (errors) => {
        toast.error(Object.values(errors).join(', '));
        setProcessing(false);
      }
    });
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>{t('PayPal Payment')}</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div className="text-center p-4">
            <div className="text-4xl mb-2">🅿️</div>
            <h3 className="text-lg font-semibold mb-2">{t('Pay with PayPal')}</h3>
            <p className="text-xl font-bold text-blue-600">              {formatCurrencyForCompany(amount.toFixed(2))}</p>
          </div>

          {paypalClientId ? (
            <div>
              <div ref={paypalRef} className="min-h-[50px] w-full"></div>
              {!paypalLoaded && (
                <div className="text-center py-4">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="text-sm text-gray-600 mt-2">{t('Loading PayPal...')}</p>
                </div>
              )}
              {paypalLoaded && (
                <p className="text-xs text-gray-500 text-center mt-2">
                  {t('Click the PayPal button above to complete payment')}
                </p>
              )}
            </div>
          ) : (
            <div className="space-y-4">
              <p className="text-sm text-gray-600 text-center">
                {t('PayPal integration not configured. Use simple payment for testing.')}
              </p>
              <div className="flex gap-3">
                <Button type="button" variant="outline" onClick={onClose} className="flex-1">
                  {t('Cancel')}
                </Button>
                <Button onClick={handleSimplePayment} disabled={processing} className="flex-1 bg-blue-600 hover:bg-blue-700">
                  {processing ? t('Processing...') : t('Pay with PayPal (Test)')}
                </Button>
              </div>
            </div>
          )}

          {paypalClientId && paypalLoaded && (
            <div className="flex justify-center">
              <Button type="button" variant="outline" onClick={onClose} disabled={processing}>
                {t('Cancel')}
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}

// Extend window object for PayPal
declare global {
  interface Window {
    paypal?: any;
  }
}
