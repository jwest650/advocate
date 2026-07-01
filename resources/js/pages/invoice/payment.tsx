import { useState, useEffect } from 'react';
import { usePage, router, Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FileText, Calendar, User, Building2, Clock, Shield, Banknote, CreditCard, IndianRupee, Wallet, Coins } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { PaymentGatewaySelection } from '@/components/payment-gateway-selection';
import { StripePaymentModal } from '@/components/payment-modals/stripe-payment-modal';
import { BankPaymentModal } from '@/components/payment-modals/bank-payment-modal';
import { PayPalPaymentModal } from '@/components/payment-modals/paypal-payment-modal';
import { RazorpayPaymentModal } from '@/components/payment-modals/razorpay-payment-modal';
import { FlutterwavePaymentModal } from '@/components/payment-modals/flutterwave-payment-modal';
import { TapPaymentModal } from '@/components/payment-modals/tap-payment-modal';
import { XenditPaymentModal } from '@/components/payment-modals/xendit-payment-modal';
import { PaystackPaymentModal } from '@/components/payment-modals/paystack-payment-modal';
import { CoinGatePaymentModal } from '@/components/payment-modals/coingate-payment-modal';
import { AamarpayPaymentModal } from '@/components/payment-modals/aamarpay-payment-modal';
import { AuthorizeNetPaymentModal } from '@/components/payment-modals/authorizenet-payment-modal';
import { BenefitPaymentModal } from '@/components/payment-modals/benefit-payment-modal';
import { CashfreePaymentModal } from '@/components/payment-modals/cashfree-payment-modal';
import { CinetPayPaymentModal } from '@/components/payment-modals/cinetpay-payment-modal';
import { EasebuzzPaymentModal } from '@/components/payment-modals/easebuzz-payment-modal';
import { FedaPayPaymentModal } from '@/components/payment-modals/fedapay-payment-modal';
import { IyzipayPaymentModal } from '@/components/payment-modals/iyzipay-payment-modal';
import { KhaltiPaymentModal } from '@/components/payment-modals/khalti-payment-modal';
import { MidtransPaymentModal } from '@/components/payment-modals/midtrans-payment-modal';
import { MolliePaymentModal } from '@/components/payment-modals/mollie-payment-modal';
import { OzowPaymentModal } from '@/components/payment-modals/ozow-payment-modal';
import { PaiementPaymentModal } from '@/components/payment-modals/paiement-payment-modal';
import { PayfastPaymentModal } from '@/components/payment-modals/payfast-payment-modal';
import { PayHerePaymentModal } from '@/components/payment-modals/payhere-payment-modal';
import { PayTabsPaymentModal } from '@/components/payment-modals/paytabs-payment-modal';
import { PayTRPaymentModal } from '@/components/payment-modals/paytr-payment-modal';
import { PaymentWallPaymentModal } from '@/components/payment-modals/paymentwall-payment-modal';

import { SSPayPaymentModal } from '@/components/payment-modals/sspay-payment-modal';
import { ToyyibPayPaymentModal } from '@/components/payment-modals/toyyibpay-payment-modal';
import { YooKassaPaymentModal } from '@/components/payment-modals/yookassa-payment-modal';
import { SkrillPaymentModal } from '@/components/payment-modals/skrill-payment-modal';
import { useFavicon } from '@/hooks/use-favicon';

export default function InvoicePayment() {
      useFavicon();
    const { invoice, enabledGateways, remainingAmount, clientBillingInfo, currencies, paypalClientId, flutterwavePublicKey, tapPublicKey, paystackPublicKey, flash, company, favicon, appName } = usePage().props as any;
    const [selectedGateway, setSelectedGateway] = useState<string | null>(null);
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [showGatewayModal, setShowGatewayModal] = useState(false);
    const [showSuccessMessage, setShowSuccessMessage] = useState(false);
    const [showCopiedMessage, setShowCopiedMessage] = useState(false);
    const [paymentAmount, setPaymentAmount] = useState(remainingAmount || invoice.total_amount || 0);

    // Payment method icons mapping with Lucide React icons (same as plans)
    const getPaymentMethodIcon = (gatewayId: string) => {
        const iconMap = {
            bank: <Banknote className="h-5 w-5" />,
            stripe: <CreditCard className="h-5 w-5" />,
            paypal: <CreditCard className="h-5 w-5" />,
            razorpay: <IndianRupee className="h-5 w-5" />,
            mercadopago: <Wallet className="h-5 w-5" />,
            paystack: <CreditCard className="h-5 w-5" />,
            flutterwave: <CreditCard className="h-5 w-5" />,
            paytabs: <CreditCard className="h-5 w-5" />,
            skrill: <Wallet className="h-5 w-5" />,
            coingate: <Coins className="h-5 w-5" />,
            payfast: <CreditCard className="h-5 w-5" />,
            tap: <CreditCard className="h-5 w-5" />,
            xendit: <CreditCard className="h-5 w-5" />,
            paytr: <CreditCard className="h-5 w-5" />,
            mollie: <CreditCard className="h-5 w-5" />,
            toyyibpay: <CreditCard className="h-5 w-5" />,
            cashfree: <IndianRupee className="h-5 w-5" />,
            khalti: <CreditCard className="h-5 w-5" />,
            iyzipay: <CreditCard className="h-5 w-5" />,
            benefit: <CreditCard className="h-5 w-5" />,
            ozow: <CreditCard className="h-5 w-5" />,
            easebuzz: <IndianRupee className="h-5 w-5" />,
            authorizenet: <CreditCard className="h-5 w-5" />,
            fedapay: <CreditCard className="h-5 w-5" />,
            payhere: <CreditCard className="h-5 w-5" />,
            cinetpay: <CreditCard className="h-5 w-5" />,
            paiement: <CreditCard className="h-5 w-5" />,
            nepalste: <CreditCard className="h-5 w-5" />,
            yookassa: <CreditCard className="h-5 w-5" />,
            aamarpay: <CreditCard className="h-5 w-5" />,
            midtrans: <CreditCard className="h-5 w-5" />,
            paymentwall: <CreditCard className="h-5 w-5" />,
            sspay: <CreditCard className="h-5 w-5" />
        };
        return iconMap[gatewayId] || <CreditCard className="h-5 w-5" />;
    };

    // Add icons to enabled gateways
    const gatewaysWithIcons = enabledGateways?.map(gateway => ({
        ...gateway,
        icon: getPaymentMethodIcon(gateway.id)
    })) || [];

    useEffect(() => {
        // Add a small delay to ensure DOM is ready
        const timer = setTimeout(() => {
            if (flash?.success) {
                toast.success(flash.success);
                setShowSuccessMessage(true);
                setTimeout(() => setShowSuccessMessage(false), 5000);
            }
            if (flash?.error) {
                toast.error(flash.error);
            }
        }, 100);

        return () => clearTimeout(timer);
    }, [flash]);

    // Check for payment status changes and show toast
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const paymentSuccess = urlParams.get('payment_success');
        const paymentStatus = urlParams.get('status');

        if (paymentSuccess === 'true' || paymentStatus === 'success') {
            toast.success('Payment successful');
            // Clean URL parameters
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }, []);

    // Monitor invoice status changes
    useEffect(() => {
        const checkPaymentStatus = () => {
            if (invoice?.status === 'paid' && remainingAmount === 0 && !flash?.success) {
                toast.success('Payment successful');
            }
        };

        checkPaymentStatus();
    }, [invoice?.status, remainingAmount, flash?.success]);

    // Get formatted currency using company settings
    const formatAmount = (amount) => {
        if (typeof window !== 'undefined' && window.appSettings?.formatCurrency) {
            const numericAmount = typeof amount === 'number' ? amount : parseFloat(amount);
            return window.appSettings.formatCurrency(numericAmount, { showSymbol: true });
        }
        return `$${parseFloat(amount).toFixed(2)}`;
    };

    const isOverdue = new Date(invoice.due_date) < new Date();

    const handleGatewaySelect = (gatewayId: string) => {
        try {
            console.log('Selected gateway:', gatewayId);
            setSelectedGateway(gatewayId);
            setShowPaymentModal(true);
        } catch (error) {
            console.error('Error selecting payment gateway:', error);
            toast.error('Failed to select payment method. Please try again.');
        }
    };

    const closeModal = () => {
        try {
            setShowPaymentModal(false);
            setSelectedGateway(null);
        } catch (error) {
            console.error('Error closing modal:', error);
            toast.error('An error occurred. Please refresh the page.');
        }
    };

    const handlePaymentSuccess = () => {
        toast.success('Payment successful');
        closeModal();
        // Reload page after a short delay to show updated status
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    };

    const renderPaymentModal = () => {
        if (!selectedGateway || !showPaymentModal) return null;

        const modalProps = {
            isOpen: showPaymentModal,
            onClose: closeModal,
            onSuccess: handlePaymentSuccess,
            invoice,
            amount: Number(paymentAmount)
        };

        switch (selectedGateway) {
            case 'stripe':
                return <StripePaymentModal {...modalProps} />;
            case 'bank':
                return <BankPaymentModal {...modalProps} />;
            case 'paypal':
                return <PayPalPaymentModal {...modalProps} paypalClientId={paypalClientId} />;
            case 'razorpay':
                return <RazorpayPaymentModal {...modalProps} />;
            case 'flutterwave':
                return <FlutterwavePaymentModal {...modalProps} flutterwavePublicKey={flutterwavePublicKey} />;
            case 'tap':
                return <TapPaymentModal {...modalProps} tapPublicKey={tapPublicKey} />;
            case 'xendit':
                return <XenditPaymentModal {...modalProps} />;
            case 'paystack':
                return <PaystackPaymentModal
                    isOpen={showPaymentModal}
                    onClose={closeModal}
                    invoice={invoice}
                    amount={Number(paymentAmount)}
                    paystackKey={paystackPublicKey}
                />;
            case 'coingate':
                return <CoinGatePaymentModal {...modalProps} amount={paymentAmount} />;
            case 'aamarpay':
                return <AamarpayPaymentModal {...modalProps} />;
            case 'authorizenet':
                return <AuthorizeNetPaymentModal {...modalProps} />;
            case 'benefit':
                return <BenefitPaymentModal {...modalProps} />;
            case 'cashfree':
                return <CashfreePaymentModal {...modalProps} />;
            case 'cinetpay':
                return <CinetPayPaymentModal {...modalProps} />;
            case 'easebuzz':
                return <EasebuzzPaymentModal {...modalProps} />;
            case 'fedapay':
                return <FedaPayPaymentModal {...modalProps} />;
            case 'iyzipay':
                return <IyzipayPaymentModal {...modalProps} />;
            case 'khalti':
                return <KhaltiPaymentModal {...modalProps} />;
            case 'midtrans':
                return <MidtransPaymentModal {...modalProps} />;
            case 'mollie':
                return <MolliePaymentModal {...modalProps} />;
            case 'ozow':
                return <OzowPaymentModal {...modalProps} />;
            case 'paiement':
                return <PaiementPaymentModal {...modalProps} />;
            case 'payfast':
                return <PayfastPaymentModal {...modalProps} />;
            case 'payhere':
                return <PayHerePaymentModal {...modalProps} />;
            case 'paytabs':
                return (
                    <PayTabsPaymentModal
                        invoiceToken={invoice.payment_token}
                        amount={modalProps.amount}
                        onSuccess={handlePaymentSuccess}
                        onCancel={closeModal}
                    />
                );
            case 'paytr':
                return <PayTRPaymentModal {...modalProps} />;
            case 'paymentwall':
                return <PaymentWallPaymentModal {...modalProps} />;
            case 'skrill':
                return <SkrillPaymentModal {...modalProps} />;
            case 'sspay':
                return <SSPayPaymentModal {...modalProps} />;
            case 'toyyibpay':
                return <ToyyibPayPaymentModal {...modalProps} />;
            case 'yookassa':
                return <YooKassaPaymentModal {...modalProps} />;
            default:
                return null;
        }
    };

    return (
        <>
            <Head title={`Invoice - ${company?.name || 'Advocate Saas'}`}>
            {favicon && (
                <link rel="icon" type="image/x-icon" href={favicon} />
            )}
        </Head >
            <div className="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
                {/* Modern Header */}
                <div className="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 sticky top-0 z-10">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <div className="bg-gradient-to-br from-blue-600 to-blue-700 p-3 rounded-xl shadow-lg">
                                    <FileText className="h-7 w-7 text-white" />
                                </div>
                                <div>
                                    <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Invoice #{invoice.invoice_number}</h1>
                                    <p className="text-gray-600 text-sm sm:text-base flex items-center mt-1">
                                        <Shield className="h-4 w-4 mr-1" />
                                        Secure Payment Portal
                                    </p>
                                </div>
                            </div>
                            <Badge variant={isOverdue ? 'destructive' : 'secondary'} className="text-xs sm:text-sm px-3 py-1.5 font-medium">
                                {isOverdue ? 'Overdue' : 'Due'} {new Date(invoice.due_date).toLocaleDateString()}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Header with Title and Buttons */}
                    <div className="flex justify-between items-start mb-8">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Invoice Details</h1>
                            <p className="text-gray-600 mt-1">View and manage your invoice</p>
                        </div>
                        <div className="flex gap-3">
                            <Button
                                variant="outline"
                                className="h-10 px-4 text-sm font-medium relative"
                                onClick={async () => {
                                    try {
                                        await navigator.clipboard.writeText(window.location.href);
                                        setShowCopiedMessage(true);
                                        setTimeout(() => setShowCopiedMessage(false), 2000);
                                        toast.success('Link copied to clipboard!');
                                    } catch (error) {
                                        console.error('Error copying link:', error);
                                        toast.error('Failed to copy link. Please try again.');
                                    }
                                }}
                            >
                                {showCopiedMessage ? (
                                    <span className="flex items-center text-green-600">
                                        ✓ Copied!
                                    </span>
                                ) : (
                                    "📋 Copy Link"
                                )}
                            </Button>

                            {(invoice.status === 'partial_paid' || (invoice.status !== 'paid' && remainingAmount > 0)) && (
                                <Button
                                    className="h-10 px-4 text-sm font-medium bg-green-600 hover:bg-green-700"
                                    onClick={() => {
                                        try {
                                            setShowGatewayModal(true);
                                        } catch (error) {
                                            console.error('Error opening payment modal:', error);
                                            toast.error('Failed to open payment options. Please try again.');
                                        }
                                    }}
                                >
                                    💳 Pay Invoice
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Invoice Header Card */}
                    <Card className="mb-8 border-0 shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h2 className="text-xl font-bold text-gray-900">{invoice.invoice_number} {invoice.client?.name}</h2>
                                    <p className="text-gray-600 mt-1">Invoice for professional services and software licenses.</p>
                                </div>
                                <div className="text-right">
                                    <Badge
                                        variant={invoice.status === 'paid' ? 'default' : 'outline'}
                                        className={`mb-2 ${
                                            invoice.status === 'paid' ? 'bg-green-100 text-green-800' :
                                            invoice.status === 'partial_paid' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-red-100 text-red-800'
                                        }`}
                                    >
                                        {invoice.status === 'paid' ? 'Paid' : 
                                         invoice.status === 'partial_paid' ? 'Partial Paid' : 'Unpaid'}
                                    </Badge>
                                    <p className="text-sm text-gray-600">{invoice.invoice_number}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <Card className="border-l-4 border-l-green-500 shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">TOTAL AMOUNT</p>
                                        <p className="text-2xl font-bold text-green-600 mt-1">{formatAmount(invoice.total_amount)}</p>
                                    </div>
                                    <div className="bg-green-100 p-3 rounded-full">
                                        <span className="text-green-600 text-xl">💰</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-blue-500 shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">PAID AMOUNT</p>
                                        <p className="text-2xl font-bold text-blue-600 mt-1">{formatAmount((invoice.total_amount - remainingAmount) || 0)}</p>
                                    </div>
                                    <div className="bg-blue-100 p-3 rounded-full">
                                        <span className="text-blue-600 text-xl">💳</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-red-500 shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">DUE AMOUNT</p>
                                        <p className="text-2xl font-bold text-red-600 mt-1">{formatAmount(remainingAmount)}</p>
                                    </div>
                                    <div className="bg-red-100 p-3 rounded-full">
                                        <span className="text-red-600 text-xl">⏰</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <Card className="border-l-4 border-l-purple-500 shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">PRODUCTS</p>
                                        <p className="text-2xl font-bold text-purple-600 mt-1">{invoice.line_items?.length || 1}</p>
                                    </div>
                                    <div className="bg-purple-100 p-3 rounded-full">
                                        <span className="text-purple-600 text-xl">📦</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-orange-500 shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">INVOICE DATE</p>
                                        <p className="text-lg font-bold text-orange-600 mt-1">{new Date(invoice.invoice_date).toLocaleDateString()}</p>
                                    </div>
                                    <div className="bg-orange-100 p-3 rounded-full">
                                        <span className="text-orange-600 text-xl">📅</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-yellow-500 shadow-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">DUE DATE</p>
                                        <p className="text-lg font-bold text-yellow-600 mt-1">{new Date(invoice.due_date).toLocaleDateString()}</p>
                                    </div>
                                    <div className="bg-yellow-100 p-3 rounded-full">
                                        <span className="text-yellow-600 text-xl">📋</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 xl:grid-cols-1 gap-8">
                        {/* Invoice Details - Left Side */}
                        <div className="xl:col-span-3 space-y-6">
                            {/* Client & Invoice Info */}
                            <Card className="shadow-xl border-0 overflow-hidden">
                                <CardHeader className="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white">
                                    <CardTitle className="flex items-center space-x-2 text-lg">
                                        <Building2 className="h-5 w-5" />
                                        <span>Invoice Information</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div className="space-y-6">
                                            <div className="flex items-start space-x-4">
                                                <div className="bg-blue-100 p-2 rounded-lg">
                                                    <User className="h-5 w-5 text-blue-600" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-semibold text-gray-500 uppercase tracking-wide">Bill To</p>
                                                    <p className="text-xl font-bold text-gray-900 mt-1">{invoice.client?.name}</p>
                                                    {invoice.client?.email && (
                                                        <p className="text-sm text-gray-600 mt-1">{invoice.client.email}</p>
                                                    )}
                                                </div>
                                            </div>
                                            {invoice.case && (
                                                <div className="flex items-start space-x-4">
                                                    <div className="bg-green-100 p-2 rounded-lg">
                                                        <FileText className="h-5 w-5 text-green-600" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-semibold text-gray-500 uppercase tracking-wide">Case</p>
                                                        <p className="text-lg font-semibold text-gray-900 mt-1">{invoice.case.title}</p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        <div className="space-y-6">
                                            <div className="flex items-start space-x-4">
                                                <div className="bg-purple-100 p-2 rounded-lg">
                                                    <Calendar className="h-5 w-5 text-purple-600" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-semibold text-gray-500 uppercase tracking-wide">Invoice Date</p>
                                                    <p className="text-lg font-semibold text-gray-900 mt-1">{new Date(invoice.invoice_date).toLocaleDateString()}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-start space-x-4">
                                                <div className={`p-2 rounded-lg ${isOverdue ? 'bg-red-100' : 'bg-orange-100'}`}>
                                                    <Clock className={`h-5 w-5 ${isOverdue ? 'text-red-600' : 'text-orange-600'}`} />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-semibold text-gray-500 uppercase tracking-wide">Due Date</p>
                                                    <p className={`text-lg font-semibold mt-1 ${isOverdue ? 'text-red-600' : 'text-gray-900'}`}>
                                                        {new Date(invoice.due_date).toLocaleDateString()}
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Products */}
                            {invoice.line_items && invoice.line_items.length > 0 && (
                                <Card className="shadow-sm border-0 overflow-hidden">
                                    <CardHeader className="bg-gray-50 border-b">
                                        <CardTitle className="flex items-center space-x-2 text-lg text-gray-900">
                                            <span>📦</span>
                                            <span>Products</span>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-0">
                                        <div className="overflow-x-auto">
                                            <table className="w-full">
                                                <thead className="bg-gray-100">
                                                    <tr>
                                                        <th className="px-6 py-4 text-left text-sm font-bold text-gray-700">Product</th>
                                                        <th className="px-6 py-4 text-center text-sm font-bold text-gray-700">Quantity</th>
                                                        <th className="px-6 py-4 text-right text-sm font-bold text-gray-700">Unit Price</th>
                                                        <th className="px-6 py-4 text-center text-sm font-bold text-gray-700">Tax</th>
                                                        <th className="px-6 py-4 text-right text-sm font-bold text-gray-700">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-200">
                                                    {invoice.line_items.map((item: any, index: number) => (
                                                        <tr key={index} className="hover:bg-gray-50 transition-colors">
                                                            <td className="px-6 py-4 text-sm font-medium text-gray-900">{item.description}</td>
                                                            <td className="px-6 py-4 text-sm text-gray-700 text-center font-medium">{item.quantity}</td>
                                                            <td className="px-6 py-4 text-sm text-gray-700 text-right font-medium">{formatAmount(item.rate)}</td>
                                                            <td className="px-6 py-4 text-sm text-gray-700 text-center font-medium">GST</td>
                                                            <td className="px-6 py-4 text-sm font-bold text-green-600 text-right">{formatAmount(item.amount)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        <div className="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-6 border-t">
                                            <div className="space-y-3">
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600 font-medium">Subtotal</span>
                                                    <span className="font-semibold text-gray-900">{formatAmount(invoice.subtotal)}</span>
                                                </div>
                                                {invoice.tax_amount > 0 && (
                                                    <div className="flex justify-between text-sm">
                                                        <span className="text-gray-600 font-medium">Tax</span>
                                                        <span className="font-semibold text-gray-900">{formatAmount(invoice.tax_amount)}</span>
                                                    </div>
                                                )}
                                                <Separator className="my-3" />
                                                <div className="flex justify-between text-xl font-bold">
                                                    <span className="text-gray-900">Total</span>
                                                    <span className="text-blue-600">{formatAmount(invoice.total_amount)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Additional Information */}
                            <Card className="shadow-sm border-0">
                                <CardHeader className="bg-gray-50 border-b">
                                    <CardTitle className="text-lg text-gray-900">Additional Information</CardTitle>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div>
                                            <h4 className="text-sm font-semibold text-gray-700 mb-2">NOTES</h4>
                                            <p className="text-sm text-gray-600">
                                                {invoice.notes || "Thank you for your business. Please remit payment by due date."}
                                            </p>
                                        </div>
                                        <div>
                                            <h4 className="text-sm font-semibold text-gray-700 mb-2">TERMS</h4>
                                            <p className="text-sm text-gray-600">
                                                Net 30 days. Late payment fee of 1.5% per month applies.
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>


                    </div>
                </div>

                {/* Payment Gateway Selection Modal */}
                <Dialog open={showGatewayModal} onOpenChange={setShowGatewayModal}>
                    <DialogContent className="max-w-md max-h-[80vh]">
                        <DialogHeader>
                            <DialogTitle className="text-center">Pay Invoice #{invoice.invoice_number}</DialogTitle>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-blue-700">Invoice #{invoice.invoice_number}</span>
                                    <span className="font-bold text-blue-900">{formatAmount(invoice.total_amount)}</span>
                                </div>
                                <div className="text-xs text-blue-600 mt-1">{invoice.client?.name}</div>
                                <div className="text-xs text-blue-600 mt-1">Remaining: {formatAmount(remainingAmount)}</div>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 mb-2 block">Payment Amount</label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max={remainingAmount}
                                    value={paymentAmount}
                                    onChange={(e) => setPaymentAmount(Number(e.target.value))}
                                    placeholder="Enter amount to pay"
                                    className="w-full"
                                />
                                <div className="flex gap-2 mt-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPaymentAmount(remainingAmount / 2)}
                                    >
                                        50%
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPaymentAmount(remainingAmount)}
                                    >
                                        Full Amount
                                    </Button>
                                </div>
                            </div>



                            <div>
                                <label className="text-sm font-medium text-gray-700 mb-3 block">Select Payment Method</label>
                                <div className="space-y-3 max-h-64 overflow-y-auto">
                                    {gatewaysWithIcons.map((gateway) => (
                                        <div
                                            key={gateway.id}
                                            className={`flex items-center p-4 border rounded-lg cursor-pointer transition-all ${selectedGateway === gateway.id
                                                    ? 'border-blue-500 bg-blue-50'
                                                    : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                            onClick={() => {
                                                try {
                                                    setSelectedGateway(gateway.id);
                                                } catch (error) {
                                                    console.error('Error selecting gateway:', error);
                                                    toast.error('Failed to select payment method. Please try again.');
                                                }
                                            }}
                                        >
                                            <div className="text-primary mr-3">
                                                {gateway.icon}
                                            </div>
                                            <span className="text-sm font-medium text-gray-900">{gateway.name}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex gap-3 pt-4">
                                <Button
                                    variant="outline"
                                    className="flex-1 h-12 bg-gray-800 text-white border-gray-800 hover:bg-gray-700"
                                    onClick={() => {
                                        try {
                                            setShowGatewayModal(false);
                                        } catch (error) {
                                            console.error('Error closing modal:', error);
                                            toast.error('An error occurred. Please refresh the page.');
                                        }
                                    }}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    className="flex-1 h-12 bg-blue-600 hover:bg-blue-700"
                                    onClick={() => {
                                        try {
                                            if (!selectedGateway) {
                                                toast.error('Please select a payment method first.');
                                                return;
                                            }
                                            if (!paymentAmount || paymentAmount <= 0) {
                                                toast.error('Please enter a valid payment amount.');
                                                return;
                                            }
                                            if (paymentAmount > remainingAmount) {
                                                toast.error('Payment amount cannot exceed remaining balance.');
                                                return;
                                            }
                                            setShowGatewayModal(false);
                                            setShowPaymentModal(true);
                                        } catch (error) {
                                            console.error('Error processing payment:', error);
                                            toast.error('Failed to process payment. Please try again.');
                                        }
                                    }}
                                    disabled={!selectedGateway || !paymentAmount || paymentAmount <= 0}
                                >
                                    Pay {formatAmount(paymentAmount)}
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                {/* Payment Modals */}
                {renderPaymentModal()}
            </div>
    </>
  );
}
