import { useForm, router, usePage } from '@inertiajs/react';
import { Mail, Lock, Loader2, ArrowRight, ShieldCheck, Building2, User, Users, Sparkles } from 'lucide-react';
import { FormEventHandler, useState, useEffect } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';
import AuthButton from '@/components/auth/auth-button';
import Recaptcha, { executeRecaptcha } from '@/components/recaptcha';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';
import { Button } from '@/components/ui/button';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
    recaptcha_token?: string;
};

interface Business {
    id: number;
    name: string;
    slug: string;
    business_type: string;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    demoBusinesses?: Business[];
}

export default function Login({ status, canResetPassword, demoBusinesses = [] }: LoginProps) {
    const { t } = useTranslation();
    const [recaptchaToken, setRecaptchaToken] = useState<string>('');
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const [isDemo, setIsDemo] = useState<boolean>(false);
    // Always show business buttons by default
    const [showBusinessButtons, setShowBusinessButtons] = useState<boolean>(true);
    const [focusedField, setFocusedField] = useState<string | null>(null);
    const { props } = usePage();
    const { settings = {} } = props as any;
    const isDemos = (props as any).globalSettings?.is_demo;
    const recaptchaEnabled = settings.recaptchaEnabled === 'true' || settings.recaptchaEnabled === true || settings.recaptchaEnabled === 1 || settings.recaptchaEnabled === '1';

    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        // Check if demo mode is enabled
        // const isDemoMode = (window as any).isDemo === true;
        setIsDemo(isDemos);

        // Set default credentials if in demo mode
        if (isDemos) {
            setData({
                email: 'company@example.com',
                password: 'password',
                remember: false
            });
        }
    }, []);

    const submit: FormEventHandler = async (e) => {
        e.preventDefault();

        let tokenToSend = recaptchaToken;

        if (recaptchaEnabled) {
            try {
                const token = await executeRecaptcha();
                if (!token) {
                    alert(t('Please complete the reCAPTCHA verification'));
                    return;
                }
                tokenToSend = token;
            } catch {
                alert(t('reCAPTCHA verification failed. Please try again.'));
                return;
            }
        }

        post(route('login'), {
            ...data,
            recaptcha_token: tokenToSend || ''
        }, {
            onFinish: () => reset('password'),
        });
    };

    const quickLogin = async (email: string) => {
        let tokenToSend = recaptchaToken || '';

        if (recaptchaEnabled) {
            try {
                const token = await executeRecaptcha();
                if (!token) {
                    alert(t('Please complete the reCAPTCHA verification'));
                    return;
                }
                tokenToSend = token;
            } catch {
                alert(t('reCAPTCHA verification failed. Please try again.'));
                return;
            }
        }

        router.post(route('login'), {
            email,
            password: 'password',
            remember: false,
            recaptcha_token: tokenToSend
        });
    };

    const demoAccounts = [
        { label: t('Login as Super Admin'), email: 'superadmin@example.com', icon: ShieldCheck },
        { label: t('Login as Company'), email: 'company@example.com', icon: Building2 },
        { label: t('Login as Client'), email: 'michael_brown_2@example.com', icon: User },
        { label: t('Login as Team Member'), email: 'linda_davis_2@example.com', icon: Users },
    ];

    const fieldWrapperStyle = (name: string) => ({
        boxShadow: focusedField === name ? `0 0 0 4px ${primaryColor}1f` : '0 1px 2px rgba(16,24,40,0.04)',
        borderColor: focusedField === name ? primaryColor : 'rgb(226 232 240)',
    });

    return (
        <AuthLayout
            title={t("Log in to your account")}
            description={t("Enter your credentials to access your account")}
            status={status}
        >
            {/* Scoped animations for the login experience */}
            <style>{`
                @keyframes lgFadeUp {
                    from { opacity: 0; transform: translateY(14px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes lgShimmer {
                    0% { transform: translateX(-120%); }
                    100% { transform: translateX(220%); }
                }
                @keyframes lgGlow {
                    0%, 100% { opacity: .35; transform: scale(1); }
                    50% { opacity: .7; transform: scale(1.06); }
                }
                .lg-reveal {
                    opacity: 0;
                    animation: lgFadeUp .55s cubic-bezier(.16,.84,.44,1) forwards;
                }
                .lg-shimmer::after {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(100deg, transparent 20%, rgba(255,255,255,.35) 50%, transparent 80%);
                    transform: translateX(-120%);
                }
                .lg-shimmer:hover::after {
                    animation: lgShimmer 1.1s ease-in-out;
                }
                @media (prefers-reduced-motion: reduce) {
                    .lg-reveal { animation: none; opacity: 1; }
                    .lg-shimmer:hover::after { animation: none; }
                }
            `}</style>

            <form className="space-y-5" onSubmit={submit}>
                <div className="space-y-4">
                    {/* Email */}
                    <div className="lg-reveal" style={{ animationDelay: '60ms' }}>
                        <div className="mb-2 flex items-center justify-between">
                            <Label
                                htmlFor="email"
                                className="block text-[13px] font-medium tracking-wide transition-colors duration-300"
                                style={{ color: focusedField === 'email' ? primaryColor : 'rgb(55 65 81)' }}
                            >
                                {t("Email")}
                            </Label>
                        </div>
                        <div
                            className="group relative rounded-xl border bg-slate-50/70 transition-all duration-300 focus-within:bg-white"
                            style={fieldWrapperStyle('email')}
                        >
                            <Mail
                                className="pointer-events-none absolute start-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 transition-all duration-300"
                                style={{ color: focusedField === 'email' ? primaryColor : 'rgb(148 163 184)' }}
                            />
                            <Input
                                id="email"
                                type="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder={t("Enter your email")}
                                className="h-12 w-full rounded-xl border-0 bg-transparent ps-11 pe-4 text-sm text-gray-900 shadow-none outline-none placeholder:text-slate-400 focus-visible:border-0 focus-visible:ring-0"
                                onFocus={() => setFocusedField('email')}
                                onBlur={() => setFocusedField(null)}
                            />
                        </div>
                        <InputError message={errors.email} />
                    </div>

                    {/* Password */}
                    <div className="lg-reveal" style={{ animationDelay: '140ms' }}>
                        <div className="mb-2 flex items-center justify-between">
                            <Label
                                htmlFor="password"
                                className="block text-[13px] font-medium tracking-wide transition-colors duration-300"
                                style={{ color: focusedField === 'password' ? primaryColor : 'rgb(55 65 81)' }}
                            >
                                {t("Password")}
                            </Label>
                            {canResetPassword && (
                                <TextLink
                                    href={route('password.request')}
                                    className="group text-[13px] font-medium no-underline transition-opacity duration-200 hover:opacity-80"
                                    style={{ color: primaryColor }}
                                    tabIndex={5}
                                >
                                    <span className="relative">
                                        {t("Forgot password?")}
                                        <span
                                            className="absolute -bottom-0.5 start-0 h-px w-0 transition-all duration-300 group-hover:w-full"
                                            style={{ backgroundColor: primaryColor }}
                                        />
                                    </span>
                                </TextLink>
                            )}
                        </div>
                        <div
                            className="group relative rounded-xl border bg-slate-50/70 transition-all duration-300 focus-within:bg-white"
                            style={fieldWrapperStyle('password')}
                        >
                            <Lock
                                className="pointer-events-none absolute start-3.5 top-1/2 z-10 h-[18px] w-[18px] -translate-y-1/2 transition-all duration-300"
                                style={{ color: focusedField === 'password' ? primaryColor : 'rgb(148 163 184)' }}
                            />
                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder={t("Enter your password")}
                                className="h-12 w-full rounded-xl border-0 bg-transparent ps-11 pe-11 text-sm text-gray-900 shadow-none outline-none placeholder:text-slate-400 focus-visible:border-0 focus-visible:ring-0"
                                onFocus={() => setFocusedField('password')}
                                onBlur={() => setFocusedField(null)}
                            />
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    {/* Remember me */}
                    <div className="lg-reveal !mt-4 !mb-5 flex items-center" style={{ animationDelay: '220ms' }}>
                        <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onClick={() => setData('remember', !data.remember)}
                            tabIndex={3}
                            className="rounded-[5px] border border-slate-300 transition-all duration-200 data-[state=checked]:scale-105"
                            style={data.remember ? { backgroundColor: primaryColor, borderColor: primaryColor } : undefined}
                        />
                        <Label htmlFor="remember" className="ms-2 cursor-pointer text-[13px] text-slate-600 transition-colors duration-200 hover:text-slate-900">
                            {t("Remember me")}
                        </Label>
                    </div>
                </div>

                <Recaptcha
                    onVerify={setRecaptchaToken}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                {/* Submit */}
                <div className="lg-reveal relative" style={{ animationDelay: '300ms' }}>
                    <div
                        className="absolute inset-x-3 -bottom-1 h-8 rounded-full blur-xl"
                        style={{ backgroundColor: primaryColor, animation: 'lgGlow 3.5s ease-in-out infinite' }}
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        tabIndex={4}
                        className="lg-shimmer group relative w-full overflow-hidden rounded-xl py-3 text-sm font-semibold tracking-wide text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl active:translate-y-0 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
                        style={{
                            backgroundImage: `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}d9 55%, ${primaryColor} 100%)`,
                            boxShadow: `0 8px 24px -8px ${primaryColor}99`,
                        }}
                    >
                        <span className="relative z-10 flex items-center justify-center gap-2">
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    {t("Log in...")}
                                </>
                            ) : (
                                <>
                                    {t("Log in")}
                                    <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 rtl:rotate-180 rtl:group-hover:-translate-x-1" />
                                </>
                            )}
                        </span>
                    </button>
                </div>

                <div className="lg-reveal text-center" style={{ animationDelay: '360ms' }}>
                    <p className="text-[13px] text-slate-500">{t("Don't have an account?")}{' '}
                        <TextLink
                            href={route('register')}
                            className="group relative font-semibold no-underline"
                            style={{ color: primaryColor }}
                            tabIndex={6}
                        >
                            <span className="relative">
                                {t("Sign Up")}
                                <span
                                    className="absolute -bottom-0.5 start-0 h-px w-0 transition-all duration-300 group-hover:w-full"
                                    style={{ backgroundColor: primaryColor }}
                                />
                            </span>
                        </TextLink>
                    </p>
                </div>

                {isDemo && (
                    <div className="lg-reveal mt-6" style={{ animationDelay: '420ms' }}>
                        <div className="flex items-center gap-3">
                            <div className="h-px flex-1 bg-gradient-to-r from-transparent to-slate-200" />
                            <span
                                className="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold tracking-wider uppercase"
                                style={{ backgroundColor: `${primaryColor}14`, color: primaryColor }}
                            >
                                <Sparkles className="h-3 w-3" />
                                {t('Quick Access')}
                            </span>
                            <div className="h-px flex-1 bg-gradient-to-l from-transparent to-slate-200" />
                        </div>
                    </div>
                )}

                {isDemo && (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {demoAccounts.map((account, index) => {
                            const Icon = account.icon;
                            return (
                                <Button
                                    key={account.email}
                                    type="button"
                                    onClick={() => quickLogin(account.email)}
                                    className="lg-reveal group relative h-auto justify-start gap-2.5 overflow-hidden rounded-xl border bg-white px-3.5 py-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-white hover:text-slate-900 hover:shadow-lg"
                                    style={{
                                        borderColor: 'rgb(226 232 240)',
                                        animationDelay: `${480 + index * 70}ms`,
                                    }}
                                >
                                    <span
                                        className="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                        style={{ backgroundImage: `linear-gradient(135deg, ${primaryColor}12, transparent 70%)` }}
                                    />
                                    <span
                                        className="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-transform duration-300 group-hover:scale-110"
                                        style={{ backgroundColor: `${primaryColor}1a`, color: primaryColor }}
                                    >
                                        <Icon className="h-[15px] w-[15px]" />
                                    </span>
                                    <span className="relative z-10 truncate text-start">{account.label}</span>
                                    <ArrowRight
                                        className="relative z-10 ms-auto h-3.5 w-3.5 shrink-0 opacity-0 transition-all duration-300 group-hover:translate-x-0.5 group-hover:opacity-100 rtl:rotate-180"
                                        style={{ color: primaryColor }}
                                    />
                                </Button>
                            );
                        })}
                    </div>
                )}
            </form>
        </AuthLayout>
    );
}
