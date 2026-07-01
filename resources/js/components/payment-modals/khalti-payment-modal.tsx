import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Loader } from '@/components/ui/loader';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';

interface KhaltiPaymentModalProps {
    isOpen: boolean;
    onClose: () => void;
    invoice: any;
    amount: number;
}

export function KhaltiPaymentModal({ isOpen, onClose, invoice, amount }: KhaltiPaymentModalProps) {
    const { t } = useTranslation();
    const [loading, setLoading] = useState(false);

    const handlePayment = async () => {
        setLoading(true);

        try {

            const response = await fetch(route('khalti.create-invoice-payment'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    invoice_token: invoice.payment_token,
                    amount: amount,
                }),
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (jsonError) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid response');
            }

            if (data.success && data.public_key) {
                // Initialize Khalti payment with embedded mode
                const config = {
                    publicKey: data.public_key,
                    productIdentity: 'invoice_' + invoice.id,
                    productName: 'Invoice Payment - ' + invoice.invoice_number,
                    productUrl: window.location.origin,
                    paymentPreference: ['KHALTI', 'EBANKING', 'MOBILE_BANKING', 'CONNECT_IPS', 'SCT'],
                    // Use embedded mode to stay in same window
                    embedded: true,
                    eventHandler: {
                        onSuccess: (payload: any) => {
                            // Handle successful payment
                            onClose(); // Close modal first
                            window.location.href = baseUrl + '/khalti/invoice/success?token=' + payload.token + '&amount=' + payload.amount + '&invoice_token=' + invoice.payment_token;
                        },
                        onError: (error: any) => {
                            console.error('Khalti payment error:', error);
                            toast.error(t('Payment failed'));
                            setLoading(false);
                        },
                        onClose: () => {
                            setLoading(false);
                        }
                    }
                };

                // Load Khalti script if not already loaded
                if (typeof (window as any).KhaltiCheckout === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.17.0.0.0/khalti-checkout.iffe.js';
                    script.onload = () => {
                        const checkout = new (window as any).KhaltiCheckout(config);
                        checkout.show({
                            amount: data.amount,
                            // Force same window instead of popup
                            target: '_self'
                        });
                    };
                    document.head.appendChild(script);
                } else {
                    const checkout = new (window as any).KhaltiCheckout(config);
                    checkout.show({
                        amount: data.amount,
                        // Force same window instead of popup
                        target: '_self'
                    });
                }
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
                        {t('Khalti Payment')}
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
                                    {t('Loading...')}
                                </>
                            ) : (
                                t('Pay with Khalti')
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
