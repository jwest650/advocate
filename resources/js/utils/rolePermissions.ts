// utils/rolePermissions.ts
export const normalizeUserRole = (userRole?: string | null): string => {
  if (!userRole) return '';

  return userRole.toString().trim().toLowerCase().replace(/[_-]+/g, ' ');
};

export const isSuperAdminRole = (userRole?: string | null): boolean => {
  const normalizedRole = normalizeUserRole(userRole);
  return normalizedRole === 'superadmin' || normalizedRole === 'super admin';
};

export const getModulesFromNavigation = (userRole: string): string[] => {
  const superAdminModules = [
    'dashboard',
    'companies',
    'nfc_cards',
    'nfc_card_order_requests',
    'campaigns',
    'plans',
    'plan_requests',
    'plan_orders',
    'domain_requests',
    'currencies',
    'referral',
    'settings'
  ];

  const companyModules = [
    'dashboard',
    'users',
    'roles',
    'contacts',
    'appointments',
    'nfc_cards',
    'campaigns',
    'plans',
    'referral',
    'settings'
  ];

  return isSuperAdminRole(userRole) ? superAdminModules : companyModules;
};

export const filterPermissionsByRole = (permissions: Record<string, string[]>, userRole: string): Record<string, string[]> => {
  const allowedModules = getModulesFromNavigation(userRole);
  const filteredPermissions: Record<string, string[]> = {};

  Object.keys(permissions).forEach(module => {
    if (allowedModules.includes(module)) {
      filteredPermissions[module] = permissions[module];
    }
  });

  return filteredPermissions;
};