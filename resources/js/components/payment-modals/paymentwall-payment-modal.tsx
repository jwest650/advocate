import { useState, useEffect, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { formatCurrencyForCompany } from '@/utils/helpers';


declare global {
    interface Window {
        Brick: any;
    }
}

interface PaymentWallPaymentModalProps {
    isOpen: boolean;
    onClose: () => void;
    invoice: any;
    amount: number;
}

export function PaymentWallPaymentModal({ isOpen, onClose, invoice, amount }: PaymentWallPaymentModalProps) {
    const { t } = useTranslation();
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [brickLoaded, setBrickLoaded] = useState(false);
    const [brickInstance, setBrickInstance] = useState<any>(null);
    const paymentFormRef = useRef<HTMLDivElement>(null);

    // Load Brick.js script
    useEffect(() => {
        if (!isOpen) return;

        const loadBrickScript = () => {
            if (window.Brick) {
                setBrickLoaded(true);
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://api.paymentwall.com/brick/build/brick-default.1.5.0.min.js';
            script.async = true;
            script.onload = () => {
                setBrickLoaded(true);
            };
            script.onerror = () => {
                setError(t('Failed to load PaymentWall payment form'));
            };
            document.head.appendChild(script);
        };

        loadBrickScript();
    }, [isOpen, t]);

    // Initialize Brick payment form
    useEffect(() => {
        if (brickLoaded && isOpen && !brickInstance) {
            initializeBrickForm();
        }
    }, [brickLoaded, isOpen, brickInstance]);

    const initializeBrickForm = async () => {
        try {
            const response = await fetch(route('paymentwall.create-invoice-payment'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    invoice_token: invoice.payment_token,
                    amount: amount,
                }),
            });

            const data = await response.json();

            if (data.success && data.brick_config) {
                const config = data.brick_config;

                const brick = new window.Brick({
                    public_key: config.public_key,
                    amount: config.amount,
                    currency: config.currency,
                    container: 'paymentwall-invoice-form-container',
                    action: route('paymentwall.process.invoice'),
                    form: {
                        merchant: 'Invoice Payment',
                        product: config.description,
                        pay_button: t('Pay Now'),
                        show_zip: true,
                        show_cardholder: true
                    }
                });

                brick.showPaymentForm(
                    (data: any) => {
                        // Success callback
                        onClose();
                        toast.success(t('Payment successful'));

                        window.location.reload();
                    },
                    (errors: any) => {
                        // Error callback
                        console.error('Payment error:', errors);
                        if (errors && errors.length > 0) {
                            setError(errors[0].message || t('Payment failed'));
                        } else {
                            setError(t('Payment failed'));
                        }
                        setIsLoading(false);
                    }
                );

                setBrickInstance(brick);
            } else {
                throw new Error(data.error || t('Failed to initialize payment form'));
            }
        } catch (err) {
            console.error('PaymentWall initialization error:', err);
            setError(err instanceof Error ? err.message : t('Payment initialization failed'));
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <CreditCard className="h-5 w-5" />
                        {t('PaymentWall Payment')}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    {error && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    <div className="bg-muted p-4 rounded-lg">
                        <div className="flex justify-between items-center">
                            <span className="font-medium">{t('Amount to Pay')}</span>
                            <span className="text-lg font-bold">{formatCurrencyForCompany(amount.toFixed(2))}</span>
                        </div>
                        <div className="text-sm text-muted-foreground mt-1">
                            {t('Invoice')} #{invoice.invoice_number}
                        </div>
                    </div>

                    {/* PaymentWall Brick.js Form Container */}
                    <div className="space-y-4">
                        <div id="paymentwall-invoice-form-container" ref={paymentFormRef} className="min-h-[300px]">
                            {!brickLoaded && (
                                <div className="flex items-center justify-center h-32">
                                    <Loader2 className="h-6 w-6 animate-spin mr-2" />
                                    <span>{t('Loading payment form...')}</span>
                                </div>
                            )}
                        </div>

                        {/* Hidden form fields for Brick.js */}
                        <form id="brick-invoice-form" style={{ display: 'none' }}>
                            <input type="hidden" name="invoice_token" value={invoice.payment_token} />
                            <input type="hidden" name="amount" value={amount} />
                            <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
                        </form>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            variant="outline"
                            onClick={onClose}
                            disabled={isLoading}
                            className="flex-1"
                        >
                            {t('Cancel')}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
