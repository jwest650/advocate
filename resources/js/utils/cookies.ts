// Cookie utility functions for theme data storage
export const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') return;
  const maxAge = days * 24 * 60 * 60;
  // Use the same path as Laravel session cookies
  document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
};

export const getCookie = (name: string): string | null => {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    const cookieValue = parts.pop()?.split(';').shift();
    return cookieValue ? decodeURIComponent(cookieValue) : null;
  }
  return null;
};

// export const isDemoMode = (): boolean => {
//   // Check multiple sources for demo mode
//   const globalSettings = (window as any).page?.props?.globalSettings;
//   if (globalSettings?.is_demo !== undefined) {
//     return globalSettings.is_demo === true || globalSettings.is_demo === '1';
//   }
  
//   // Check window.globalSettings as fallback
//   const windowGlobalSettings = (window as any).globalSettings;
//   if (windowGlobalSettings?.is_demo !== undefined) {
//     return windowGlobalSettings.is_demo === true || windowGlobalSettings.is_demo === '1';
//   }
  
//   // Check window.isDemo as another fallback
//   if ((window as any).isDemo !== undefined) {
//     return (window as any).isDemo === true;
//   }
  
//   return false;
// };
export const isDemoMode = (): boolean => {
  // Check multiple sources for demo mode
  const globalSettings = (window as any).page?.props?.globalSettings;
  if (globalSettings?.is_demo !== undefined) {
    return globalSettings.is_demo === true || globalSettings.is_demo === '1';
  }
  
  // Check window.globalSettings as fallback
  const windowGlobalSettings = (window as any).globalSettings;
  if (windowGlobalSettings?.is_demo !== undefined) {
    return windowGlobalSettings.is_demo === true || windowGlobalSettings.is_demo === '1';
  }
  
  // Check window.isDemo as another fallback
  if ((window as any).isDemo !== undefined) {
    return (window as any).isDemo === true;
  }
  
  // Check if app_language cookie exists as an indicator of demo mode
  // This is a fallback for when the page hasn't loaded yet
  const appLangCookie = getCookie('app_language');
  if (appLangCookie && appLangCookie !== 'undefined' && appLangCookie !== 'null') {
    // Additional check: see if there's a demo-specific cookie pattern
    const selectedLangCookie = getCookie('selected_language');
    if (selectedLangCookie) {
      return true; // Both cookies suggest demo mode
    }
  }
  
  return false;
};