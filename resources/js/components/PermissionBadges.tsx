// components/PermissionBadges.tsx
import React from 'react';
import { useTranslation } from 'react-i18next';

interface Permission {
  id: number | string;
  name: string;
  label: string;
}

interface PermissionBadgesProps {
  permissions: Permission[];
  maxDisplay?: number;
}

export function PermissionBadges({ permissions = [], maxDisplay = 3 }: PermissionBadgesProps) {
  const { t } = useTranslation();
  if (!permissions || !Array.isArray(permissions) || permissions.length === 0) {
    return <span className="text-muted-foreground text-sm">-</span>;
  }

  return (
    <div className="flex flex-wrap items-center gap-1.5">
      {permissions.slice(0, maxDisplay).map((permission, index) => (
        <span
          key={index}
          className="bg-primary/10 text-primary ring-primary/15 inline-flex max-w-[13rem] items-center truncate rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset"
          title={permission.label || permission.name}
        >
          {permission.label || permission.name}
        </span>
      ))}
      {permissions.length > maxDisplay && (
        <span className="bg-muted text-muted-foreground inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums">
          +{permissions.length - maxDisplay} {t("more")}
        </span>
      )}
    </div>
  );
}
