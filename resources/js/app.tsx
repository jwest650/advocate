import '../css/app.css';
import '../css/dark-mode.css';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { lazy, Suspense } from 'react';
import { LayoutProvider } from './contexts/LayoutContext';
import { SidebarProvider } from './contexts/SidebarContext';
import { BrandProvider } from './contexts/BrandContext';
import { ModalStackProvider } from './contexts/ModalStackContext';
import { initializeTheme } from './hooks/use-appearance';
import { CustomToast } from './components/custom-toast';
import { initializeGlobalSettings } from './utils/globalSettings';
import { initPerformanceMonitoring, lazyLoadImages } from './utils/performance';
import { getCookie, isDemoMode } from './utils/cookies';
import './i18n'; // Import i18n configuration
import './utils/axios-config'; // Import axios configuration
import i18n from './i18n'; // Import i18n instance
// Initialize performance monitoring
initPerformanceMonitoring();

// Initialize lazy loading of images when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    lazyLoadImages();
});

// Add event listener for theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    // Re-apply theme when system preference changes
    let savedTheme = null;

    if (isDemoMode()) {
        savedTheme = getCookie('themeSettings');
    }

    if (savedTheme) {
        const themeSettings = JSON.parse(savedTheme);
        if (themeSettings.appearance === 'system') {
            initializeTheme();
        }
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
        return pages[`./pages/${name}.tsx`];
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Make page data globally available for axios interceptor
        try {
            (window as any).page = props.initialPage;
        } catch (e) {
            console.warn('Could not set global page data:', e);
        }

        // Set demo mode globally
        try {
            (window as any).isDemo = props.initialPage.props?.is_demo || false;
            (window as any).appConfig = {
                is_demo: props.initialPage.props?.is_demo || false
            };
        } catch (e) {
            // Ignore errors
        }

        // Sync language from database
        const syncLanguage = (pageProps: any) => {
              if (isDemoMode()) {
                return;
            }

            const userLanguage = pageProps?.props?.userLanguage;
            
            if (userLanguage) {
                // Only force LTR for dashboard pages, allow RTL for landing/auth pages
                if (isDashboardPage()) {
                    document.documentElement.dir = 'ltr';
                    document.documentElement.setAttribute('dir', 'ltr');
                }

                if (i18n.language !== userLanguage) {
                    i18n.changeLanguage(userLanguage);
                }
            }
        };

        // Initial language sync
        syncLanguage(props.initialPage);

        // Initialize global settings from shared data
        const globalSettings = props.initialPage.props.globalSettings || {};
        if (Object.keys(globalSettings).length > 0) {
            initializeGlobalSettings(globalSettings);
        }

        // Create a memoized render function to prevent unnecessary re-renders
        const renderApp = (appProps: any) => {
            const currentGlobalSettings = appProps.initialPage.props.globalSettings || {};
            const user = appProps.initialPage.props.auth?.user;

            return (
                <ModalStackProvider>
                    <LayoutProvider>
                        <SidebarProvider>
                            <BrandProvider globalSettings={currentGlobalSettings} user={user}>
                                <Suspense fallback={<div className="flex h-screen w-full items-center justify-center">Loading...</div>}>
                                    <App {...appProps} />
                                </Suspense>
                                <CustomToast />
                            </BrandProvider>
                        </SidebarProvider>
                    </LayoutProvider>
                </ModalStackProvider>
            );
        };

        // Initial render
        root.render(renderApp(props));

        // Update global page data on navigation and re-render with new settings
        router.on('navigate', (event) => {
            try {
                (window as any).page = event.detail.page;
                
                // Sync language from database AFTER render
                setTimeout(() => {
                    syncLanguage(event.detail.page);
                }, 0);
                
                // Re-render with updated props including globalSettings
                root.render(renderApp({ initialPage: event.detail.page }));

                // Force layout direction update from cookies/settings
                setTimeout(() => {
                    const isDemo = isDemoMode();
                    const currentLang = getCookie('app_language') || localStorage.getItem('i18nextLng') || 'en';
                    const isRTLLang = ['ar', 'he'].includes(currentLang);
                    let layoutDirection = 'left';
                    
                    if (isDemo) {
                        // In demo mode, prioritize saved layoutDirection over language
                        const savedDirection = getCookie('layoutDirection');
                        if (savedDirection) {
                            layoutDirection = savedDirection;
                        } else {
                            // Only use language-based direction if no saved preference
                            layoutDirection = isRTLLang ? 'right' : 'left';
                        }
                    } else {
                        // In live mode, prioritize database settings over language
                        const newGlobalSettings = event.detail.page.props.globalSettings;
                        if (newGlobalSettings?.layoutDirection) {
                            layoutDirection = newGlobalSettings.layoutDirection;
                        } else {
                            // Only use language-based direction if no saved preference
                            layoutDirection = isRTLLang ? 'right' : 'left';
                        }
                    }
                    
                    console.log('Navigation - Current lang:', currentLang, 'isRTL:', isRTLLang, 'Final direction:', layoutDirection);
                    
                    // Dispatch event to notify LayoutContext
                    window.dispatchEvent(new CustomEvent('layoutDirectionChanged', {
                        detail: { direction: layoutDirection }
                    }));
                }, 0);

                // Force dark mode check on navigation
                let savedTheme = null;

                if (isDemoMode()) {
                    savedTheme = getCookie('themeSettings');
                }

                if (savedTheme) {
                    const themeSettings = JSON.parse(savedTheme);
                    const isDark = themeSettings.appearance === 'dark' ||
                        (themeSettings.appearance === 'system' &&
                            window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', isDark);
                    document.body.classList.toggle('dark', isDark);
                }
            } catch (e) {
                console.error('Navigation error:', e);
            }
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Check if current page is dashboard (authenticated area)
const isDashboardPage = () => {
    const currentPath = window.location.pathname;
    // Dashboard pages typically don't include /login, /register, /forgot-password, etc.
    const authPages = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email'];
    const isLandingPage = currentPath === '/' || currentPath.startsWith('/landing');
    return !authPages.some(page => currentPath.includes(page)) && !isLandingPage;
};

// Set initial direction based on current language - run immediately
(function() {
    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    };
    
    const currentPath = window.location.pathname;
    const authPages = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email'];
    const isLandingPage = currentPath === '/' || currentPath.startsWith('/landing');
    const isDashboard = !authPages.some(page => currentPath.includes(page)) && !isLandingPage;
    const isAuthPage = authPages.some(page => currentPath.includes(page));
    
    // Only handle RTL for auth pages here, let landing page handle its own RTL
    if (isAuthPage) {
        const currentLang = getCookie('app_language') || localStorage.getItem('i18nextLng') || 'en';
        const isRTL = ['ar', 'he'].includes(currentLang);
        
        // Check if we're in demo mode
        const isDemo = getCookie('app_language') !== null;
        let layoutDirection = 'left';
        
        if (isDemo) {
            // In demo mode, get layoutDirection from cookie
            layoutDirection = getCookie('layoutDirection') || (isRTL ? 'right' : 'left');
        } else {
            // In live mode, use language-based direction
            layoutDirection = isRTL ? 'right' : 'left';
        }
        
        if (layoutDirection === 'right') {
            document.documentElement.dir = 'rtl';
            document.documentElement.setAttribute('dir', 'rtl');
        } else {
            document.documentElement.dir = 'ltr';
            document.documentElement.setAttribute('dir', 'ltr');
        }
    } else if (isDashboard) {
        // Force LTR for dashboard
        document.documentElement.dir = 'ltr';
        document.documentElement.setAttribute('dir', 'ltr');
    }
    // For landing pages, let the landing page component handle RTL
})();

// Force LTR direction only for dashboard pages
if (isDashboardPage()) {
    document.documentElement.dir = 'ltr';
    document.documentElement.setAttribute('dir', 'ltr');

    // Add interval to ensure LTR stays for dashboard
    setInterval(() => {
        if (isDashboardPage() && document.documentElement.dir !== 'ltr') {
            document.documentElement.dir = 'ltr';
            document.documentElement.setAttribute('dir', 'ltr');
        }
    }, 100);
}

// Global function to update direction (updated for RTL/LTR)
window.updateLayoutDirection = (lng) => {
    const isRTL = ['ar', 'he'].includes(lng);
    const direction = isRTL ? 'rtl' : 'ltr';
    
    const currentPath = window.location.pathname;
    const authPages = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email'];
    const isLandingPage = currentPath === '/' || currentPath.startsWith('/landing');
    const isDashboard = !authPages.some(page => currentPath.includes(page)) && !isLandingPage;
    
    // For dashboard pages, don't override - let LayoutContext handle it
    if (isDashboard) {
        // Check if user has saved layout preference
        const isDemo = isDemoMode();
        let hasSavedPreference = false;
        
        if (isDemo) {
            hasSavedPreference = !!getCookie('layoutDirection');
        } else {
            const globalSettings = (window as any).page?.props?.globalSettings;
            hasSavedPreference = !!globalSettings?.layoutDirection;
        }
        
        // Don't override saved preferences for RTL languages
        if (hasSavedPreference && isRTL) {
            console.log('Not overriding saved layout preference for RTL language:', lng);
            return;
        }
    }
    
    // Only apply RTL/LTR if not on dashboard pages or no saved preference
    if (!isDashboard) {
        document.documentElement.dir = direction;
        document.documentElement.setAttribute('dir', direction);
        
        // Force layout recalculation
        document.documentElement.classList.add('direction-changed');
        window.dispatchEvent(new Event('resize'));
        
        setTimeout(() => {
            document.documentElement.classList.remove('direction-changed');
        }, 100);
        
        // Dispatch custom event for landing page to listen to
        if (isLandingPage) {
            window.dispatchEvent(new CustomEvent('languageDirectionChanged', {
                detail: { language: lng, direction: direction }
            }));
        }
    }
};

// Override i18n changeLanguage to update direction immediately
const originalChangeLanguage = i18n.changeLanguage;
i18n.changeLanguage = function(lng, callback) {
    // Check if user has saved layout preference before changing language
    const isDemo = isDemoMode();
    let savedLayoutDirection = null;
    
    if (isDemo) {
        savedLayoutDirection = getCookie('layoutDirection');
    } else {
        const globalSettings = (window as any).page?.props?.globalSettings;
        savedLayoutDirection = globalSettings?.layoutDirection;
    }
    
    const result = originalChangeLanguage.call(this, lng, callback);
    
    // Only update direction if no saved preference exists
    if (!savedLayoutDirection) {
        window.updateLayoutDirection(lng);
    } else {
        console.log('Preserving saved layout direction:', savedLayoutDirection, 'for language:', lng);
    }
    
    return result;
};

// Listen for i18n events as backup
i18n.on('languageChanged', (lng) => {
    // Check if user has saved layout preference
    const isDemo = isDemoMode();
    let savedLayoutDirection = null;
    
    if (isDemo) {
        savedLayoutDirection = getCookie('layoutDirection');
    } else {
        const globalSettings = (window as any).page?.props?.globalSettings;
        savedLayoutDirection = globalSettings?.layoutDirection;
    }
    
    // Only update direction if no saved preference exists
    if (!savedLayoutDirection) {
        window.updateLayoutDirection(lng);
    }
});
i18n.on('loaded', () => {
    // Check if user has saved layout preference
    const isDemo = isDemoMode();
    let savedLayoutDirection = null;
    
    if (isDemo) {
        savedLayoutDirection = getCookie('layoutDirection');
    } else {
        const globalSettings = (window as any).page?.props?.globalSettings;
        savedLayoutDirection = globalSettings?.layoutDirection;
    }
    
    // Only update direction if no saved preference exists
    if (!savedLayoutDirection) {
        window.updateLayoutDirection(i18n.language);
    }
});
