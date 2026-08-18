import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X, ChevronDown } from 'lucide-react';
import { useTranslation } from 'react-i18next';

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
  sectionData?: {
    transparent?: boolean;
    background_color?: string;
  };
  customPages?: CustomPage[];
}

export default function Header({ settings, sectionData, customPages = [], brandColor = '#3b82f6' }: HeaderProps) {
  const { t } = useTranslation();
  const { props: pageProps } = usePage<{ customPages?: CustomPage[] }>();
  const resolvedCustomPages = Array.isArray(customPages) && customPages.length > 0
    ? customPages
    : Array.isArray(pageProps?.customPages) ? pageProps.customPages : [];
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
  }, [isRtl]);

  const [openDropdown, setOpenDropdown] = useState<string | null>(null);
  const [openMobileGroup, setOpenMobileGroup] = useState<string | null>(null);

  const toLink = (page: CustomPage) => ({
    name: page.title,
    summary: page.summary ?? null,
    href: route('custom-page.show', page.slug),
  });

  const practiceTypes = resolvedCustomPages.filter((p: CustomPage) => p.nav_group === 'practice-type').map(toLink);
  const solutions = resolvedCustomPages.filter((p: CustomPage) => p.nav_group === 'solution').map(toLink);

  // Only the grouped landing-page sections should appear in the main header.
  // Standalone custom pages remain available via the footer or direct URL.
  const menuItems: Array<{ name: string; summary: string | null; href: string }> = [];

  const dropdowns = [
    { key: 'solutions', label: t('Solutions'), items: solutions },
    { key: 'practice-type', label: t('Practice Type'), items: practiceTypes },
  ].filter(group => group.items.length > 0);

  const isTransparent = sectionData?.transparent;
  const backgroundColor = sectionData?.background_color || '#ffffff';

  const getHeaderClasses = () => {
    if (isTransparent) {
      return isScrolled
        ? 'border-b border-slate-200/80 bg-white/90 shadow-[0_12px_40px_-24px_rgba(15,23,42,0.35)] backdrop-blur-xl'
        : 'bg-transparent';
    }
    return isScrolled
      ? 'border-b border-slate-200/80 shadow-[0_12px_40px_-24px_rgba(15,23,42,0.35)]'
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
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className={`flex h-16 items-center ${isRtl ? 'flex-row-reverse justify-between' : 'justify-between'}`}>
          {/* Logo */}
          <div className={`flex-shrink-0 ${isRtl ? 'order-3' : 'order-1'}`}>
            <Link
              href={route("home")}
              className="text-xl font-semibold tracking-tight text-slate-900 transition-colors sm:text-2xl"
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {settings.company_name}
            </Link>
          </div>

          {/* Desktop Navigation */}
          <nav className={`hidden md:flex items-center gap-2 rounded-full border border-slate-200/80 bg-white/70 px-3 py-2 shadow-sm backdrop-blur ${isRtl ? 'space-x-reverse order-2' : 'order-2'}`} role="navigation" aria-label="Main navigation">
            <Link
              href={route('home')}
              className="relative rounded-full px-3 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:bg-slate-50 hover:text-slate-950"
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
                  className="flex items-center gap-1 rounded-full px-3 py-2 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-slate-50 hover:text-slate-950"
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
                    className={`absolute top-full ${isRtl ? 'right-0' : 'left-0'} pt-3 z-50`}
                  >
                    <div className="w-84 max-h-[72vh] overflow-y-auto rounded-[24px] border border-slate-200/80 bg-white/95 p-2 shadow-[0_32px_80px_-24px_rgba(15,23,42,0.4)] backdrop-blur-xl ring-1 ring-slate-200/70">
                      <div className="rounded-[20px] border border-slate-100 bg-gradient-to-br from-white via-slate-50 to-white p-2">
                        {group.items.map((item) => (
                          <Link
                            key={item.name}
                            href={item.href}
                            className="group flex items-start gap-3 rounded-[16px] border border-transparent px-3 py-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-200 hover:bg-slate-50 hover:shadow-sm"
                            onClick={() => setOpenDropdown(null)}
                          >
                            <span
                              className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full transition-all duration-200 group-hover:scale-125"
                              style={{ backgroundColor: brandColor }}
                              aria-hidden="true"
                            />
                            <span className="min-w-0">
                              <span
                                className="block text-sm font-semibold text-slate-800 transition-colors"
                                onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                                onMouseLeave={(e) => e.currentTarget.style.color = ''}
                              >
                                {item.name}
                              </span>
                              {item.summary && (
                                <span className="mt-1 block text-xs leading-5 text-slate-500">{item.summary}</span>
                              )}
                            </span>
                          </Link>
                        ))}
                      </div>
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
              className="rounded-full px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-950"
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {t('Login')}
            </Link>
            <Link
              href={route('register')}
              className="rounded-full border px-6 py-2.5 text-sm font-semibold transition-all duration-200"
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
                    className="flex w-full items-center justify-between rounded-xl border border-slate-200/80 bg-white/70 px-3 py-2.5 text-base font-semibold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50"
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
                    <div className={`mt-3 space-y-2 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-2 ${isRtl ? 'pr-3' : 'pl-3'}`}>
                      {group.items.map((item) => (
                        <Link
                          key={item.name}
                          href={item.href}
                          className="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-white hover:text-slate-900"
                          onClick={() => {
                            setIsMenuOpen(false);
                            setOpenMobileGroup(null);
                          }}
                        >
                          <span
                            className="h-2 w-2 rounded-full"
                            style={{ backgroundColor: brandColor }}
                            aria-hidden="true"
                          />
                          <span>{item.name}</span>
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
