import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { getBrandSettings, type BrandSettings } from '@/pages/settings/components/brand-settings';
import { getImagePath } from '@/utils/helpers';
import { setCookie, getCookie, isDemoMode } from '@/utils/cookies';

interface BrandContextType extends BrandSettings {
  updateBrandSettings: (settings: Partial<BrandSettings>) => void;
}

const BrandContext = createContext<BrandContextType | undefined>(undefined);

export function BrandProvider({ children, globalSettings, user }: { children: ReactNode; globalSettings?: any; user?: any }) {
  // Determine which settings to use based on user role and route
  const getEffectiveSettings = () => {
    const isDemo = globalSettings?.is_demo || false;

    // In demo mode, prioritize cookies over database settings
    if (isDemo) {
      return null; // This will force getBrandSettings to use cookies
    }

    const isPublicRoute = window.location.pathname.includes('/public/') ||
      window.location.pathname === '/' ||
      window.location.pathname.includes('/auth/');

    // For public routes (landing page, auth pages), always use superadmin settings
    if (isPublicRoute) {
      return globalSettings;
    }

    // For authenticated routes, use user's own settings if company role
    if (user?.role === 'company' && user?.globalSettings) {
      return user.globalSettings;
    }

    // Default to global settings (superadmin)
    return globalSettings;
  };

  const [brandSettings, setBrandSettings] = useState<BrandSettings>(() => {
    const settings = getBrandSettings(getEffectiveSettings(), globalSettings);
    // In demo mode, ensure we have default logos if none are set
    const isDemo = globalSettings?.is_demo || false;
    return {
      ...settings,
      logoDark: getImagePath(settings.logoDark || (isDemo ? 'images/logos/logo-dark.png' : '')),
      logoLight: getImagePath(settings.logoLight || (isDemo ? 'images/logos/logo-light.png' : '')),
      favicon: getImagePath(settings.favicon || (isDemo ? 'images/logos/favicon.png' : ''))
    };
  });

  // Apply theme settings immediately for landing page (both demo and non-demo modes)
  useEffect(() => {
    if (brandSettings && typeof window !== 'undefined') {
      // Apply theme color globally
      const color = brandSettings.themeColor === 'custom' ? brandSettings.customColor : {
        blue: '#3b82f6',
        green: '#10b77f',
        purple: '#8b5cf6',
        orange: '#f97316',
        red: '#ef4444'
      }[brandSettings.themeColor] || '#3b82f6';

      document.documentElement.style.setProperty('--theme-color', color);
      document.documentElement.style.setProperty('--primary', color);
      document.documentElement.style.setProperty('--chart-1', color);

      // Apply theme mode
      const isDark = brandSettings.themeMode === 'dark' ||
        (brandSettings.themeMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      document.documentElement.classList.toggle('dark', isDark);
      document.body.classList.toggle('dark', isDark);

      // Always keep document direction as LTR to prevent sidebar issues
      document.documentElement.dir = 'ltr';
      document.documentElement.setAttribute('dir', 'ltr');
      
      // Save brand layout direction to cookies to ensure it persists
      if (isDemoMode()) {
        setCookie('layoutDirection', brandSettings.layoutDirection);
        setCookie('layoutPosition', brandSettings.layoutDirection);
      }

      // Force a small repaint to ensure colors are applied
      const tempClass = 'theme-color-updating';
      document.documentElement.classList.add(tempClass);
      setTimeout(() => {
        document.documentElement.classList.remove(tempClass);
      }, 10);
    }
  }, [brandSettings]);

  // Apply layout direction from cookie on initial load for demo mode (exclude landing page)
  useEffect(() => {
    if (isDemoMode() && typeof window !== 'undefined') {
      const isLandingPage = window.location.pathname === '/';
      if (!isLandingPage) {
        // Always keep document direction as LTR to prevent sidebar issues
        document.documentElement.dir = 'ltr';
        document.documentElement.setAttribute('dir', 'ltr');
      }
    }
  }, []);

  // Listen for changes in settings
  useEffect(() => {
    const effectiveSettings = getEffectiveSettings();
    const updatedSettings = getBrandSettings(effectiveSettings, globalSettings);
    const isDemo = globalSettings?.is_demo || false;
    setBrandSettings({
      ...updatedSettings,
      logoDark: getImagePath(updatedSettings.logoDark || (isDemo ? 'images/logos/logo-dark.png' : '')),
      logoLight: getImagePath(updatedSettings.logoLight || (isDemo ? 'images/logos/logo-light.png' : '')),
      favicon: getImagePath(updatedSettings.favicon || (isDemo ? 'images/logos/favicon.png' : ''))
    });
  }, [globalSettings, user]);



  const updateBrandSettings = (newSettings: Partial<BrandSettings>) => {
    // Convert logo paths to full URLs if they are relative paths
    const processedSettings = { ...newSettings };
    if (newSettings.logoDark) {
      processedSettings.logoDark = getImagePath(newSettings.logoDark);
    }
    if (newSettings.logoLight) {
      processedSettings.logoLight = getImagePath(newSettings.logoLight);
    }
    if (newSettings.favicon) {
      processedSettings.favicon = getImagePath(newSettings.favicon);
    }
    setBrandSettings(prev => ({ ...prev, ...processedSettings }));
  };

  return (
    <BrandContext.Provider value={{ ...brandSettings, updateBrandSettings }}>
      {children}
    </BrandContext.Provider>
  );
}

export function useBrand() {
  const context = useContext(BrandContext);
  if (context === undefined) {
    throw new Error('useBrand must be used within a BrandProvider');
  }
  return context;
}
