import { useForm, usePage } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';
import AuthButton from '@/components/auth/auth-button';
import Recaptcha, { executeRecaptcha } from '@/components/recaptcha';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useTranslation();
    const [recaptchaToken, setRecaptchaToken] = useState<string>('');
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const { props } = usePage();
    const { settings = {}, errors: pageErrors = {} } = props as any;
    const recaptchaEnabled = settings.recaptchaEnabled === 'true' || settings.recaptchaEnabled === true || settings.recaptchaEnabled === 1 || settings.recaptchaEnabled === '1';
    const { data, setData, post, processing, errors } = useForm<{ email: string; recaptcha_token?: string }>({
        email: '',
    });

    // Get error message from session flash data
    const errorMessage = pageErrors.error || (props as any).flash?.error;

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
        
        post(route('password.email'), {
            data: { ...data, recaptcha_token: tokenToSend },
        });
    };

    return (
        <AuthLayout
            title={t("Reset your password")}
            description={t("Enter your email to receive a secure password reset link")}
            icon={<Mail className="h-7 w-7" style={{ color: primaryColor }} />}
            status={status || errorMessage}
            statusType={errorMessage ? 'error' : 'success'}
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="space-y-4">
                    <div className="relative">
                        <Label htmlFor="email" className="block text-sm font-medium text-gray-900 mb-2">{t("Email")}</Label>
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
                </div>

                <Recaptcha
                    onVerify={setRecaptchaToken}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                <button 
                    type="submit" 
                    disabled={processing}
                    tabIndex={2}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed" 
                    style={{ backgroundColor: primaryColor }}
                >
                    {processing ? t("Sending...") : t("Email Password Reset Link")}
                </button>

                <div className="text-center">
                    <p className="text-sm text-gray-500">{t("Remember your password?")}{' '}
                        <TextLink
                            href={route('login')}
                            className="font-medium hover:underline"
                            style={{ color: primaryColor }}
                            tabIndex={3}
                        >
                            {t("Back to login")}
                        </TextLink>
                    </p>
                </div>
            </form>
        </AuthLayout>
    );
}
