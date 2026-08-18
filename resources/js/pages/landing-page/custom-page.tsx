import React from 'react';
import { usePage, Head } from '@inertiajs/react';
import Header from './components/Header';
import Footer from './components/Footer';
import { useFavicon } from '@/hooks/use-favicon';

interface CustomPage {
  id: number;
  title: string;
  slug: string;
  content: string;
  meta_title?: string;
  meta_description?: string;
  is_active: boolean;
}

interface CustomPageData {
  id: number;
  title: string;
  slug: string;
}

interface PageProps {
  page: CustomPage;
  customPages: CustomPageData[];
  settings: {
    company_name: string;
    contact_email?: string;
    contact_phone?: string;
    contact_address?: string;
    config_sections?: {
      sections?: Array<{
        key: string;
        [key: string]: unknown;
      }>;
      theme?: {
        primary_color?: string;
        secondary_color?: string;
        accent_color?: string;
      };
    };
    [key: string]: unknown;
  };
}

export default function CustomPage() {
  const pageProps = usePage<PageProps & { globalSettings?: { is_demo?: boolean; layoutDirection?: 'left' | 'right' } }>();
  const { page, customPages = [], settings } = pageProps.props;
  const globalSettings = pageProps.props.globalSettings;

  // RTL Support for custom pages
  React.useEffect(() => {
    const isDemo = globalSettings?.is_demo || false;
    let storedPosition = 'left';

    if (isDemo) {
      // In demo mode, use cookies
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
      if (stored === 'left' || stored === 'right') {
        storedPosition = stored;
      }
    } else {
      // In normal mode, get from database via globalSettings
      const stored = globalSettings?.layoutDirection;
      if (stored === 'left' || stored === 'right') {
        storedPosition = stored;
      }
    }

    const dir = storedPosition === 'right' ? 'rtl' : 'ltr';
    document.documentElement.dir = dir;
    document.documentElement.setAttribute('dir', dir);
  }, [globalSettings?.is_demo, globalSettings?.layoutDirection]);
  
React.useEffect(() => {
    const isDemo = globalSettings?.is_demo || false;
    let storedPosition = 'left';

    if (isDemo) {
      // In demo mode, use cookies
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
      if (stored === 'left' || stored === 'right') {
        storedPosition = stored;
      }
    } else {
      // In normal mode, get from database via globalSettings
      const stored = globalSettings?.layoutDirection;
      if (stored === 'left' || stored === 'right') {
        storedPosition = stored;
      }
    }

    const dir = storedPosition === 'right' ? 'rtl' : 'ltr';
    document.documentElement.dir = dir;
    document.documentElement.setAttribute('dir', dir);

    // Check if it was actually set
    setTimeout(() => {
      const actualDir = document.documentElement.getAttribute('dir');
      if (actualDir !== dir) {
        document.documentElement.dir = dir;
        document.documentElement.setAttribute('dir', dir);
      }
    }, 1);
  }, [globalSettings?.is_demo, globalSettings?.layoutDirection]);



  // Custom CSS for content styling
  const customCSS = `
    .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
      color: #1f2937;
      font-weight: 600;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }

    .prose h1 { font-size: 2.25rem; }
    .prose h2 { font-size: 1.875rem; }
    .prose h3 { font-size: 1.5rem; }

    .prose p {
      margin-bottom: 1.5rem;
      line-height: 1.75;
    }

    .prose ul, .prose ol {
      margin: 1.5rem 0;
      padding-left: 1.5rem;
    }

    .prose li {
      margin-bottom: 0.5rem;
    }

    .prose a {
      color: var(--primary-color);
      text-decoration: underline;
    }

    .prose blockquote {
      border-left: 4px solid var(--primary-color);
      padding-left: 1rem;
      margin: 1.5rem 0;
      font-style: italic;
      background-color: #f9fafb;
      padding: 1rem;
    }

    .prose img {
      max-width: 100%;
      height: auto;
      border-radius: 0.5rem;
      margin: 1.5rem 0;
    }
  `;
  const primaryColor = settings?.config_sections?.theme?.primary_color || '#3b82f6';
  const secondaryColor = settings?.config_sections?.theme?.secondary_color || '#8b5cf6';
  const accentColor = settings?.config_sections?.theme?.accent_color || '#10b77f';
  useFavicon();
  return (
    <>
      <Head title={page.meta_title || page.title}>
        {page.meta_description && (
          <meta name="description" content={page.meta_description} />
        )}
        <style>{customCSS}</style>
      </Head>

      <div
        className="min-h-screen bg-white"
        style={{
          '--primary-color': primaryColor,
          '--secondary-color': secondaryColor,
          '--accent-color': accentColor,
          '--primary-color-rgb': primaryColor.replace('#', '').match(/.{2}/g)?.map(x => parseInt(x, 16)).join(', ') || '59, 130, 246',
          '--secondary-color-rgb': secondaryColor.replace('#', '').match(/.{2}/g)?.map(x => parseInt(x, 16)).join(', ') || '139, 92, 246',
          '--accent-color-rgb': accentColor.replace('#', '').match(/.{2}/g)?.map(x => parseInt(x, 16)).join(', ') || '16, 185, 129'
        } as React.CSSProperties}
      >
        <Header
          settings={settings}
          customPages={customPages}
          sectionData={settings?.config_sections?.sections?.find(s => s.key === 'header') || {}}
          brandColor={primaryColor}
        />

        <main className="pt-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div className="overflow-hidden rounded-[32px] border border-slate-200/80 bg-white/80 shadow-[0_30px_80px_-24px_rgba(15,23,42,0.2)] backdrop-blur">
              <div className="border-b border-slate-200/70 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-8 py-10 lg:px-10 lg:py-12">
                <div className="max-w-4xl">
                  <span className="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.24em] text-slate-600">
                    Legal operations
                  </span>
                  <h1 className="mt-4 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                    {page.title}
                  </h1>
                  <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-600">
                    A polished experience designed for modern legal services, professional workflows, and streamlined client engagement.
                  </p>
                </div>
              </div>

              <div className="grid gap-8 p-8 lg:grid-cols-[1.15fr_0.85fr] lg:p-10">
                <article className="prose prose-lg max-w-none">
                  <div
                    className="text-slate-700 leading-relaxed"
                    dangerouslySetInnerHTML={{ __html: page.content }}
                  />
                </article>

                <div className="rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-3 shadow-sm">
                  <img
                    src="/screenshots/a-advocate-saas-pic.png"
                    alt={page.title}
                    loading="lazy"
                    className="w-full rounded-[18px] object-cover"
                    style={{ aspectRatio: '4 / 3' }}
                  />
                  <div className="mt-4 rounded-[18px] border border-slate-200 bg-white/80 p-4">
                    <p className="text-sm font-semibold text-slate-900">Professional legal experience</p>
                    <p className="mt-1 text-sm leading-6 text-slate-600">
                      Modern visuals, thoughtful structure, and a high-trust presentation tailored to legal services.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>

        <Footer
          settings={settings}
          sectionData={settings?.config_sections?.sections?.find(s => s.key === 'footer') || {}}
          brandColor={primaryColor}
        />
      </div>
    </>
  );
}
