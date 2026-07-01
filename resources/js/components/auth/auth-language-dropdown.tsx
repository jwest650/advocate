import { useState, useEffect } from "react";
import { useTranslation } from 'react-i18next';
import { usePage, router } from '@inertiajs/react';
import { isDemoMode, setCookie, getCookie } from '@/utils/cookies';

interface Language {
  code: string;
  name: string;
  countryCode: string;
  enabled?: boolean;
}

const AuthLanguageDropdown = () => {
  const { t, i18n } = useTranslation();
  const { auth, globalSettings } = usePage().props as any;
  const [isOpen, setIsOpen] = useState(false);
  const [currentLanguage, setCurrentLanguage] = useState<Language | null>(null);

  const availableLanguages = globalSettings?.availableLanguages || [];
  const enabledLanguages = availableLanguages.filter((l: Language) => l.enabled !== false);

  // RTL languages list
  const rtlLanguages = ['ar', 'he'];

  // Country code to flag emoji mapping
  const getFlagEmoji = (countryCode: string) => {
    const codePoints = countryCode
      .toUpperCase()
      .split('')
      .map(char => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
  };

  useEffect(() => {
    // In demo mode, prioritize cookie language
    if (isDemoMode()) {
      const cookieLang = getCookie('app_language');
      if (cookieLang) {
        const cookieLanguage = enabledLanguages.find((l: Language) => l.code === cookieLang);
        if (cookieLanguage) {
          setCurrentLanguage(cookieLanguage);
          if (i18n.language !== cookieLang) {
            i18n.changeLanguage(cookieLang);
          }
          return;
        }
      }
    }
    
    const lang = enabledLanguages.find((l: Language) => l.code === i18n.language) || enabledLanguages[0];
    setCurrentLanguage(lang);
  }, [i18n.language, availableLanguages]);

  const handleLanguageChange = async (language: Language) => {
    setCurrentLanguage(language);
    setIsOpen(false);
    
    try {
      const isRtl = rtlLanguages.includes(language.code);
      const newDirection = isRtl ? 'right' : 'left';

      // In demo mode, set cookies BEFORE changing language
      if (isDemoMode()) {
        setCookie('app_language', language.code, 365);
        setCookie('layoutDirection', newDirection, 365);
        setCookie('selected_language', language.code, 365);
        
        if (typeof localStorage !== 'undefined') {
          localStorage.setItem('i18nextLng', language.code);
        }
        
        await new Promise(resolve => setTimeout(resolve, 100));
      }
      
      if (isDemoMode() && i18n.services.resourceStore.data[language.code]) {
        delete i18n.services.resourceStore.data[language.code];
      }
      
      await i18n.changeLanguage(language.code);

      // Always save to cookies for immediate persistence (non-demo mode)
      if (!isDemoMode()) {
        setCookie('app_language', language.code);
        setCookie('layoutDirection', newDirection);
        
        if (typeof localStorage !== 'undefined') {
          localStorage.setItem('i18nextLng', language.code);
        }
      }

      // Save language change for authenticated non-demo users
      if (auth?.user && !isDemoMode()) {
        router.post(route('languages.change'), {
          language: language.code,
          layoutDirection: newDirection
        }, {
          preserveScroll: true,
          onError: (errors) => {
            console.error('Failed to change language and layout direction:', errors);
          }
        });
      }

      // Dispatch events
      window.dispatchEvent(new CustomEvent('languageChanged', {
        detail: { language: language.code, direction: newDirection }
      }));
      window.dispatchEvent(new Event('resize'));
    } catch (error) {
      console.error('Error changing language:', error);
    }
  };

  if (!currentLanguage || enabledLanguages.length === 0) {
    return null;
  }

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center space-x-2 bg-white rounded-lg px-3 py-2 border border-gray-200 shadow-sm hover:shadow-md transition-all"
      >
        <svg
          className="h-4 w-4 text-gray-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
          <path d="M2 12h20"></path>
        </svg>
        <span className="text-sm text-gray-700 font-medium w-[50px]">
          {currentLanguage.name}
        </span>
        <svg
          className={`h-3 w-3 text-gray-400 transition-transform ${
            isOpen ? "rotate-180" : ""
          }`}
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </button>

      {isOpen && (
        <div className="absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-[190px] z-50 overflow-y-auto">
          {enabledLanguages.map((lang: Language) => (
            <button
              key={lang.code}
              onClick={() => handleLanguageChange(lang)}
              className="w-full flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            >
              <span>{getFlagEmoji(lang.countryCode)}</span>
              <span>{lang.name}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default AuthLanguageDropdown;