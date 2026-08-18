import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { CreditCard, Users, Smartphone, QrCode } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LanguageSwitcher } from '@/components/language-switcher';
import AuthLanguageDropdown from '@/components/auth/auth-language-dropdown';
import { useBrand } from '@/contexts/BrandContext';
import { useAppearance, THEME_COLORS } from '@/hooks/use-appearance';
import { useFavicon } from '@/hooks/use-favicon';
import CookieConsentBanner from '@/components/CookieConsentBanner';

interface AuthLayoutProps {
    children: ReactNode;
    title: string;
    description?: string;
    icon?: ReactNode;
    status?: string;
    statusType?: 'success' | 'error';
}

function hexToAdjustedRgba(hex: string, opacity = 1, adjust: number | Record<string, number> = 0) {
    hex = hex.replace("#", "");
    let r = parseInt(hex.slice(0, 2), 16);
    let g = parseInt(hex.slice(2, 4), 16);
    let b = parseInt(hex.slice(4, 6), 16);
    const clamp = (v: number) => Math.max(-1, Math.min(1, v));
    const getF = (ch: string) =>
        typeof adjust === "number" ? clamp(adjust) : clamp(adjust[ch] ?? 0);
    const adj = (c: number, f: number) =>
        f < 0 ? Math.floor(c * (1 + f)) : Math.floor(c + (255 - c) * f);
    const rr = adj(r, getF("r"));
    const gg = adj(g, getF("g"));
    const bb = adj(b, getF("b"));
    return opacity === 1
        ? `#${rr.toString(16).padStart(2, "0")}${gg
            .toString(16)
            .padStart(2, "0")}${bb.toString(16).padStart(2, "0")}`.toUpperCase()
        : `rgba(${rr}, ${gg}, ${bb}, ${opacity})`;
}


export default function AuthLayout({
    children,
    title,
    description,
    icon,
    status,
    statusType = 'success',
}: AuthLayoutProps) {
    useFavicon();
    const { t } = useTranslation();
    const [mounted, setMounted] = useState(false);
    const { logoLight, logoDark, themeColor, customColor } = useBrand();
    const { appearance } = useAppearance();
    const globalSettings = (usePage().props as any).globalSettings;
    const userLanguage = (usePage().props as any).userLanguage;

    const currentLogo = appearance === 'dark' ? logoLight : logoDark;
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];

    useEffect(() => {
        setMounted(true);
    }, []);

    // RTL Support for auth pages - Apply immediately and persist
    const applyRTLDirection = React.useCallback(() => {
        const isDemo = globalSettings?.is_demo || false;
        const currentLang = userLanguage || globalSettings?.defaultLanguage || 'en';
        const isRTLLanguage = ['ar', 'he'].includes(currentLang);
        let dir = 'ltr';

        const getCookie = (name: string): string | null => {
            if (typeof document === 'undefined') return null;
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) {
                const cookieValue = parts.pop()?.split(';').shift();
                return cookieValue ? decodeURIComponent(cookieValue) : null;
            }
            return null;
        };

        // Check RTL setting from cookies/globalSettings
        const layoutDirection = isDemo ? getCookie('layoutDirection') : globalSettings?.layoutDirection;
        const isRTLSetting = layoutDirection === 'right';

        // Apply RTL if: 1) Language is ar/he OR 2) RTL setting is enabled
        if (isRTLLanguage || isRTLSetting) {
            dir = 'rtl';
        }

        // Apply direction immediately
        document.documentElement.dir = dir;
        document.documentElement.setAttribute('dir', dir);
        document.body.dir = dir;

        return dir;
    }, [userLanguage, globalSettings?.defaultLanguage, globalSettings?.is_demo, globalSettings?.layoutDirection]);

    // Apply RTL on mount and when dependencies change
    React.useLayoutEffect(() => {
        const direction = applyRTLDirection();

        // Listen for language changes from the language switcher
        const handleLanguageChange = (event: CustomEvent) => {
            const { language, direction: newDirection } = event.detail;
            // Convert 'right'/'left' to 'rtl'/'ltr'
            const dir = newDirection === 'right' ? 'rtl' : 'ltr';

            document.documentElement.dir = dir;
            document.documentElement.setAttribute('dir', dir);
            document.body.dir = dir;
        };

        // Listen for both standard and landing page language change events
        window.addEventListener('languageChanged', handleLanguageChange as EventListener);
        window.addEventListener('languageDirectionChanged', handleLanguageChange as EventListener);

        // Ensure direction persists after any DOM changes
        const observer = new MutationObserver(() => {
            const currentDir = document.documentElement.dir;
            if (currentDir !== direction && !['rtl', 'ltr'].includes(currentDir)) {
                document.documentElement.dir = direction;
                document.documentElement.setAttribute('dir', direction);
            }
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['dir']
        });

        // Cleanup function
        return () => {
            observer.disconnect();
            window.removeEventListener('languageChanged', handleLanguageChange as EventListener);
            window.removeEventListener('languageDirectionChanged', handleLanguageChange as EventListener);
            // Reset to LTR when leaving auth layout
            document.documentElement.dir = 'ltr';
            document.documentElement.setAttribute('dir', 'ltr');
            document.body.dir = 'ltr';
        };
    }, [applyRTLDirection]);


     return (
        <div className="relative min-h-screen overflow-hidden bg-slate-50">
            <Head title={title} />

            {/* Scoped animations for the auth experience */}
            <style>{`
                @keyframes authFadeUp {
                    from { opacity: 0; transform: translateY(18px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes authScaleIn {
                    from { opacity: 0; transform: translateY(22px) scale(.97); }
                    to { opacity: 1; transform: translateY(0) scale(1); }
                }
                @keyframes authFloat {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-8px); }
                }
                @keyframes authDrift {
                    0%   { transform: translate(0, 0) scale(1); }
                    33%  { transform: translate(4%, -6%) scale(1.08); }
                    66%  { transform: translate(-5%, 4%) scale(.95); }
                    100% { transform: translate(0, 0) scale(1); }
                }
                @keyframes authSheen {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }
                @keyframes authGrow {
                    from { width: 0; opacity: 0; }
                    to { width: 3rem; opacity: 1; }
                }
                .auth-anim { opacity: 0; animation: authFadeUp .6s cubic-bezier(.16,.84,.44,1) forwards; }
                .auth-card-anim { opacity: 0; animation: authScaleIn .7s cubic-bezier(.16,.84,.44,1) forwards; }
                .auth-blob { animation: authDrift 18s ease-in-out infinite; will-change: transform; }
                .auth-logo { animation: authFloat 6s ease-in-out infinite; }
                .auth-sheen { animation: authSheen 6s ease-in-out infinite; }
                .auth-rule { animation: authGrow .8s cubic-bezier(.16,.84,.44,1) .25s forwards; width: 0; }
                @media (prefers-reduced-motion: reduce) {
                    .auth-anim, .auth-card-anim { animation: none; opacity: 1; }
                    .auth-blob, .auth-logo, .auth-sheen { animation: none; }
                    .auth-rule { animation: none; width: 3rem; }
                }
            `}</style>

            {/* Enhanced Background Design */}
            <div className="pointer-events-none absolute inset-0">
                {/* Base Gradient */}
                <div className="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-slate-100" />

                {/* Soft brand aurora */}
                <div
                    className="auth-blob absolute -top-32 -start-24 h-[28rem] w-[28rem] rounded-full blur-3xl"
                    style={{ background: `radial-gradient(circle at 30% 30%, ${primaryColor}33, transparent 70%)` }}
                />
                <div
                    className="auth-blob absolute -bottom-40 -end-28 h-[32rem] w-[32rem] rounded-full blur-3xl"
                    style={{ background: `radial-gradient(circle at 60% 40%, ${primaryColor}26, transparent 70%)`, animationDelay: '-6s' }}
                />
                <div
                    className="auth-blob absolute top-1/3 start-1/2 h-72 w-72 -translate-x-1/2 rounded-full blur-3xl"
                    style={{ background: `radial-gradient(circle, ${primaryColor}1f, transparent 70%)`, animationDelay: '-12s' }}
                />

                {/* Elegant Pattern Overlay */}
                <div className="absolute inset-0 opacity-[0.18]" style={{
                    backgroundImage: `radial-gradient(circle at 30% 70%, ${primaryColor} 1px, transparent 1px)`,
                    backgroundSize: '80px 80px'
                }} />

                {/* Fine grid */}
                <div className="absolute inset-0 opacity-[0.35]" style={{
                    backgroundImage: `linear-gradient(to right, rgba(15,23,42,.035) 1px, transparent 1px), linear-gradient(to bottom, rgba(15,23,42,.035) 1px, transparent 1px)`,
                    backgroundSize: '56px 56px',
                    maskImage: 'radial-gradient(ellipse at center, black 30%, transparent 75%)',
                    WebkitMaskImage: 'radial-gradient(ellipse at center, black 30%, transparent 75%)'
                }} />
            </div>

            {/* Language Dropdown */}
            <div className="auth-anim absolute top-6 right-6 z-10 hidden md:block">
                <LanguageSwitcher />
            </div>

            <div className="flex min-h-screen items-center justify-center p-6">
                <div className="w-full max-w-md">
                    {/* Logo */}
                    <div className="auth-anim mb-8 text-center" style={{ animationDelay: '80ms' }}>
                        <div className="relative pb-2 lg:inline-block lg:px-6">
                            <div
                                className="absolute inset-0 -z-10 mx-auto blur-2xl"
                                style={{ background: `radial-gradient(ellipse at center, ${primaryColor}26, transparent 70%)` }}
                            />
                            {currentLogo ? (
                                <img src={currentLogo} alt="Logo" className="auth-logo mx-auto w-auto" />
                            ) : (
                                <CreditCard className="auth-logo mx-auto h-8 w-8" style={{ color: primaryColor }} />
                            )}
                        </div>
                    </div>

                    {/* Main Card */}
                    <div className="auth-card-anim relative" style={{ animationDelay: '140ms' }}>
                        {/* Ambient glow behind the card */}
                        <div
                            className="pointer-events-none absolute -inset-x-6 -bottom-6 top-8 -z-10 rounded-[2rem] blur-2xl"
                            style={{ background: `linear-gradient(135deg, ${primaryColor}1f, transparent 60%)` }}
                        />

                        {/* Corner accents */}
                        <div className="absolute -top-3 -start-3 h-6 w-6 rounded-ss-md border-t-2 border-s-2 transition-all duration-500" style={{ borderColor: primaryColor }} />
                        <div className="absolute -bottom-3 -end-3 h-6 w-6 rounded-ee-md border-b-2 border-e-2 transition-all duration-500" style={{ borderColor: primaryColor }} />

                        <div className="relative overflow-hidden rounded-2xl border border-white/70 bg-white/80 p-4 shadow-[0_20px_60px_-20px_rgba(15,23,42,0.18),0_2px_8px_-2px_rgba(15,23,42,0.06)] ring-1 ring-slate-900/[0.04] backdrop-blur-xl lg:p-8 lg:pt-6">
                            {/* Animated hairline on top edge */}
                            <div className="absolute inset-x-0 top-0 h-px overflow-hidden">
                                <div className="h-full w-full" style={{ background: `linear-gradient(90deg, transparent, ${primaryColor}80, transparent)` }} />
                                <div className="auth-sheen absolute inset-y-0 w-1/3" style={{ background: `linear-gradient(90deg, transparent, ${primaryColor}, transparent)` }} />
                            </div>

                            {/* Header */}
                            <div className="mb-5 text-center">
                                {icon && (
                                    <div
                                        className="auth-anim mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl ring-1 ring-inset"
                                        style={{
                                            backgroundColor: `${primaryColor}14`,
                                            boxShadow: `0 8px 24px -12px ${primaryColor}99`,
                                            // ring color via CSS custom property
                                            '--tw-ring-color': `${primaryColor}2e`,
                                            animationDelay: '200ms',
                                        } as React.CSSProperties}
                                    >
                                        {icon}
                                    </div>
                                )}
                                <h1 className="auth-anim mb-2 text-[1.6rem] leading-tight font-semibold tracking-tight text-slate-900" style={{ animationDelay: '220ms' }}>
                                    {title}
                                </h1>
                                <div
                                    className="auth-rule mx-auto mb-3 h-[3px] rounded-full"
                                    style={{ backgroundImage: `linear-gradient(90deg, ${primaryColor}00, ${primaryColor}, ${primaryColor}00)` }}
                                />
                                {description && (
                                    <p className="auth-anim text-sm text-slate-500" style={{ animationDelay: '280ms' }}>{description}</p>
                                )}
                            </div>

                            {status && (
                                <div className={`auth-anim mb-6 flex items-start gap-2.5 rounded-xl border p-3 text-sm font-medium ${statusType === 'success'
                                        ? 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800/30'
                                        : 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/30'
                                    }`} style={{ animationDelay: '300ms' }}>
                                    <span className={`mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full ${statusType === 'success' ? 'bg-green-500' : 'bg-red-500'}`} />
                                    <span className="text-start">{status}</span>
                                </div>
                            )}

                            {children}
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="auth-anim mt-7 text-center" style={{ animationDelay: '520ms' }}>
                        <div className="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/70 px-4 py-2 shadow-sm ring-1 ring-slate-900/[0.03] backdrop-blur-md transition-all duration-300 hover:bg-white">
                            <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: primaryColor }} />
                            <p className="text-[13px] text-slate-500">{globalSettings?.footerText || '© 2026 Advocate SaaS'}</p>
                        </div>
                    </div>
                </div>
            </div>
            <CookieConsentBanner />
        </div>
    );
}
