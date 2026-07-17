import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Menu, X, ChevronDown } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { LanguageSwitcher } from '@/components/language-switcher';

interface CustomPage {
  id: number;
  title: string;
  slug: string;
  nav_group?: string | null;
  summary?: string | null;
}

interface HeaderProps {
  brandColor?: string;
  settings: {
    company_name: string;
  };
  sectionData?: any;
  customPages?: CustomPage[];
}

export default function Header({ settings, sectionData, customPages = [], brandColor = '#3b82f6' }: HeaderProps) {
  const { t } = useTranslation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const [isRtl, setIsRtl] = useState(() => {
    if (typeof document !== 'undefined') {
      return document.documentElement.dir === 'rtl';
    }
    return false;
  });

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    
    // Check RTL direction
    const checkRtl = () => {
      const newRtl = document.documentElement.dir === 'rtl';
      if (newRtl !== isRtl) {
        setIsRtl(newRtl);
      }
    };
    checkRtl();
    
    // Watch for dir changes
    const observer = new MutationObserver(checkRtl);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['dir'] });
    
    return () => {
      window.removeEventListener('scroll', handleScroll);
      observer.disconnect();
    };
  }, []);

  const [openDropdown, setOpenDropdown] = useState<string | null>(null);
  const [openMobileGroup, setOpenMobileGroup] = useState<string | null>(null);

  // These pages stay reachable from the footer and by URL, but are kept out of the header nav.
  const hiddenNavSlugs = [
    'about-us',
    'privacy-policy',
    'terms-of-service',
    'contact-us',
    'refund-policy',
    'faq',
  ];

  const toLink = (page: CustomPage) => ({
    name: page.title,
    summary: page.summary ?? null,
    href: route('custom-page.show', page.slug),
  });

  const practiceTypes = customPages.filter(p => p.nav_group === 'practice-type').map(toLink);
  const solutions = customPages.filter(p => p.nav_group === 'solution').map(toLink);

  // Anything not in a dropdown and not hidden still renders as a plain top-level link.
  const menuItems = customPages
    .filter(page => !page.nav_group && !hiddenNavSlugs.includes(page.slug))
    .map(toLink);

  const dropdowns = [
    { key: 'solutions', label: t('Solutions'), items: solutions },
    { key: 'practice-type', label: t('Practice Type'), items: practiceTypes },
  ].filter(group => group.items.length > 0);

  const isTransparent = sectionData?.transparent;
  const backgroundColor = sectionData?.background_color || '#ffffff';

  const getHeaderClasses = () => {
    if (isTransparent) {
      return isScrolled
        ? 'bg-white/95 backdrop-blur-xl shadow-lg border-b border-gray-200/50'
        : 'bg-transparent';
    }
    return isScrolled
      ? 'shadow-lg border-b border-gray-200/50'
      : '';
  };

  const getHeaderStyle = () => {
    if (isTransparent) return {};
    return { backgroundColor };
  };

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${getHeaderClasses()}`}
      style={getHeaderStyle()}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className={`flex items-center h-16 ${isRtl ? 'flex-row-reverse justify-between' : 'justify-between'}`}>
          {/* Logo */}
          <div className={`flex-shrink-0 ${isRtl ? 'order-3' : 'order-1'}`}>
            <Link
              href={route("home")}
              className="text-2xl font-bold text-gray-900 transition-colors"
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {settings.company_name}
            </Link>
          </div>

          {/* Desktop Navigation */}
          <nav className={`hidden md:flex items-center space-x-8 ${isRtl ? 'space-x-reverse order-2' : 'order-2'}`} role="navigation" aria-label="Main navigation">
            <Link
              href={route('home')}
              className="text-gray-600 text-sm font-medium transition-colors relative group"
              style={{ '--hover-color': brandColor } as React.CSSProperties}
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {t('Home')}
              <span
                className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full"
                style={{ backgroundColor: brandColor }}
                aria-hidden="true"
              ></span>
            </Link>
            {dropdowns.map((group) => (
              <div
                key={group.key}
                className="relative"
                onMouseEnter={() => setOpenDropdown(group.key)}
                onMouseLeave={() => setOpenDropdown(null)}
              >
                <button
                  type="button"
                  className="flex items-center gap-1 text-gray-600 text-sm font-medium transition-colors py-2"
                  aria-expanded={openDropdown === group.key}
                  aria-haspopup="true"
                  onClick={() => setOpenDropdown(openDropdown === group.key ? null : group.key)}
                  onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                  onMouseLeave={(e) => e.currentTarget.style.color = ''}
                >
                  {group.label}
                  <ChevronDown
                    size={16}
                    className={`transition-transform ${openDropdown === group.key ? 'rotate-180' : ''}`}
                    aria-hidden="true"
                  />
                </button>

                {openDropdown === group.key && (
                  <div
                    className={`absolute top-full ${isRtl ? 'right-0' : 'left-0'} pt-2 z-50`}
                  >
                    <div className="w-72 max-h-[70vh] overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl py-2">
                      {group.items.map((item) => (
                        <Link
                          key={item.name}
                          href={item.href}
                          className="block px-4 py-2.5 hover:bg-gray-50 transition-colors"
                          onClick={() => setOpenDropdown(null)}
                        >
                          <span
                            className="block text-sm font-medium text-gray-900"
                            onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                            onMouseLeave={(e) => e.currentTarget.style.color = ''}
                          >
                            {item.name}
                          </span>
                          {item.summary && (
                            <span className="block text-xs text-gray-500 mt-0.5">{item.summary}</span>
                          )}
                        </Link>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ))}
            {menuItems.map((item) => (
              <Link
                key={item.name}
                href={item.href}
                className="text-gray-600 text-sm font-medium transition-colors relative group"
                style={{ '--hover-color': brandColor } as React.CSSProperties}
                onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                onMouseLeave={(e) => e.currentTarget.style.color = ''}
              >
                {item.name}
                <span
                  className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full"
                  style={{ backgroundColor: brandColor }}
                  aria-hidden="true"
                ></span>
              </Link>
            ))}
          </nav>

          {/* Auth Buttons */}
          <div className={`hidden md:flex items-center gap-4 ${isRtl ? 'order-1' : 'order-3'}`}>
            <Link
              href={route('login')}
              className="text-gray-600 text-sm font-medium transition-colors"
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {t('Login')}
            </Link>
            <Link
              href={route('register')}
              className="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors border"
              style={{
                backgroundColor: brandColor,
                color: 'white',
                borderColor: brandColor
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = 'white';
                e.currentTarget.style.color = brandColor;
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = brandColor;
                e.currentTarget.style.color = 'white';
              }}
            >
              {t('Get Started')}
            </Link>
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button
              onClick={() => setIsMenuOpen(!isMenuOpen)}
              className="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label={isMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
              aria-expanded={isMenuOpen}
              aria-controls="mobile-menu"
            >
              {isMenuOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {isMenuOpen && (
          <div className="md:hidden border-t border-gray-200" id="mobile-menu">
            <div
              className="px-4 py-6 space-y-4"
              style={isTransparent ? { backgroundColor: 'white' } : { backgroundColor }}
            >
              <Link
                href={route('home')}
                className="block text-gray-600 hover:text-gray-900 text-base font-medium transition-colors"
                onClick={() => setIsMenuOpen(false)}
              >
                {t('Home')}
              </Link>
              {dropdowns.map((group) => (
                <div key={group.key}>
                  <button
                    type="button"
                    className="flex w-full items-center justify-between text-gray-600 hover:text-gray-900 text-base font-medium transition-colors"
                    aria-expanded={openMobileGroup === group.key}
                    onClick={() => setOpenMobileGroup(openMobileGroup === group.key ? null : group.key)}
                  >
                    {group.label}
                    <ChevronDown
                      size={18}
                      className={`transition-transform ${openMobileGroup === group.key ? 'rotate-180' : ''}`}
                      aria-hidden="true"
                    />
                  </button>
                  {openMobileGroup === group.key && (
                    <div className={`mt-3 space-y-3 border-gray-200 ${isRtl ? 'pr-4 border-r' : 'pl-4 border-l'}`}>
                      {group.items.map((item) => (
                        <Link
                          key={item.name}
                          href={item.href}
                          className="block text-gray-600 hover:text-gray-900 text-sm transition-colors"
                          onClick={() => {
                            setIsMenuOpen(false);
                            setOpenMobileGroup(null);
                          }}
                        >
                          {item.name}
                        </Link>
                      ))}
                    </div>
                  )}
                </div>
              ))}
              {menuItems.map((item) => (
                <Link
                  key={item.name}
                  href={item.href}
                  className="block text-gray-600 hover:text-gray-900 text-base font-medium transition-colors"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {item.name}
                </Link>
              ))}
              <div className="pt-4 space-y-3 border-t border-gray-200">
                <Link
                  href={route('login')}
                  className="block w-full text-center text-gray-600 py-2.5 text-sm font-medium transition-colors"
                  onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                  onMouseLeave={(e) => e.currentTarget.style.color = ''}
                >
                  {t('Login')}
                </Link>
                <Link
                  href={route('register')}
                  className="block w-full text-center py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                  style={{
                    backgroundColor: brandColor,
                    color: 'white',
                    borderColor: brandColor
                  }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.backgroundColor = 'white';
                    e.currentTarget.style.color = brandColor;
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.backgroundColor = brandColor;
                    e.currentTarget.style.color = 'white';
                  }}
                >
                  {t('Get Started')}
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </header>
  );
}
