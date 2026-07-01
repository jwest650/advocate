import { usePage } from "@inertiajs/react";

// Get base URL from page props or window
const getBaseUrl = (): string => {
  try {
    // Try to get from page props first
    const { base_url } = usePage().props as any;
    if (base_url) return base_url;
  } catch {
    // If usePage fails, try to get from window object or use default
  }

  // Fallback to window.APP_URL if available, otherwise construct from location
  if (typeof window !== 'undefined') {
    // Check if APP_URL is available in window
    if ((window as any).APP_URL) {
      return (window as any).APP_URL;
    }
  }

};

// Currency formatting function
export const formatCurrency = (amount: string | number, useSuperAdminSettings = false) => {
  if (typeof window !== 'undefined' && window.appSettings?.formatCurrency) {
    const numericAmount =
      typeof amount === 'number' ? amount : parseFloat(amount);

    if (useSuperAdminSettings && window.appSettings?.formatCurrencyWithSuperAdminSettings) {
      return window.appSettings.formatCurrencyWithSuperAdminSettings(numericAmount, { showSymbol: true });
    }

    return window.appSettings.formatCurrency(numericAmount, { showSymbol: true });
  }
  return amount;
};

// Get full image path helper - consistent with reference project
export const getImagePath = (path: string): string => {
  if (!path) return '';
  if (path.startsWith('http')) return path;

  const baseUrl = getBaseUrl();

  // If path already contains storage/media, just prepend base URL
  if (path.includes('storage/media')) {
    return path.startsWith('/') ? `${baseUrl}${path}` : `${baseUrl}/${path}`;
  }

  // For paths starting with /images/, prepend the base URL
  if (path.startsWith('/images/')) {
    return `${baseUrl}${path}`;
  }

  // For paths starting with images/ (relative), prepend the base URL
  if (path.startsWith('images/')) {
    return `${baseUrl}/${path}`;
  }

  // For legacy paths like 'logo/logo-dark.png' from reference project
  if (path.startsWith('logo/')) {
    return `${baseUrl}/images/logos/${path.replace('logo/', '')}`;
  }

  // For legacy paths like 'logos/logo-dark.png', convert to new format
  if (path.startsWith('logos/')) {
    return `${baseUrl}/images/${path}`;
  }

  // For bare filenames, assume they're in the logos directory
  if (!path.includes('/')) {
    return `${baseUrl}/images/logos/${path}`;
  }

  // Default fallback - treat as relative path from root
  return `${baseUrl}/${path}`;
};

// Date formatting function
export const formatDate = (date: string | Date) => {
  const d = new Date(date);
  return d.toLocaleDateString();
};

// String capitalize function
export const capitalize = (str: string) => {
  if (!str) return '';

  return str
    .toLowerCase() // Convert everything to lowercase first
    .replace(/_/g, ' ') // Replace underscores with spaces
    .split(' ') // Split into words
    .map(word => word.charAt(0).toUpperCase() + word.slice(1)) // Capitalize first letter of each word
    .join(' '); // Join back with spaces
};


// Get status icon based on status value
export const getStatusIcon = (status: string | boolean | number) => {
  // Handle boolean values
  if (typeof status === 'boolean') {
    return status ? 'Unlock' : 'Lock';
  }

  // Handle string values
  if (typeof status === 'string') {
    const lowerStatus = status.toLowerCase();
    if (['active', 'enabled', 'open', 'published', 'approved', 'completed', 'paid'].includes(lowerStatus)) {
      return 'Unlock';
    }
    if (['inactive', 'disabled', 'closed', 'draft', 'pending', 'cancelled', 'unpaid'].includes(lowerStatus)) {
      return 'Lock';
    }
  }

  // Handle numeric values (1 = active, 0 = inactive)
  if (typeof status === 'number') {
    return status === 1 ? 'Unlock' : 'Lock';
  }

  // Default to Lock for unknown status
  return 'Lock';
};

// Get status label based on current status
export const getStatusLabel = (status: string | boolean | number, t: (key: string) => string) => {
  const icon = getStatusIcon(status);
  return icon === 'Unlock' ? t('Deactivate') : t('Activate');
};

// Get dynamic action config for status toggle
export const getStatusAction = (item: any, t: (key: string) => string, permission: string) => {
  const status = item.status;
  return {
    label: getStatusLabel(status, t),
    icon: getStatusIcon(status),
    action: 'toggle-status',
    className: 'text-green-500',
    requiredPermission: permission
  };
};

export const getIsDemo = () => {
  try {
    const { is_demo } = usePage().props as any;
    console.log('is_demo value:', is_demo);
    return is_demo === true || is_demo === 'true';
  } catch {
    return false;
  }
};

// Format currency using super admin settings for plans and referrals
export const formatCurrencyForPlansAndReferrals = (amount: string | number) => {
  if (typeof window !== 'undefined' && window.appSettings?.formatCurrencyWithSuperAdminSettings) {
    const numericAmount = typeof amount === 'number' ? amount : parseFloat(amount);
    return window.appSettings.formatCurrencyWithSuperAdminSettings(numericAmount, { showSymbol: true });
  }
  return formatCurrency(amount);
};


// Format currency using company settings
export const formatCurrencyForCompany = (amount: string | number) => {
  if (typeof window !== 'undefined' && window.appSettings?.formatCurrency) {
    const numericAmount = typeof amount === 'number' ? amount : parseFloat(amount);
    return window.appSettings.formatCurrency(numericAmount, { showSymbol: true });
  }
  return formatCurrency(amount);
};
