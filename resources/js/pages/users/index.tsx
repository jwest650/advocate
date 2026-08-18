// pages/users/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Plus, Eye, Edit, Trash2, KeyRound, Lock, Unlock, MoreHorizontal, Mail, CalendarDays, ShieldCheck, Users as UsersIcon, UserCheck } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';

export default function Users() {
  const { t } = useTranslation();
  const { auth, users, roles, planLimits, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const getInitials = useInitials();

  // State
  const [activeView, setActiveView] = useState('list');
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedRole, setSelectedRole] = useState(pageFilters.role || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isResetPasswordModalOpen, setIsResetPasswordModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedRole !== 'all' || searchTerm !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedRole !== 'all' ? 1 : 0) + (searchTerm ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    if (selectedRole !== 'all') {
      params.role = selectedRole;
    }

    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleRoleFilter = (value: string) => {
    setSelectedRole(value);

    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    if (value !== 'all') {
      params.role = value;
    }

    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    const params: any = {
      sort_field: field,
      sort_direction: direction,
      page: 1
    };

    // Add search and filters
    if (searchTerm) {
      params.search = searchTerm;
    }

    if (selectedRole !== 'all') {
      params.role = selectedRole;
    }

    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);

    switch (action) {
      case 'view':
        setFormMode('view');
        setIsFormModalOpen(true);
        break;
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'reset-password':
        setIsResetPasswordModalOpen(true);
        break;
      case 'user-logs':
        router.get(route('users.logs', item.id));
        break;
      case 'toggle-status':
        handleToggleStatus(item);
        break;
      default:
        break;
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleFormSubmit = (formData: any) => {
    // Keep roles as single string value, not array
    if (formData.roles && Array.isArray(formData.roles)) {
      formData.roles = formData.roles[0];
    }

    if (formMode === 'create') {
      toast.loading(t('Creating user...'));

      router.post(route('users.store'), formData, {
        onSuccess: () => {
          setIsFormModalOpen(false);
          toast.dismiss();
          toast.success(t('User created successfully'));
        },
        onError: (errors) => {
          toast.dismiss();
          toast.error(`Failed to create user: ${Object.values(errors).join(', ')}`);
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating user...'));

      router.put(route("users.update", currentItem.id), formData, {
        onSuccess: () => {
          setIsFormModalOpen(false);
          toast.dismiss();
          toast.success(t('User updated successfully'));
        },
        onError: (errors) => {
          toast.dismiss();
          toast.error(`Failed to update user: ${Object.values(errors).join(', ')}`);
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting user...'));

    router.delete(route("users.destroy", currentItem.id), {
      onSuccess: () => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        toast.success(t('User deleted successfully'));
      },
      onError: (errors) => {
        toast.dismiss();
        toast.error(`Failed to delete user: ${Object.values(errors).join(', ')}`);
      }
    });
  };

  const handleResetPasswordConfirm = (data: { password: string, password_confirmation: string }) => {
    toast.loading(t('Resetting password...'));

    router.put(route('users.reset-password', currentItem.id), data, {
      onSuccess: () => {
        setIsResetPasswordModalOpen(false);
        toast.dismiss();
        toast.success(t('Password reset successfully'));
      },
      onError: (errors) => {
        toast.dismiss();
        toast.error(`Failed to reset password: ${Object.values(errors).join(', ')}`);
      }
    });
  };

  const handleToggleStatus = (user: any) => {
    const newStatus = user.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} user...`);

    router.put(route('users.toggle-status', user.id), {}, {
      onSuccess: () => {
        toast.dismiss();
        toast.success(`User ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully`);
      },
      onError: (errors) => {
        toast.dismiss();
        toast.error(`Failed to update user status: ${Object.values(errors).join(', ')}`);
      }
    });
  };

  const handleResetFilters = () => {
    setSelectedRole('all');
    setSearchTerm('');
    setShowFilters(false);

    router.get(route('users.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

// Add User Logs History button
  if (hasPermission(permissions, 'manage-users')) {
    pageActions.push({
      label: t('User Logs'),
      icon: <Eye className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('user-logs.index'))
    });
  }

  // Add the "Add New User" button if user has permission and within limits
  if (hasPermission(permissions, 'create-users')) {
    const canCreate = !planLimits || planLimits.can_create;
    pageActions.push({
      label: planLimits && !canCreate ? t('User Limit Reached ({{current}}/{{max}})', { current: planLimits.current_users, max: planLimits.max_users }) : t('Add User'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: canCreate ? 'default' : 'outline',
      onClick: canCreate ? () => handleAddNew() : () => toast.error(t('User limit exceeded. Your plan allows maximum {{max}} users. Please upgrade your plan.', { max: planLimits.max_users })),
      disabled: !canCreate
    });
  }


  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Team Members'), href: route('users.index') },
    { title: t('Members') }
  ];

  /* ── Presentation helpers ───────────────────────────────── */

  const formatJoined = (value: string) =>
    window.appSettings?.formatDateTime(value, false) || new Date(value).toLocaleDateString();

  const RolePills = ({ userRoles, limit = 2 }: { userRoles: any[]; limit?: number }) => {
    if (!userRoles || !userRoles.length) {
      return <span className="text-muted-foreground text-xs">{t('No role')}</span>;
    }

    const shown = userRoles.slice(0, limit);
    const rest = userRoles.length - shown.length;

    return (
      <div className="flex flex-wrap items-center gap-1.5">
        {shown.map((role: any) => (
          <span
            key={role.id}
            className="bg-primary/10 text-primary ring-primary/15 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset"
          >
            <ShieldCheck className="h-3 w-3" />
            {role.label || role.name}
          </span>
        ))}
        {rest > 0 && (
          <span className="bg-muted text-muted-foreground inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium">
            +{rest}
          </span>
        )}
      </div>
    );
  };

  const StatusPill = ({ status }: { status: string }) => {
    const active = status === 'active';
    return (
      <span
        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset ${
          active
            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/25'
            : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/25'
        }`}
      >
        <span className={`h-1.5 w-1.5 rounded-full ${active ? 'bg-emerald-500' : 'bg-rose-500'}`} />
        {active ? t('Active') : t('Inactive')}
      </span>
    );
  };

  const summaryCards = [
    {
      label: t('Total Members'),
      value: users?.total ?? 0,
      icon: UsersIcon,
      tone: 'from-blue-500 to-indigo-600',
    },
    {
      label: t('Showing'),
      value: `${users?.from ?? 0}–${users?.to ?? 0}`,
      icon: UserCheck,
      tone: 'from-emerald-500 to-teal-600',
    },
    {
      label: t('Roles'),
      value: (roles || []).length,
      icon: ShieldCheck,
      tone: 'from-violet-500 to-purple-600',
    },
  ];

  const planUsage =
    planLimits && planLimits.max_users
      ? Math.min(100, Math.round((Number(planLimits.current_users) / Number(planLimits.max_users)) * 100))
      : null;

  // Define table columns
  const columns = [
    {
      key: 'name',
      label: t('Name'),
      sortable: true,
      render: (value: any, row: any) => {
        return (
          <div className="flex items-center gap-3">
            <div className="relative shrink-0">
              <div className="from-primary to-primary/70 ring-card flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br text-sm font-semibold text-white ring-2">
                {getInitials(row.name)}
              </div>
              <span
                className={`ring-card absolute -end-0.5 -bottom-0.5 h-3 w-3 rounded-full ring-2 ${
                  row.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'
                }`}
              />
            </div>
            <div className="min-w-0">
              <div className="truncate font-semibold">{row.name}</div>
              <div className="text-muted-foreground flex items-center gap-1 truncate text-xs">
                <Mail className="h-3 w-3 shrink-0" />
                {row.email}
              </div>
            </div>
          </div>
        );
      }
    },
    {
      key: 'roles',
      label: t('Roles'),
      render: (value: any) => <RolePills userRoles={value} />
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value: string) => <StatusPill status={value} />
    },
    {
      key: 'created_at',
      label: t('Joined'),
      sortable: true,
        type: 'date',
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-users'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-users'
    },

    {
      label: t('Reset Password'),
      icon: 'KeyRound',
      action: 'reset-password',
      className: 'text-blue-500',
      requiredPermission: 'reset-password-users'
    },
    {
      label: t('Toggle Status'),
      icon: 'Lock',
      action: 'toggle-status',
      className: 'text-amber-500',
      requiredPermission: 'toggle-status-users'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-users'
    }
  ];

  return (
    <PageTemplate
      title={t("Members")}
      url="/users"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Scoped animations */}
      <style>{`
        @keyframes usersFadeUp {
          from { opacity: 0; transform: translateY(14px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .users-in { opacity: 0; animation: usersFadeUp .5s cubic-bezier(.16,.84,.44,1) forwards; }
        @media (prefers-reduced-motion: reduce) {
          .users-in { animation: none; opacity: 1; }
        }
      `}</style>

      <div className="space-y-4">
        {/* Summary strip */}
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {summaryCards.map((card, index) => {
            const CardIcon = card.icon;
            return (
              <div
                key={card.label}
                className="users-in bg-card group relative overflow-hidden rounded-2xl border p-4 transition-all duration-300 hover:shadow-md"
                style={{ animationDelay: `${index * 70}ms` }}
              >
                <div className="flex items-center gap-3">
                  <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white transition-transform duration-300 group-hover:scale-105 ${card.tone}`}
                  >
                    <CardIcon className="h-[18px] w-[18px]" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-muted-foreground text-[11px] font-semibold tracking-[0.12em] uppercase">{card.label}</p>
                    <p className="mt-0.5 text-xl leading-none font-bold tabular-nums">{card.value}</p>
                  </div>
                </div>
              </div>
            );
          })}

          {/* Plan usage — only when the backend reports limits */}
          {planUsage !== null && (
            <div
              className="users-in bg-card relative overflow-hidden rounded-2xl border p-4 transition-all duration-300 hover:shadow-md"
              style={{ animationDelay: '210ms' }}
            >
              <div className="flex items-center justify-between gap-2">
                <p className="text-muted-foreground text-[11px] font-semibold tracking-[0.12em] uppercase">{t('Plan Usage')}</p>
                <span className="text-muted-foreground text-xs tabular-nums">
                  {planLimits.current_users} / {planLimits.max_users}
                </span>
              </div>
              <p className="mt-1 text-xl leading-none font-bold tabular-nums">{planUsage}%</p>
              <div className="bg-muted mt-2.5 h-1.5 w-full overflow-hidden rounded-full">
                <div
                  className={`h-full rounded-full transition-[width] duration-1000 ease-out ${
                    planUsage >= 100 ? 'bg-rose-500' : planUsage >= 80 ? 'bg-amber-500' : 'bg-primary'
                  }`}
                  style={{ width: `${planUsage}%` }}
                />
              </div>
            </div>
          )}
        </div>

        {/* Search and filters section */}
        <div className="users-in bg-card rounded-2xl border p-4 shadow-sm" style={{ animationDelay: '120ms' }}>
          <SearchAndFilterBar
            searchTerm={searchTerm}
            onSearchChange={setSearchTerm}
            onSearch={handleSearch}
            filters={[
              {
                name: 'role',
                label: t('Role'),
                type: 'select',
                value: selectedRole,
                onChange: handleRoleFilter,
                options: [
                  { value: 'all', label: t('All Roles') },
                  ...(roles || []).map((role: any) => ({
                    value: role.id.toString(),
                    label: role.label || role.name
                  }))
                ]
              }
            ]}
            showFilters={showFilters}
            setShowFilters={setShowFilters}
            hasActiveFilters={hasActiveFilters}
            activeFilterCount={activeFilterCount}
            onResetFilters={handleResetFilters}
            onApplyFilters={applyFilters}
            currentPerPage={pageFilters.per_page?.toString() || "10"}
            onPerPageChange={(value) => {
              const params: any = { page: 1, per_page: parseInt(value) };

              if (searchTerm) {
                params.search = searchTerm;
              }

              if (selectedRole !== 'all') {
                params.role = selectedRole;
              }

              router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
            }}
            showViewToggle={true}
            activeView={activeView}
            onViewChange={setActiveView}
          />
        </div>

        {/* Content section */}
        {activeView === 'list' ? (
          <div className="users-in bg-card overflow-hidden rounded-2xl border shadow-sm" style={{ animationDelay: '180ms' }}>
            <CrudTable
              columns={columns}
              actions={actions}
              data={users?.data || []}
              from={users?.from || 1}
              onAction={handleAction}
              sortField={pageFilters.sort_field}
              sortDirection={pageFilters.sort_direction}
              onSort={handleSort}
              permissions={permissions}
              entityPermissions={{
                view: 'view-users',
                create: 'create-users',
                edit: 'edit-users',
                delete: 'delete-users'
              }}
            />

            {/* Pagination section */}
            <Pagination
              from={users?.from || 0}
              to={users?.to || 0}
              total={users?.total || 0}
              links={users?.links}
              entityName={t("users")}
              onPageChange={(url) => router.get(url)}
            />
          </div>
        ) : (
          <div>
            {/* Grid View */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {users?.data?.map((user: any, index: number) => (
                <div
                  key={user.id}
                  className="users-in bg-card group relative overflow-hidden rounded-2xl border transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                  style={{ animationDelay: `${180 + index * 50}ms` }}
                >
                  {/* Brand band */}
                  <div className="from-primary/20 via-primary/10 relative h-20 bg-gradient-to-br to-transparent">
                    <div
                      className="absolute inset-0 opacity-40"
                      style={{
                        backgroundImage:
                          'radial-gradient(circle at 25% 60%, rgba(255,255,255,.35) 1px, transparent 1px)',
                        backgroundSize: '18px 18px',
                      }}
                    />
                    {/* Actions dropdown */}
                    <div className="absolute end-2 top-2">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="bg-card/70 hover:bg-card h-8 w-8 p-0 backdrop-blur transition-colors"
                          >
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="z-50 w-48" sideOffset={5}>
                          {hasPermission(permissions, 'view-users') && (
                            <DropdownMenuItem onClick={() => handleAction('view', user)}>
                              <Eye className="mr-2 h-4 w-4" />
                              <span>{t("View User")}</span>
                            </DropdownMenuItem>
                          )}

                          {hasPermission(permissions, 'edit-users') && (
                            <DropdownMenuItem onClick={() => handleAction('reset-password', user)}>
                              <KeyRound className="mr-2 h-4 w-4" />
                              <span>{t("Reset Password")}</span>
                            </DropdownMenuItem>
                          )}
                          {hasPermission(permissions, 'edit-users') && (
                            <DropdownMenuItem onClick={() => handleAction('toggle-status', user)}>
                              {user.status === 'active' ?
                                <Lock className="mr-2 h-4 w-4" /> :
                                <Unlock className="mr-2 h-4 w-4" />
                              }
                              <span>{user.status === 'active' ? t("Disable User") : t("Enable User")}</span>
                            </DropdownMenuItem>
                          )}
                          <DropdownMenuSeparator />
                          {hasPermission(permissions, 'edit-users') && (
                            <DropdownMenuItem onClick={() => handleAction('edit', user)} className="text-amber-600">
                              <Edit className="mr-2 h-4 w-4" />
                              <span>{t("Edit")}</span>
                            </DropdownMenuItem>
                          )}
                          {hasPermission(permissions, 'delete-users') && (
                            <DropdownMenuItem onClick={() => handleAction('delete', user)} className="text-rose-600">
                              <Trash2 className="mr-2 h-4 w-4" />
                              <span>{t("Delete")}</span>
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </div>

                  <div className="-mt-9 px-5 pb-5">
                    {/* Avatar */}
                    <div className="relative inline-block">
                      <div className="from-primary to-primary/70 ring-card flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br text-lg font-bold text-white shadow-lg ring-4 transition-transform duration-300 group-hover:scale-105">
                        {getInitials(user.name)}
                      </div>
                      <span
                        className={`ring-card absolute -end-1 -bottom-1 h-4 w-4 rounded-full ring-[3px] ${
                          user.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'
                        }`}
                      />
                    </div>

                    <div className="mt-3 flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <h3 className="truncate text-base font-bold">{user.name}</h3>
                        <p className="text-muted-foreground mt-0.5 flex items-center gap-1 truncate text-xs">
                          <Mail className="h-3 w-3 shrink-0" />
                          {user.email}
                        </p>
                      </div>
                      <StatusPill status={user.status} />
                    </div>

                    {/* Role info */}
                    <div className="mt-3 min-h-[28px]">
                      <RolePills userRoles={user.roles} limit={3} />
                    </div>

                    {/* Joined date */}
                    <div className="text-muted-foreground mt-3 flex items-center gap-1.5 border-t pt-3 text-xs">
                      <CalendarDays className="h-3.5 w-3.5" />
                      {t("Joined:")} {formatJoined(user.created_at)}
                    </div>

                    {/* Action buttons */}
                    <div className="mt-3 flex gap-2">
                      {hasPermission(permissions, 'edit-users') && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleAction('edit', user)}
                          className="hover:border-primary/40 hover:text-primary h-9 flex-1 rounded-xl text-xs transition-all duration-300"
                        >
                          <Edit className="mr-1.5 h-3.5 w-3.5" />
                          {t("Edit")}
                        </Button>
                      )}

                      {hasPermission(permissions, 'view-users') && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleAction('view', user)}
                          className="hover:border-primary/40 hover:text-primary h-9 flex-1 rounded-xl text-xs transition-all duration-300"
                        >
                          <Eye className="mr-1.5 h-3.5 w-3.5" />
                          {t("View")}
                        </Button>
                      )}

                      {hasPermission(permissions, 'delete-users') && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleAction('delete', user)}
                          className="h-9 w-9 rounded-xl p-0 transition-all duration-300 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600 dark:hover:border-rose-500/30 dark:hover:bg-rose-500/10"
                          title={t("Delete")}
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Empty state */}
            {(!users?.data || users.data.length === 0) && (
              <div className="bg-card flex flex-col items-center justify-center rounded-2xl border py-16 text-center">
                <div className="bg-muted mb-4 flex h-16 w-16 items-center justify-center rounded-2xl">
                  <UsersIcon className="text-muted-foreground h-7 w-7" />
                </div>
                <p className="font-semibold">{t('No members found')}</p>
                {hasActiveFilters() && (
                  <Button variant="outline" size="sm" onClick={handleResetFilters} className="mt-4 rounded-full">
                    {t('Reset Filters')}
                  </Button>
                )}
              </div>
            )}

            {/* Pagination for grid view */}
            <div className="bg-card mt-4 overflow-hidden rounded-2xl border shadow-sm">
              <Pagination
                from={users?.from || 0}
                to={users?.to || 0}
                total={users?.total || 0}
                links={users?.links}
                entityName={t("users")}
                onPageChange={(url) => router.get(url)}
              />
            </div>
          </div>
        )}
      </div>

      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { name: 'name', label: t('Name'), type: 'text', required: true },
            { name: 'email', label: t('Email'), type: 'email', required: true },
            {
              name: 'password',
              label: t('Password'),
              type: 'password',
              required: true,
              conditional: (mode) => mode === 'create'
            },
            {
              name: 'password_confirmation',
              label: t('Confirm Password'),
              type: 'password',
              required: true,
              conditional: (mode) => mode === 'create'
            },
            {
              name: 'roles',
              label: t('Role'),
              type: 'select',
              options: roles ? roles.filter((role: any) => role.name !== 'client').map((role: any) => ({
                value: role.id.toString(),
                label: role.label || role.name
              })) : [],
              required: true
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          roles: currentItem.roles && currentItem.roles.length > 0 ? currentItem.roles[0].id.toString() : ''
        } : null}
        title={
          formMode === 'create'
            ? t('Add New User')
            : formMode === 'edit'
              ? t('Edit User')
              : t('View User')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="user"
      />

      {/* Reset Password Modal */}
      <CrudFormModal
        isOpen={isResetPasswordModalOpen}
        onClose={() => setIsResetPasswordModalOpen(false)}
        onSubmit={handleResetPasswordConfirm}
        formConfig={{
          fields: [
            { name: 'password', label: t('New Password'), type: 'password', required: true },
            { name: 'password_confirmation', label: t('Confirm Password'), type: 'password', required: true }
          ],
          modalSize: 'sm'
        }}
        initialData={{}}
        title={`Reset Password for ${currentItem?.name || 'User'}`}
        mode="edit"
      />
    </PageTemplate>
  );
}
