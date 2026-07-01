import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader } from '@/components/ui/loader';
import { useTranslation } from 'react-i18next';
import { formatCurrencyForCompany } from '@/utils/helpers';

interface SkrillPaymentModalProps {
    isOpen: boolean;
    onClose: () => void;
    invoice: any;
    amount: number;
}

export function SkrillPaymentModal({ isOpen, onClose, invoice, amount }: SkrillPaymentModalProps) {
    const { t } = useTranslation();
    const [loading, setLoading] = useState(false);
    const [email, setEmail] = useState('');

    const handlePayment = async () => {
        setLoading(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                 const response = await fetch(route('skrill.create-invoice-payment'), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken || '',
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    invoice_token: invoice?.payment_token || '',
                    amount: amount,
                    email: email || 'customer@example.com'
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.redirect_url) {
                if (data.method === 'POST' && data.data) {
                    // Create and submit form for POST request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = data.redirect_url;

                    Object.keys(data.data).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = data.data[key];
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                } else {
                    window.location.href = data.redirect_url;
                }
            } else {
                throw new Error(data.error || "Payment failed");
            }
        } catch (error: any) {
            alert(error.message || "Payment failed");
        } finally {
            setLoading(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <div className="w-8 h-6 bg-purple-600 rounded flex items-center justify-center">
                            <span className="text-white text-xs font-bold">S</span>
                        </div>
                        {t("Skrill Payment")}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="bg-purple-50 p-4 rounded-lg">
                        <div className="text-sm text-purple-600 font-medium">
                            {t("Invoice")} #{invoice?.invoice_number || 'N/A'}
                        </div>
                        <div className="text-2xl font-bold text-purple-900">
                            {formatCurrencyForCompany(amount.toFixed(2))}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">{t("Email Address")}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder={t("Enter your email address")}
                            required
                        />
                    </div>

                    <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
                        <p className="text-sm text-blue-800">
                            {t("You will be redirected to Skrill to complete the payment")}
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            onClick={handlePayment}
                            disabled={loading || !email}
                            className="flex-1 bg-purple-600 hover:bg-purple-700"
                        >
                            {loading ? (
                                <>
                                    <Loader className="mr-2 h-4 w-4" />
                                    {t("Processing...")}
                                </>
                            ) : (
                                t("Pay with Skrill") || "Pay with Skrill"
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
