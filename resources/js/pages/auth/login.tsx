import { useForm, router, usePage } from '@inertiajs/react';
import { Mail, Lock } from 'lucide-react';
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


    return (
        <AuthLayout
            title={t("Log in to your account")}
            description={t("Enter your credentials to access your account")}
            status={status}
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="space-y-4">
                    <div className="relative">
                        <div className="flex justify-between items-center mb-2">
                            <Label htmlFor="email" className="block text-sm font-medium text-gray-900">{t("Email")}</Label>
                        </div>
                        <div className="relative">
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
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400"
                                onFocus={(e) => e.target.style.borderColor = primaryColor}
                                onBlur={(e) => e.target.style.borderColor = "rgb(209 213 219)"}
                            />
                        </div>
                        <InputError message={errors.email} />
                    </div>

                    <div>
                        <div className="flex justify-between items-center mb-2">
                            <Label htmlFor="password" className="block text-sm font-medium text-gray-900">{t("Password")}</Label>
                            {canResetPassword && (
                                <TextLink
                                    href={route('password.request')}
                                    className="text-sm no-underline hover:underline hover:underline-primary"
                                    style={{ color: primaryColor }}
                                    tabIndex={5}
                                >
                                    {t("Forgot password?")}
                                </TextLink>
                            )}
                        </div>
                        <div className="relative">
                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder={t("Enter your password")}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400"
                                onFocus={(e) => e.target.style.borderColor = primaryColor}
                                onBlur={(e) => e.target.style.borderColor = "rgb(209 213 219)"}
                            />
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center !mt-4 !mb-5">
                       <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onClick={() => setData('remember', !data.remember)}
                            tabIndex={3}
                            className="border border-gray-300 rounded"
                        />
                        <Label htmlFor="remember" className="ms-2 text-sm text-gray-600">{t("Remember me")}</Label>
                    </div>
                </div>

                <Recaptcha
                    onVerify={setRecaptchaToken}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                <button 
                    type="submit" 
                    disabled={processing}
                    tabIndex={4}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed" 
                    style={{ backgroundColor: primaryColor }}
                >
                    {processing ? t("Log in...") : t("Log in")}
                </button>

                <div className="text-center">
                    <p className="text-sm text-gray-500">{t("Don't have an account?")}{' '}
                        <TextLink
                            href={route('register')}
                            className="font-medium hover:underline"
                            style={{ color: primaryColor }}
                            tabIndex={6}
                        >
                            {t("Sign Up")}
                        </TextLink>
                    </p>
                </div>

                {isDemo && (
                    <div className="mt-5">
                        <div className="flex items-center">
                            <div className="flex-1 h-px bg-gray-200"></div>
                            <div className="w-2 h-2 rotate-45 mx-4" style={{ backgroundColor: primaryColor }}></div>
                            <div className="flex-1 h-px bg-gray-200"></div>
                        </div>
                    </div>
                )}

                {isDemo && (
                    <div>
                        <h3 className="text-sm font-medium text-gray-900 tracking-wider mb-4 text-center">{t('Quick Access')}</h3>
                        <div className="grid sm:grid-cols-2 gap-3">
                            <Button
                                type="button"
                                onClick={async () => {
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
                                        email: 'superadmin@example.com',
                                        password: 'password',
                                        remember: false,
                                        recaptcha_token: tokenToSend
                                    });
                                }}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                            >
                                {t('Login as Super Admin')}
                            </Button>
                            <Button
                                type="button"
                                onClick={async () => {
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
                                        email: 'company@example.com',
                                        password: 'password',
                                        remember: false,
                                        recaptcha_token: tokenToSend
                                    });
                                }}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                            >
                                {t('Login as Company')}
                            </Button>
                            <Button
                                type="button"
                                onClick={async () => {
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
                                        email: 'michael_brown_2@example.com',
                                        password: 'password',
                                        remember: false,
                                        recaptcha_token: tokenToSend
                                    });
                                }}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                            >
                                {t('Login as Client')}
                            </Button>
                            <Button
                                type="button"
                                onClick={async () => {
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
                                        email: 'linda_davis_2@example.com',
                                        password: 'password',
                                        remember: false,
                                        recaptcha_token: tokenToSend
                                    });
                                }}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                            >
                                {t('Login as Team Member')}
                            </Button>
                        </div>
                    </div>
                )}
            </form>
        </AuthLayout>
    );
}
