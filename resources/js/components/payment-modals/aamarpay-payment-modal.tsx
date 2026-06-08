import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Loader } from '@/components/ui/loader';
import { useTranslation } from 'react-i18next';

interface AamarpayPaymentModalProps {
    isOpen: boolean;
    onClose: () => void;
    invoice: any;
    amount: number;
}

export function AamarpayPaymentModal({ isOpen, onClose, invoice, amount }: AamarpayPaymentModalProps) {
    const { t } = useTranslation();
    const [loading, setLoading] = useState(false);

    const handlePayment = async () => {
        setLoading(true);

        try {
            // Create form and submit directly like plan payment
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('aamarpay.create-invoice-payment');
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            form.appendChild(csrfInput);
            
            // Add invoice token
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'invoice_token';
            tokenInput.value = invoice?.payment_token || '';
            form.appendChild(tokenInput);
            
            // Add amount
            const amountInput = document.createElement('input');
            amountInput.type = 'hidden';
            amountInput.name = 'amount';
            amountInput.value = amount.toString();
            form.appendChild(amountInput);
            
            document.body.appendChild(form);
            form.submit();
        } catch (error: any) {
            alert(error.message || t("Payment failed"));
            setLoading(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <div className="w-8 h-6 bg-green-600 rounded flex items-center justify-center">
                            <span className="text-white text-xs font-bold">A</span>
                        </div>
                        {t("Aamarpay Payment")}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="bg-green-50 p-4 rounded-lg">
                        <div className="text-sm text-green-600 font-medium">
                            {t("Invoice")} #{invoice?.invoice_number || 'N/A'}
                        </div>
                        <div className="text-2xl font-bold text-green-900">
                            ৳{amount.toFixed(2)}
                        </div>
                    </div>

                    <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
                        <p className="text-sm text-blue-800">
                            <strong>{t("Test Mode")}:</strong>{" "}
                            {t("This will simulate Aamarpay payment processing")}
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            onClick={handlePayment}
                            disabled={loading}
                            className="flex-1 bg-green-600 hover:bg-green-700"
                        >
                            {loading ? (
                                <>
                                    <Loader className="mr-2 h-4 w-4" />
                                    {t("Redirecting...")}
                                </>
                            ) : (
                                t("Pay with Aamarpay")
                            )}
                        </Button>
                        <Button
                            variant="outline"
                            onClick={onClose}
                            disabled={loading}
                        >
                            {t("Cancel")}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
