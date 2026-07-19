import React from 'react';
import { usePage, Head, Link } from '@inertiajs/react';
import * as LucideIcons from 'lucide-react';
import { CheckCircle2, ArrowRight, Quote, Plus, Minus, Shield } from 'lucide-react';
import Header from './components/Header';
import Footer from './components/Footer';
import { useFavicon } from '@/hooks/use-favicon';
import { useScrollAnimation } from '@/hooks/useScrollAnimation';

interface CtaLink {
  label: string;
  href?: string;
}

interface PageData {
  eyebrow?: string;
  layout?: {
    hero?: 'split' | 'reverse' | 'centered';
    features?: 'grid' | 'list' | 'zigzag';
    showcase?: 'alternating' | 'cards';
    accent?: 'primary' | 'secondary' | 'accent';
  };
  hero?: {
    headline?: string;
    subheadline?: string;
    image?: string;
    bullets?: string[];
    primary_cta?: CtaLink;
    secondary_cta?: CtaLink;
  };
  features?: {
    title?: string;
    subtitle?: string;
    items?: Array<{ icon?: string; title: string; description: string }>;
  };
  showcase?: {
    title?: string;
    subtitle?: string;
    items?: Array<{ image: string; title: string; description: string }>;
  };
  stats?: Array<{ value: string; label: string }>;
  testimonials?: {
    title?: string;
    subtitle?: string;
    items?: Array<{ quote: string; author: string; role?: string }>;
  };
  faq?: {
    title?: string;
    subtitle?: string;
    items?: Array<{ question: string; answer: string }>;
  };
  cta?: {
    title?: string;
    subtitle?: string;
    primary_cta?: CtaLink;
    secondary_cta?: CtaLink;
  };
}

interface MarketingPage {
  id: number;
  title: string;
  slug: string;
  summary?: string | null;
  content: string;
  page_data?: PageData | null;
  meta_title?: string;
  meta_description?: string;
}

interface PageProps {
  page: MarketingPage;
  customPages: Array<{ id: number; title: string; slug: string; nav_group?: string | null; summary?: string | null }>;
  settings: {
    config_sections?: {
      theme?: {
        primary_color?: string;
        secondary_color?: string;
        accent_color?: string;
      };
      sections?: Array<{ key: string; [key: string]: unknown }>;
      custom_css?: string;
      custom_js?: string;
      section_visibility?: Record<string, boolean>;
      section_order?: string[];
    };
    [key: string]: unknown;
  };
}

/** Resolves an icon name from the seeded data to a Lucide component. */
function Icon({ name, ...props }: { name?: string; size?: number; className?: string }) {
  const fallback = LucideIcons.Circle;
  const iconMap = LucideIcons as Record<string, React.ElementType>;
  const Component = (name ? iconMap[name] : undefined) || fallback;
  return <Component {...props} />;
}

/** Screenshots are stored as public-relative paths (e.g. /screenshots/a.png). */
function imageUrl(path?: string) {
  if (!path) return undefined;
  if (path.startsWith('http')) return path;
  return path;
}

function Section({ children, className = '' }: { children: React.ReactNode; className?: string }) {
  const { ref, isVisible } = useScrollAnimation();
  return (
    <section
      ref={ref}
      className={`transition-all duration-700 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'} ${className}`}
    >
      {children}
    </section>
  );
}

export default function MarketingPage() {
  const pageProps = usePage<PageProps & { globalSettings?: { is_demo?: boolean; layoutDirection?: 'left' | 'right' } }>();
  const { page, customPages = [], settings } = pageProps.props;
  const globalSettings = pageProps.props.globalSettings;

  const data: PageData = page.page_data || {};
  const hero = data.hero || {};
  const features = data.features || {};
  const showcase = data.showcase || {};
  const stats = data.stats || [];
  const testimonials = data.testimonials || {};
  const faq = data.faq || {};
  const cta = data.cta || {};

  const [openFaq, setOpenFaq] = React.useState<number | null>(0);

  useFavicon();

  // Match the RTL handling used by the other landing templates.
  React.useEffect(() => {
    const isDemo = globalSettings?.is_demo || false;
    let storedPosition = 'left';

    if (isDemo) {
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
      const stored = getCookie('layoutPosition');
      if (stored === 'left' || stored === 'right') storedPosition = stored;
    } else {
      const stored = globalSettings?.layoutDirection;
      if (stored === 'left' || stored === 'right') storedPosition = stored;
    }

    const dir = storedPosition === 'right' ? 'rtl' : 'ltr';
    document.documentElement.dir = dir;
    document.documentElement.setAttribute('dir', dir);
  }, [globalSettings]);

  const primaryColor = settings?.config_sections?.theme?.primary_color || '#3b82f6';
  const secondaryColor = settings?.config_sections?.theme?.secondary_color || '#8b5cf6';
  const accentColor = settings?.config_sections?.theme?.accent_color || '#10b77f';

  // Layout variant — lets each practice page arrange itself differently while
  // sharing one template. Defaults reproduce the original split layout.
  const layout = data.layout || {};
  const heroMode = layout.hero || 'split';
  const featuresMode = layout.features || 'grid';
  const showcaseMode = layout.showcase || 'alternating';
  // The "lead" colour drives that page's section accents, so pages that reuse
  // the same screenshots still read as visibly different.
  const leadColor =
    layout.accent === 'secondary' ? secondaryColor :
    layout.accent === 'accent' ? accentColor :
    primaryColor;

  const registerHref = route('register');
  const contactHref = route('custom-page.show', 'contact-us');

  return (
    <>
      <Head title={page.meta_title || page.title}>
        {page.meta_description && <meta name="description" content={page.meta_description} />}
      </Head>

      <div
        className="min-h-screen bg-[linear-gradient(135deg,#f8fbff_0%,#f7f5ff_45%,#fdf8f2_100%)] text-slate-900"
        style={{
          '--primary-color': primaryColor,
          '--secondary-color': secondaryColor,
          '--accent-color': accentColor,
        } as React.CSSProperties}
      >
        <Header
          settings={settings as Record<string, unknown> & { company_name: string }}
          customPages={customPages}
          sectionData={settings?.config_sections?.sections?.find((s: { key: string; [key: string]: unknown }) => s.key === 'header') || {}}
        />

        <main className="pt-16">
          {/* Hero — phase 1 of the refreshed dropdown-page experience */}
          <section className="relative overflow-hidden">
            <div
              className="absolute inset-0 -z-10"
              style={{ background: `linear-gradient(135deg, ${leadColor}0D 0%, ${secondaryColor}14 100%)` }}
              aria-hidden="true"
            />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
              {(() => {
                const heroImage = imageUrl(hero.image || '/screenshots/a-advocate-saas-pic.png');
                const heroBadges = ['Secure client workflows', 'Built for modern legal teams', 'Trusted by growing firms'];
                const eyebrowEl = data.eyebrow && (
                  <span
                    className="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white/80 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.24em] shadow-sm"
                    style={{ color: leadColor }}
                  >
                    <Shield size={14} aria-hidden="true" />
                    {data.eyebrow}
                  </span>
                );
                const headlineEl = (
                  <h1 className="text-4xl lg:text-5xl font-semibold tracking-tight text-slate-950 leading-tight mb-6">
                    {hero.headline || page.title}
                  </h1>
                );
                const subheadEl = hero.subheadline && (
                  <p className="text-lg text-slate-600 leading-8 mb-8 max-w-2xl">{hero.subheadline}</p>
                );
                const bulletsEl = !!hero.bullets?.length && (
                  <ul className={`space-y-3 mb-8 ${heroMode === 'centered' ? 'inline-flex flex-col text-left' : ''}`}>
                    {hero.bullets.map((bullet) => (
                      <li key={bullet} className="flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white/70 px-3 py-2.5 shadow-sm">
                        <CheckCircle2 size={18} className="mt-0.5 shrink-0" style={{ color: accentColor }} aria-hidden="true" />
                        <span className="text-slate-700">{bullet}</span>
                      </li>
                    ))}
                  </ul>
                );
                const ctaEl = (
                  <div className={`flex flex-wrap gap-4 ${heroMode === 'centered' ? 'justify-center' : ''}`}>
                    <Link
                      href={hero.primary_cta?.href || registerHref}
                      className="inline-flex items-center gap-2 rounded-full px-7 py-3.5 text-sm font-semibold text-white shadow-lg transition-transform duration-200 hover:-translate-y-0.5"
                      style={{ backgroundColor: leadColor }}
                    >
                      {hero.primary_cta?.label || 'Start Free Trial'}
                      <ArrowRight size={18} aria-hidden="true" />
                    </Link>
                    <Link
                      href={hero.secondary_cta?.href || contactHref}
                      className="inline-flex items-center rounded-full border border-slate-300 bg-white/80 px-7 py-3.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-white"
                    >
                      {hero.secondary_cta?.label || 'Book a Demo'}
                    </Link>
                  </div>
                );
                const imageEl = heroImage && (
                  <div className="relative">
                    <div
                      className="absolute -inset-4 rounded-[32px] blur-3xl opacity-20"
                      style={{ background: `linear-gradient(135deg, ${leadColor}, ${secondaryColor})` }}
                      aria-hidden="true"
                    />
                    <div className="relative overflow-hidden rounded-[28px] border border-slate-200 bg-slate-950/90 p-2 shadow-[0_35px_90px_-30px_rgba(15,23,42,0.45)]">
                      <img
                        src={heroImage}
                        alt={`${page.title} in AdvocateGo`}
                        loading="eager"
                        className="w-full rounded-[22px] border border-white/10 object-cover shadow-2xl"
                        style={{ aspectRatio: '16 / 10' }}
                      />
                    </div>
                    <div className="absolute -bottom-4 left-4 right-4 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-lg backdrop-blur">
                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl" style={{ backgroundColor: `${leadColor}14`, color: leadColor }}>
                          <Shield size={18} aria-hidden="true" />
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-slate-900">Legal-grade operations</p>
                          <p className="text-sm text-slate-600">Secure workflows, clear oversight, and faster execution.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                );

                if (heroMode === 'centered') {
                  return (
                    <div className="rounded-[32px] border border-slate-200/80 bg-white/70 p-8 shadow-[0_30px_80px_-24px_rgba(15,23,42,0.2)] backdrop-blur-xl lg:p-10">
                      <div className="text-center">
                        <div className="max-w-3xl mx-auto">
                          {eyebrowEl}
                          {headlineEl}
                          {subheadEl}
                          {bulletsEl && <div className="flex justify-center">{bulletsEl}</div>}
                          {ctaEl}
                          <div className="mt-6 flex flex-wrap justify-center gap-2">
                            {heroBadges.map((badge) => (
                              <span key={badge} className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-600">
                                {badge}
                              </span>
                            ))}
                          </div>
                        </div>
                        {imageEl && <div className="mt-10 max-w-5xl mx-auto">{imageEl}</div>}
                      </div>
                    </div>
                  );
                }

                const copyCol = (
                  <div key="copy" className="rounded-[32px] border border-slate-200/80 bg-white/70 p-8 shadow-[0_30px_80px_-24px_rgba(15,23,42,0.2)] backdrop-blur-xl lg:p-10">
                    {eyebrowEl}
                    {headlineEl}
                    {subheadEl}
                    {bulletsEl}
                    {ctaEl}
                    <div className="mt-6 flex flex-wrap gap-2">
                      {heroBadges.map((badge) => (
                        <span key={badge} className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-600">
                          {badge}
                        </span>
                      ))}
                    </div>
                  </div>
                );
                const imageCol = imageEl ? <div key="image" className="relative">{imageEl}</div> : null;
                return (
                  <div className="grid lg:grid-cols-2 gap-10 lg:gap-12 items-center">
                    {heroMode === 'reverse' ? [imageCol, copyCol] : [copyCol, imageCol]}
                  </div>
                );
              })()}
            </div>
          </section>

          {/* Stats */}
          {!!stats.length && (
            <Section className="border-y border-slate-200/70 bg-white/60 backdrop-blur">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <dl className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                  {stats.map((stat) => (
                    <div key={stat.label} className="rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                      <dt className="sr-only">{stat.label}</dt>
                      <dd>
                        <span className="block text-3xl lg:text-4xl font-semibold tracking-tight" style={{ color: leadColor }}>
                          {stat.value}
                        </span>
                        <span className="mt-2 block text-sm text-slate-600">{stat.label}</span>
                      </dd>
                    </div>
                  ))}
                </dl>
              </div>
            </Section>
          )}

          {/* Feature cards */}
          {!!features.items?.length && (
            <Section className="py-20">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto text-center mb-14">
                  <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    {features.title || 'What you get'}
                  </h2>
                  {features.subtitle && <p className="text-lg text-gray-600">{features.subtitle}</p>}
                </div>

                {/* List: icon-left rows in a two-column stack */}
                {featuresMode === 'list' && (
                  <div className="grid md:grid-cols-2 gap-x-10 gap-y-8 max-w-5xl mx-auto">
                    {features.items.map((item) => (
                      <div key={item.title} className="flex items-start gap-4">
                        <div
                          className="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                          style={{ backgroundColor: `${leadColor}14`, color: leadColor }}
                        >
                          <Icon name={item.icon} size={20} />
                        </div>
                        <div>
                          <h3 className="text-lg font-semibold text-gray-900 mb-1.5">{item.title}</h3>
                          <p className="text-gray-600 leading-relaxed text-sm">{item.description}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* Zigzag: larger two-up cards with a numbered accent */}
                {featuresMode === 'zigzag' && (
                  <div className="grid md:grid-cols-2 gap-6">
                    {features.items.map((item, index) => (
                      <div
                        key={item.title}
                        className="group relative p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-xl transition-all duration-300 overflow-hidden"
                      >
                        <span
                          className="absolute top-4 right-5 text-5xl font-black opacity-10 select-none"
                          style={{ color: leadColor }}
                          aria-hidden="true"
                        >
                          {String(index + 1).padStart(2, '0')}
                        </span>
                        <div
                          className="w-12 h-12 rounded-xl flex items-center justify-center mb-5"
                          style={{ backgroundColor: `${leadColor}14`, color: leadColor }}
                        >
                          <Icon name={item.icon} size={22} />
                        </div>
                        <h3 className="text-xl font-semibold text-gray-900 mb-2">{item.title}</h3>
                        <p className="text-gray-600 leading-relaxed">{item.description}</p>
                      </div>
                    ))}
                  </div>
                )}

                {/* Grid (default): three-up cards */}
                {featuresMode === 'grid' && (
                  <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    {features.items.map((item) => (
                      <div
                        key={item.title}
                        className="group rounded-[24px] border border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-white p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                      >
                        <div
                          className="w-12 h-12 rounded-xl flex items-center justify-center mb-5"
                          style={{ backgroundColor: `${leadColor}14`, color: leadColor }}
                        >
                          <Icon name={item.icon} size={22} />
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">{item.title}</h3>
                        <p className="text-gray-600 leading-relaxed text-sm">{item.description}</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </Section>
          )}

          {/* Screenshot showcase — alternating image / copy */}
          {!!showcase.items?.length && (
            <Section className="py-20 bg-gray-50 border-y border-gray-100">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto text-center mb-16">
                  <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    {showcase.title || 'See it in action'}
                  </h2>
                  {showcase.subtitle && <p className="text-lg text-gray-600">{showcase.subtitle}</p>}
                </div>

                {/* Cards: three image-topped cards side by side */}
                {showcaseMode === 'cards' ? (
                  <div className="grid md:grid-cols-3 gap-6">
                    {showcase.items.map((item) => (
                      <div
                        key={item.title}
                        className="flex flex-col overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                      >
                        <img
                          src={imageUrl(item.image)}
                          alt={item.title}
                          loading="lazy"
                          className="w-full aspect-[4/3] object-cover border-b border-gray-100"
                        />
                        <div className="p-6">
                          <h3 className="text-lg font-semibold text-gray-900 mb-2">{item.title}</h3>
                          <p className="text-gray-600 leading-relaxed text-sm">{item.description}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="space-y-20">
                    {showcase.items.map((item, index) => (
                      <div key={item.title} className="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                        <div className={index % 2 === 1 ? 'lg:order-2' : ''}>
                          <div className="overflow-hidden rounded-[24px] border border-slate-200/80 bg-white p-2 shadow-[0_20px_60px_-24px_rgba(15,23,42,0.35)]">
                            <img
                              src={imageUrl(item.image)}
                              alt={item.title}
                              loading="lazy"
                              className="w-full rounded-[18px] border border-slate-100 object-cover"
                              style={{ aspectRatio: '16 / 10' }}
                            />
                          </div>
                        </div>
                        <div className={index % 2 === 1 ? 'lg:order-1' : ''}>
                          <div className="rounded-[24px] border border-slate-200/80 bg-white/80 p-8 shadow-sm backdrop-blur">
                            <h3 className="text-2xl font-semibold text-slate-900 mb-4">{item.title}</h3>
                            <p className="text-slate-600 leading-relaxed">{item.description}</p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </Section>
          )}

          {/* Testimonials */}
          {!!testimonials.items?.length && (
            <Section className="py-20">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto text-center mb-14">
                  <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    {testimonials.title || 'What firms say'}
                  </h2>
                  {testimonials.subtitle && <p className="text-lg text-gray-600">{testimonials.subtitle}</p>}
                </div>

                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {testimonials.items.map((item) => (
                    <figure
                      key={item.author}
                      className="flex flex-col p-7 rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                      <Quote size={28} className="mb-4 shrink-0" style={{ color: leadColor }} aria-hidden="true" />
                      <blockquote className="text-gray-700 leading-relaxed mb-6 flex-1">
                        {item.quote}
                      </blockquote>
                      <figcaption className="mt-auto">
                        <div className="font-semibold text-gray-900">{item.author}</div>
                        {item.role && <div className="text-sm text-gray-500">{item.role}</div>}
                      </figcaption>
                    </figure>
                  ))}
                </div>
              </div>
            </Section>
          )}

          {/* FAQ */}
          {!!faq.items?.length && (
            <Section className="py-20 bg-gray-50 border-y border-gray-100">
              <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-14">
                  <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    {faq.title || 'Frequently asked questions'}
                  </h2>
                  {faq.subtitle && <p className="text-lg text-gray-600">{faq.subtitle}</p>}
                </div>

                <dl className="space-y-4">
                  {faq.items.map((item, index) => {
                    const isOpen = openFaq === index;
                    return (
                      <div key={item.question} className="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                        <dt>
                          <button
                            type="button"
                            onClick={() => setOpenFaq(isOpen ? null : index)}
                            aria-expanded={isOpen}
                            className="w-full flex items-center justify-between gap-4 px-6 py-5 text-left"
                          >
                            <span className="text-base font-semibold text-gray-900">{item.question}</span>
                            <span className="shrink-0" style={{ color: leadColor }} aria-hidden="true">
                              {isOpen ? <Minus size={20} /> : <Plus size={20} />}
                            </span>
                          </button>
                        </dt>
                        {isOpen && (
                          <dd className="px-6 pb-5 -mt-1 text-gray-600 leading-relaxed">{item.answer}</dd>
                        )}
                      </div>
                    );
                  })}
                </dl>
              </div>
            </Section>
          )}

          {/* Long-form body — the original prose, kept as supporting detail */}
          {page.content && (
            <Section className="py-20">
              <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <article
                  className="prose prose-lg max-w-none text-gray-700"
                  dangerouslySetInnerHTML={{ __html: page.content }}
                />
              </div>
            </Section>
          )}

          {/* Closing CTA */}
          <section className="py-20" style={{ background: `linear-gradient(135deg, ${leadColor} 0%, ${secondaryColor} 100%)` }}>
            <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
              <h2 className="text-3xl lg:text-4xl font-bold text-white mb-5">
                {cta.title || `Run your ${page.title.toLowerCase()} practice on one platform`}
              </h2>
              <p className="text-lg text-white/90 mb-9">
                {cta.subtitle || 'Start a free trial today. No credit card required.'}
              </p>
              <div className="flex flex-wrap justify-center gap-4">
                <Link
                  href={cta.primary_cta?.href || registerHref}
                  className="inline-flex items-center gap-2 px-8 py-4 rounded-lg bg-white text-sm font-semibold shadow-lg transition-transform hover:-translate-y-0.5"
                  style={{ color: leadColor }}
                >
                  {cta.primary_cta?.label || 'Start Free Trial'}
                  <ArrowRight size={18} aria-hidden="true" />
                </Link>
                <Link
                  href={cta.secondary_cta?.href || contactHref}
                  className="inline-flex items-center px-8 py-4 rounded-lg border-2 border-white text-white text-sm font-semibold hover:bg-white/10 transition-colors"
                >
                  {cta.secondary_cta?.label || 'Talk to Sales'}
                </Link>
              </div>
            </div>
          </section>
        </main>

        <Footer
          settings={settings as Record<string, unknown> & { company_name: string }}
          sectionData={settings?.config_sections?.sections?.find((s: { key: string; [key: string]: unknown }) => s.key === 'footer') || {}}
          brandColor={primaryColor}
        />
      </div>
    </>
  );
}
