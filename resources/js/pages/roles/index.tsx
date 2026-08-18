import { PageCrudWrapper } from '@/components/PageCrudWrapper';
import { rolesConfig } from '@/config/crud/roles';
import { RolePermissionCheckboxGroup } from '@/components/RolePermissionCheckboxGroup';
import { PermissionBadges } from '@/components/PermissionBadges';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Info, Lock, ShieldCheck } from 'lucide-react';

export default function RolesPage() {
  const { t } = useTranslation();
  const { permissions, flash, auth } = usePage().props as any;
  const [config, setConfig] = useState(rolesConfig);



  // Customize the form fields to handle permissions properly
  useEffect(() => {
    if (permissions) {
      // With tDynamic, we don't need to translate the config here
      setConfig({
        ...rolesConfig,
        table: {
          ...rolesConfig.table,
          columns: [
            ...rolesConfig.table.columns.map(column => {
              // Role name — shield tile plus a badge for non-editable system roles
              if (column.key === 'label') {
                return {
                  ...column,
                  render: (value, row) => (
                    <div className="flex items-center gap-3">
                      <span className="bg-primary/10 text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-xl">
                        {row.is_system_role || row.is_editable === false ? (
                          <Lock className="h-4 w-4" />
                        ) : (
                          <ShieldCheck className="h-4 w-4" />
                        )}
                      </span>
                      <div className="min-w-0">
                        <div className="truncate font-semibold">{value || row.name}</div>
                        {(row.is_system_role || row.is_editable === false) && (
                          <span className="text-muted-foreground text-[11px] font-medium">{t('System role')}</span>
                        )}
                      </div>
                    </div>
                  )
                };
              }

              // Slug — rendered as a monospace chip
              if (column.key === 'name') {
                return {
                  ...column,
                  render: (value) => (
                    <span className="bg-muted text-muted-foreground inline-flex items-center rounded-md px-2 py-0.5 font-mono text-[11px]">
                      {value}
                    </span>
                  )
                };
              }

              return column;
            }),
            {
              key: 'permissions',
              label: t('Permissions'),
              render: (value, row) => <PermissionBadges permissions={value || []} />
            }
          ]
        },
        form: {
          ...rolesConfig.form,
          fields: [
            ...rolesConfig.form.fields.map(field => {
              if (field.name === 'label') {
                return {
                  ...field,
                  render: (fieldConfig, formData, onChange) => {
                    const isDisabled = formData?.id && !formData?.is_editable;
                    return (
                      <div className="relative">
                        <ShieldCheck className="text-muted-foreground pointer-events-none absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <input
                          type="text"
                          className={`border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-11 w-full rounded-xl border ps-10 pe-3 text-sm transition-shadow duration-300 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none ${isDisabled ? 'cursor-not-allowed opacity-60' : ''}`}
                          value={formData.label || ''}
                          onChange={(e) => onChange('label', e.target.value)}
                          disabled={isDisabled}
                          placeholder={t('Enter role name')}
                          required
                        />
                        {isDisabled && (
                          <Lock className="text-muted-foreground pointer-events-none absolute end-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2" />
                        )}
                      </div>
                    );
                  }
                };
              }
              return field;
            }).filter(field => field.name !== 'permissions'),
            {
              name: 'permissions',
              label: t('Role Permissions'),
              type: 'custom',
              colSpan: 12,
              render: (field, formData, onChange) => {
                return (
                  <div className="mt-5" id="permissions">
                    <div className="mb-4 border-t pt-5">
                      <div className="flex items-start gap-3">
                        <span className="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                          <ShieldCheck className="h-[18px] w-[18px]" />
                        </span>
                        <div>
                          <h3 className="text-base font-semibold tracking-tight">{t("Manage Permissions")}</h3>
                          <p className="text-muted-foreground mt-0.5 text-sm">
                            {t("Select permissions for this role. You can select all permissions at once or manage them by module.")}
                          </p>
                        </div>
                      </div>

                      {auth.user?.type !== 'superadmin' && (
                        <div className="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300">
                          <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                          <span>{t("Note: Only permissions for modules available to your role are shown.")}</span>
                        </div>
                      )}
                    </div>
                    <RolePermissionCheckboxGroup
                      permissions={permissions}
                      selectedPermissions={formData.permissions || []}
                      onChange={(selected) => {
                        onChange('permissions', selected);
                      }}
                    />
                  </div>
                );
              }
            }
          ]
        },

      });
    }
  }, [permissions, t]);

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Staff Management'), href: route('roles.index') },
    { title: t('Roles') }
  ];

  return (
    <PageCrudWrapper
      config={config}
      url="/roles"
      breadcrumbs={breadcrumbs}
    />
  );
}
