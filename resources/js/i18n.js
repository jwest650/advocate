// resources/js/i18n.js
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import Backend from 'i18next-http-backend';
import LanguageDetector from 'i18next-browser-languagedetector';

// Make i18n instance available for direct imports
export { default as i18next } from 'i18next';

// Helper function to get cookie value
const getCookie = (name) => {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    const cookieValue = parts.pop().split(';').shift();
    return cookieValue ? decodeURIComponent(cookieValue) : null;
  }
  return null;
};

// Helper function to check demo mode
const isDemoMode = () => {
  // Check multiple sources for demo mode
  if (typeof window !== 'undefined') {
    // Check page props first
    if (window.page?.props?.globalSettings?.is_demo === true || 
        window.page?.props?.globalSettings?.is_demo === '1') {
      return true;
    }
    
    // Check window.globalSettings as fallback
    if (window.globalSettings?.is_demo === true || 
        window.globalSettings?.is_demo === '1') {
      return true;
    }
    
    // Check window.isDemo as another fallback
    if (window.isDemo === true) {
      return true;
    }
    
    // Check if app_language cookie exists (indicates demo mode)
    const appLangCookie = getCookie('app_language');
    if (appLangCookie && appLangCookie !== 'undefined' && appLangCookie !== 'null') {
      return true;
    }
  }
  
  return false;
};

// Custom backend to handle the modified response format
const customBackend = {
  type: 'backend',
  init: function (services, backendOptions) {
    this.services = services;
    this.options = backendOptions;
  },
  read: function (language, namespace, callback) {
    // In demo mode, always prioritize cookie language
    let actualLanguage = language;

    if (isDemoMode()) {
      const cookieLang = getCookie('app_language');
      if (cookieLang && cookieLang !== 'undefined' && cookieLang !== 'null') {
        actualLanguage = cookieLang;
      }
    }

    const loadPath = window.route ? window.route('translations', actualLanguage) : `/translations/${actualLanguage}`;

    fetch(loadPath)
      .then(response => response.json())
      .then(data => {
        // Extract translations from the structured response
        const translations = data.translations;

        callback(null, translations);
      })
      .catch(error => {
        console.error('Translation loading error:', error);
        callback(error, null);
      });
  }
};

// Function to get initial language with improved detection
const getInitialLanguage = () => {
  // In demo mode, ALWAYS prioritize app_language cookie
  if (isDemoMode()) {
    const appLang = getCookie('app_language');
    if (appLang && appLang !== 'undefined' && appLang !== 'null') {
      return appLang;
    }

    // Try demo-specific cookies as fallback
    const demoLang = getCookie('selected_language');
    if (demoLang && demoLang !== 'undefined' && demoLang !== 'null') {
      return demoLang;
    }
    
    // In demo mode, don't use server locale or localStorage
    return 'en';
  }

  // Non-demo mode: try server locale first
  if (window.initialLocale) {
    return window.initialLocale;
  }

  // Non-demo mode: try app language cookie
  const appLang = getCookie('app_language');
  if (appLang && appLang !== 'undefined' && appLang !== 'null') {
    return appLang;
  }

  // Check localStorage in non-demo mode
  const storedLang = localStorage.getItem('i18nextLng');
  if (storedLang && storedLang !== 'undefined' && storedLang !== 'null') {
    return storedLang;
  }

  // Default fallback
  return 'en';
};

// Initialize i18n
i18n
  .use(customBackend)
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    lng: getInitialLanguage(),
    fallbackLng: 'en',
    load: 'currentOnly',
    debug: process.env.NODE_ENV === 'development',

    interpolation: {
      escapeValue: false,
    },

    detection: {
      order: isDemoMode() ? ['cookie', 'navigator'] : ['localStorage', 'cookie', 'navigator'],
      lookupCookie: 'app_language',
      caches: isDemoMode() ? ['cookie'] : ['localStorage', 'cookie'],
      cookieOptions: {
        path: '/',
        maxAge: 60 * 60 * 24 * 365, // 1 year
        sameSite: 'lax'
      }
    },

    ns: ['translation'],
    defaultNS: 'translation',

    partialBundledLanguages: true,
    loadOnInitialization: true,

    // Disable resource caching to ensure fresh translations
    saveMissing: false,
    updateMissing: false,

    react: {
      useSuspense: false,
      bindI18n: 'languageChanged loaded',
      bindI18nStore: 'added removed',
      transEmptyNodeValue: '',
      transSupportBasicHtmlNodes: true,
      transKeepBasicHtmlNodesFor: ['br', 'strong', 'i', 'p'],
    }
  })
  .then(() => {
    // Always keep document direction as LTR to prevent sidebar issues
    document.documentElement.dir = 'ltr';
    document.documentElement.setAttribute('dir', 'ltr');
  });

// Override changeLanguage to force resource reload and handle demo mode
const originalChangeLanguage = i18n.changeLanguage.bind(i18n);
i18n.changeLanguage = function (lng, callback) {
  // Remove cached resources for the new language
  if (i18n.services.resourceStore.data[lng]) {
    delete i18n.services.resourceStore.data[lng];
  }

  // Always keep document direction as LTR to prevent sidebar issues
  document.documentElement.dir = 'ltr';
  document.documentElement.setAttribute('dir', 'ltr');

  // In demo mode, ensure cookie is set immediately
  if (isDemoMode()) {
    const setCookie = (name, value, days = 365) => {
      if (typeof document === 'undefined') return;
      const maxAge = days * 24 * 60 * 60;
      document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
    };
    
    setCookie('app_language', lng);
  }

  // Call original changeLanguage
  return originalChangeLanguage(lng, callback);
};

// Export the initialized instance
export default i18n;

// Make sure the i18n instance is available for direct imports
window.i18next = i18n;