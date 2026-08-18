// components/RolePermissionCheckboxGroup.tsx
import { Checkbox } from '@/components/ui/checkbox';
import { IndeterminateCheckbox } from '@/components/ui/indeterminate-checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Ban, Globe, Layers, Search, ShieldCheck, User, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Input } from './ui/input';

interface Permission {
    id: string | number;
    name: string;
    label: string;
}

interface RolePermissionCheckboxGroupProps {
    permissions: Record<string, any[]>;
    selectedPermissions: any;
    onChange: (permissions: string[]) => void;
}

export function RolePermissionCheckboxGroup({ permissions, selectedPermissions, onChange }: RolePermissionCheckboxGroupProps) {
    const { t } = useTranslation();
    const [selected, setSelected] = useState<string[]>([]);
    const [searchTerm, setSearchTerm] = useState<string>('');

    // Filter permissions based on search term
    const filteredPermissions = searchTerm
        ? Object.fromEntries(
              Object.entries(permissions)
                  .filter(
                      ([module, modulePermissions]) =>
                          module.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          modulePermissions.some((p) => p.label.toLowerCase().includes(searchTerm.toLowerCase())),
                  )
                  .map(([module, modulePermissions]) => [
                      module,
                      modulePermissions.filter(
                          (p) => module.toLowerCase().includes(searchTerm.toLowerCase()) || p.label.toLowerCase().includes(searchTerm.toLowerCase()),
                      ),
                  ]),
          )
        : permissions;

    // Get all permission IDs
    const getAllPermissionIds = (): string[] => {
        const allIds: string[] = [];
        Object.values(filteredPermissions).forEach((group) => {
            group.forEach((permission) => {
                allIds.push(permission.id.toString());
            });
        });
        return allIds;
    };

    // Get all permission IDs for a specific module
    const getModulePermissionIds = (module: string): string[] => {
        return filteredPermissions[module]?.map((permission) => permission.id.toString()) || [];
    };

    // Initialize selected permissions
    useEffect(() => {
        if (!selectedPermissions || Object.keys(filteredPermissions).length === 0) {
            setSelected([]);
            return;
        }

        try {
            const nameMap = {};

            Object.values(filteredPermissions).forEach((group) => {
                group.forEach((permission) => {
                    nameMap[permission.name] = permission.id.toString();
                });
            });

            let processedPermissions: string[] = [];

            if (Array.isArray(selectedPermissions)) {
                processedPermissions = selectedPermissions
                    .map((p) => {
                        if (typeof p === 'object' && p !== null) {
                            if ('id' in p) return p.id.toString();
                            if ('name' in p) return nameMap[p.name] || p.name;
                        }
                        return nameMap[String(p)] || String(p);
                    })
                    .filter(Boolean);
            } else if (typeof selectedPermissions === 'object' && selectedPermissions !== null) {
                if ('permissions' in selectedPermissions && Array.isArray(selectedPermissions.permissions)) {
                    processedPermissions = selectedPermissions.permissions
                        .map((p) => {
                            if (typeof p === 'object' && p !== null) {
                                if ('id' in p) return p.id.toString();
                                if ('name' in p) return nameMap[p.name] || p.name;
                            }
                            return nameMap[String(p)] || String(p);
                        })
                        .filter(Boolean);
                }
            }

            setSelected(processedPermissions);
        } catch (error) {
            console.error('Error processing permissions:', error);
            setSelected([]);
        }
    }, [selectedPermissions]);

    const handlePermissionChange = (permissionId: string, checked: boolean) => {
        const newSelected = checked ? [...selected, permissionId] : selected.filter((id) => id !== permissionId);

        setSelected(newSelected);
        updateParent(newSelected);
    };

    const handleModuleChange = (module: string, checked: boolean) => {
        const modulePermissionIds = getModulePermissionIds(module);

        let newSelected: string[];

        if (checked) {
            const permissionsToAdd = modulePermissionIds.filter((id) => !selected.includes(id));
            newSelected = [...selected, ...permissionsToAdd];
        } else {
            newSelected = selected.filter((id) => !modulePermissionIds.includes(id));
        }

        setSelected(newSelected);
        updateParent(newSelected);
    };

    const handleSelectAll = (checked: boolean) => {
        let newSelected: string[];
        if (checked) {
            const allIds = getAllPermissionIds();
            const manageAnyIds = getManageAnyPermissionIds();
            const manageOwnIds = getManageOwnPermissionIds();
            newSelected = allIds.filter((id) => !manageAnyIds.includes(id) && !manageOwnIds.includes(id));
        } else {
            newSelected = [];
        }
        setSelected(newSelected);
        updateParent(newSelected);
    };

    // Get all manage-any permission IDs
    const getManageAnyPermissionIds = (): string[] => {
        const allIds: string[] = [];
        Object.values(filteredPermissions).forEach((group) => {
            group.forEach((permission) => {
                if (permission.name.includes('manage-any-')) {
                    allIds.push(permission.id.toString());
                }
            });
        });
        return allIds;
    };

    // Get all manage-own permission IDs
    const getManageOwnPermissionIds = (): string[] => {
        const allIds: string[] = [];
        Object.values(filteredPermissions).forEach((group) => {
            group.forEach((permission) => {
                if (permission.name.includes('manage-own-')) {
                    allIds.push(permission.id.toString());
                }
            });
        });
        return allIds;
    };

    const handleSelectAllManageAny = (checked: boolean) => {
        const manageAnyIds = getManageAnyPermissionIds();
        let newSelected: string[];

        if (checked) {
            // Get corresponding manage- module permissions for each manage-any- permission
            const modulePermissionIds: string[] = [];
            Object.values(filteredPermissions).forEach((group) => {
                group.forEach((permission) => {
                    if (manageAnyIds.includes(permission.id.toString())) {
                        // Extract module name from manage-any-{module} and find manage-{module}
                        const moduleName = permission.name.replace('manage-any-', '');
                        const baseManagePermission = group.find((p) => p.name === `manage-${moduleName}`);
                        if (baseManagePermission) {
                            modulePermissionIds.push(baseManagePermission.id.toString());
                        }
                    }
                });
            });

            newSelected = [...selected];
            // Add manage-any permissions
            const manageAnyToAdd = manageAnyIds.filter((id) => !newSelected.includes(id));
            newSelected = [...newSelected, ...manageAnyToAdd];
            // Add base manage- module permissions
            const moduleToAdd = modulePermissionIds.filter((id) => !newSelected.includes(id));
            newSelected = [...newSelected, ...moduleToAdd];
        } else {
            // Get corresponding manage- module permissions to uncheck
            const modulePermissionIds: string[] = [];
            Object.values(filteredPermissions).forEach((group) => {
                group.forEach((permission) => {
                    if (manageAnyIds.includes(permission.id.toString())) {
                        const moduleName = permission.name.replace('manage-any-', '');
                        const baseManagePermission = group.find((p) => p.name === `manage-${moduleName}`);
                        if (baseManagePermission) {
                            modulePermissionIds.push(baseManagePermission.id.toString());
                        }
                    }
                });
            });

            newSelected = selected.filter((id) => !manageAnyIds.includes(id) && !modulePermissionIds.includes(id));
        }

        setSelected(newSelected);
        updateParent(newSelected);
    };

    const handleSelectAllManageOwn = (checked: boolean) => {
        const manageOwnIds = getManageOwnPermissionIds();
        let newSelected: string[];

        if (checked) {
            // Get corresponding manage- module permissions for each manage-own- permission
            const modulePermissionIds: string[] = [];
            Object.values(filteredPermissions).forEach((group) => {
                group.forEach((permission) => {
                    if (manageOwnIds.includes(permission.id.toString())) {
                        const moduleName = permission.name.replace('manage-own-', '');
                        const baseManagePermission = group.find((p) => p.name === `manage-${moduleName}`);
                        if (baseManagePermission) {
                            modulePermissionIds.push(baseManagePermission.id.toString());
                        }
                    }
                });
            });

            newSelected = [...selected];
            const manageOwnToAdd = manageOwnIds.filter((id) => !newSelected.includes(id));
            newSelected = [...newSelected, ...manageOwnToAdd];
            const moduleToAdd = modulePermissionIds.filter((id) => !newSelected.includes(id));
            newSelected = [...newSelected, ...moduleToAdd];
        } else {
            // Get corresponding manage- module permissions to uncheck
            const modulePermissionIds: string[] = [];
            Object.values(filteredPermissions).forEach((group) => {
                group.forEach((permission) => {
                    if (manageOwnIds.includes(permission.id.toString())) {
                        const moduleName = permission.name.replace('manage-own-', '');
                        const baseManagePermission = group.find((p) => p.name === `manage-${moduleName}`);
                        if (baseManagePermission) {
                            modulePermissionIds.push(baseManagePermission.id.toString());
                        }
                    }
                });
            });

            newSelected = selected.filter((id) => !manageOwnIds.includes(id) && !modulePermissionIds.includes(id));
        }

        setSelected(newSelected);
        updateParent(newSelected);
    };

    const updateParent = (newSelected: string[]) => {
        const idToNameMap = {};

        // Use original permissions, not filtered ones
        Object.values(permissions).forEach((group) => {
            group.forEach((permission) => {
                idToNameMap[permission.id.toString()] = permission.name;
            });
        });

        const permissionNames = newSelected
            .map((id) => {
                return idToNameMap[id] || id;
            })
            .filter((name) => !!name);

        onChange(permissionNames);
    };

    // Check if all permissions are selected (excluding manage-any and manage-own)
    const isAllSelected = (() => {
        const allIds = getAllPermissionIds();
        const manageAnyIds = getManageAnyPermissionIds();
        const manageOwnIds = getManageOwnPermissionIds();
        const nonManageIds = allIds.filter((id) => !manageAnyIds.includes(id) && !manageOwnIds.includes(id));
        return nonManageIds.every((id) => selected.includes(id)) && nonManageIds.length > 0;
    })();

    // Check if all manage-any permissions are selected
    const isAllManageAnySelected = (): boolean => {
        const manageAnyIds = getManageAnyPermissionIds();
        return manageAnyIds.every((id) => selected.includes(id)) && manageAnyIds.length > 0;
    };

    // Check if all manage-own permissions are selected
    const isAllManageOwnSelected = (): boolean => {
        const manageOwnIds = getManageOwnPermissionIds();
        return manageOwnIds.every((id) => selected.includes(id)) && manageOwnIds.length > 0;
    };

    // Check if all permissions in a module are selected
    const isModuleSelected = (module: string): boolean => {
        const modulePermissionIds = getModulePermissionIds(module);
        return modulePermissionIds.every((id) => selected.includes(id)) && modulePermissionIds.length > 0;
    };

    // Check if some but not all permissions in a module are selected
    const isModuleIndeterminate = (module: string): boolean => {
        const modulePermissionIds = getModulePermissionIds(module);
        const selectedCount = modulePermissionIds.filter((id) => selected.includes(id)).length;
        return selectedCount > 0 && selectedCount < modulePermissionIds.length;
    };

    const totalCount = getAllPermissionIds().length;
    const selectedPercent = totalCount > 0 ? Math.round((selected.length / totalCount) * 100) : 0;
    const manageAnyIds = getManageAnyPermissionIds();
    const manageOwnIds = getManageOwnPermissionIds();
    const manageAnySelected = manageAnyIds.filter((id) => selected.includes(id)).length;
    const manageOwnSelected = manageOwnIds.filter((id) => selected.includes(id)).length;

    return (
        <div className="space-y-5">
            {/* Scoped animations */}
            <style>{`
                @keyframes permFadeUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .perm-in { opacity: 0; animation: permFadeUp .4s cubic-bezier(.16,.84,.44,1) forwards; }
                @media (prefers-reduced-motion: reduce) {
                    .perm-in { animation: none; opacity: 1; }
                }
            `}</style>

            {/* Master selector with progress */}
            <div className="bg-card relative overflow-hidden rounded-2xl border p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <span className="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                            <ShieldCheck className="h-[18px] w-[18px]" />
                        </span>
                        <div className="flex items-center gap-2.5">
                            <IndeterminateCheckbox
                                id="select-all-permissions-checkbox"
                                checked={isAllSelected}
                                onCheckedChange={(checked) => handleSelectAll(checked === true)}
                            />
                            <Label htmlFor="select-all-permissions-checkbox" className="cursor-pointer font-semibold">
                                {t('Select All Permissions')}
                            </Label>
                        </div>
                    </div>
                    <div className="text-end">
                        <div className="text-lg leading-none font-bold tabular-nums">
                            {selected.length}
                            <span className="text-muted-foreground text-sm font-normal"> / {totalCount}</span>
                        </div>
                        <div className="text-muted-foreground mt-1 text-[11px] font-medium tracking-wide uppercase">{t('selected')}</div>
                    </div>
                </div>
                <div className="bg-muted mt-3 h-1.5 w-full overflow-hidden rounded-full">
                    <div
                        className="bg-primary h-full rounded-full transition-[width] duration-700 ease-out"
                        style={{ width: `${selectedPercent}%` }}
                    />
                </div>
            </div>

            {/* Scope shortcuts */}
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div className="relative overflow-hidden rounded-2xl border border-blue-200 bg-blue-50/60 p-3.5 transition-colors duration-300 hover:bg-blue-50 dark:border-blue-500/25 dark:bg-blue-500/[0.08] dark:hover:bg-blue-500/[0.12]">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-2.5">
                            <IndeterminateCheckbox
                                id="select-all-manage-any-checkbox"
                                checked={isAllManageAnySelected()}
                                onCheckedChange={(checked) => handleSelectAllManageAny(checked === true)}
                            />
                            <Globe className="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
                            <Label
                                htmlFor="select-all-manage-any-checkbox"
                                className="cursor-pointer truncate font-semibold text-blue-700 dark:text-blue-300"
                            >
                                {t('Select All (Manage-All)')}
                            </Label>
                        </div>
                        <span className="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700 tabular-nums dark:bg-blue-500/20 dark:text-blue-300">
                            {manageAnySelected} {t('of')} {manageAnyIds.length}
                        </span>
                    </div>
                </div>

                <div className="relative overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3.5 transition-colors duration-300 hover:bg-emerald-50 dark:border-emerald-500/25 dark:bg-emerald-500/[0.08] dark:hover:bg-emerald-500/[0.12]">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-2.5">
                            <IndeterminateCheckbox
                                id="select-all-manage-own-checkbox"
                                checked={isAllManageOwnSelected()}
                                onCheckedChange={(checked) => handleSelectAllManageOwn(checked === true)}
                            />
                            <User className="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <Label
                                htmlFor="select-all-manage-own-checkbox"
                                className="cursor-pointer truncate font-semibold text-emerald-700 dark:text-emerald-300"
                            >
                                {t('Select All (Manage-Own)')}
                            </Label>
                        </div>
                        <span className="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 tabular-nums dark:bg-emerald-500/20 dark:text-emerald-300">
                            {manageOwnSelected} {t('of')} {manageOwnIds.length}
                        </span>
                    </div>
                </div>
            </div>

            {/* Search Box */}
            <div className="relative">
                <Search className="text-muted-foreground pointer-events-none absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2" />
                <Input
                    type="text"
                    placeholder={t('Search modules or permissions...')}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="h-11 w-full rounded-xl ps-10 pe-10"
                />
                {searchTerm && (
                    <button
                        type="button"
                        onClick={() => setSearchTerm('')}
                        className="text-muted-foreground hover:bg-muted hover:text-foreground absolute end-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full transition-colors"
                        aria-label={t('Clear')}
                    >
                        <X className="h-3.5 w-3.5" />
                    </button>
                )}
            </div>

            {/* Module Permissions */}
            <div className="space-y-4">
                {Object.entries(filteredPermissions).length > 0 ? (
                    Object.entries(filteredPermissions).map(([module, modulePermissions], moduleIndex) => {
                        const moduleId = `module-checkbox-${module.replace(/\s+/g, '-').toLowerCase()}`;
                        const moduleSelectedCount = modulePermissions.filter((p) => selected.includes(p.id.toString())).length;
                        const modulePercent = modulePermissions.length > 0 ? (moduleSelectedCount / modulePermissions.length) * 100 : 0;
                        const allOn = isModuleSelected(module);

                        return (
                            <div
                                key={module}
                                className={cn(
                                    'perm-in bg-card overflow-hidden rounded-2xl border transition-shadow duration-300 hover:shadow-sm',
                                    allOn && 'border-primary/30',
                                )}
                                style={{ animationDelay: `${Math.min(moduleIndex, 12) * 35}ms` }}
                            >
                                {/* Module Header */}
                                <div className="bg-muted/40 flex items-center justify-between gap-3 border-b p-3">
                                    <div className="flex min-w-0 items-center gap-2.5">
                                        <IndeterminateCheckbox
                                            id={moduleId}
                                            checked={allOn}
                                            indeterminate={isModuleIndeterminate(module)}
                                            onCheckedChange={(checked) => handleModuleChange(module, checked === true)}
                                        />
                                        <span
                                            className={cn(
                                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-300',
                                                allOn ? 'bg-primary/15 text-primary' : 'bg-background text-muted-foreground',
                                            )}
                                        >
                                            <Layers className="h-3.5 w-3.5" />
                                        </span>
                                        <Label htmlFor={moduleId} className="cursor-pointer truncate font-semibold">
                                            {module.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                        </Label>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-2.5">
                                        <div className="bg-muted hidden h-1.5 w-16 overflow-hidden rounded-full sm:block">
                                            <div
                                                className="bg-primary h-full rounded-full transition-[width] duration-500 ease-out"
                                                style={{ width: `${modulePercent}%` }}
                                            />
                                        </div>
                                        <span className="text-muted-foreground text-[11px] font-medium tabular-nums">
                                            {moduleSelectedCount} / {modulePermissions.length}
                                        </span>
                                    </div>
                                </div>

                                {/* Individual Permissions */}
                                <div className="p-3">
                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                        {modulePermissions.map((permission) => {
                                            const permissionId = `permission-checkbox-${permission.id.toString().replace(/\s+/g, '-').toLowerCase()}`;
                                            const checked = selected.includes(permission.id.toString()) || selected.includes(permission.name);

                                            return (
                                                <div
                                                    key={permission.id}
                                                    className={cn(
                                                        'flex items-center gap-2.5 rounded-xl border p-2.5 transition-all duration-200',
                                                        checked
                                                            ? 'border-primary/30 bg-primary/[0.06]'
                                                            : 'border-transparent bg-muted/40 hover:bg-muted',
                                                    )}
                                                >
                                                    <Checkbox
                                                        id={permissionId}
                                                        checked={checked}
                                                        onCheckedChange={(checked) => handlePermissionChange(permission.id.toString(), checked === true)}
                                                    />
                                                    <Label
                                                        htmlFor={permissionId}
                                                        className={cn(
                                                            'flex-1 cursor-pointer truncate text-[13px]',
                                                            checked ? 'text-primary font-medium' : 'text-foreground/80',
                                                        )}
                                                        title={permission.label}
                                                    >
                                                        {permission.label}
                                                    </Label>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        );
                    })
                ) : (
                    <div className="bg-card flex flex-col items-center justify-center rounded-2xl border py-14 text-center">
                        <div className="bg-muted mb-4 flex h-16 w-16 items-center justify-center rounded-2xl">
                            <Ban className="text-muted-foreground h-7 w-7" />
                        </div>
                        <p className="font-semibold">{t('No permissions found')}</p>
                        <p className="text-muted-foreground mt-1 text-sm">{t('Try adjusting your search criteria')}</p>
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => setSearchTerm('')}
                                className="text-primary mt-3 text-sm font-medium hover:underline"
                            >
                                {t('Clear')}
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
