import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { CrudTable } from '@/components/CrudTable';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Send } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { ConfirmDialog } from '@/components/ConfirmDialog';

export default function NewsletterPage() {
  const { t } = useTranslation();
  const { auth, subscriptions, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '');
  const [showFilters, setShowFilters] = useState(false);
  const [deleteDialog, setDeleteDialog] = useState({ open: false, item: null });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route('newsletter.index'), {
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSendNewsletter = () => {
    toast.loading(t('Sending newsletter...'));
    router.post(route('newsletter.send'), {}, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash?.success) {
          toast.success(page.props.flash.success);
        } else if (page.props.flash?.error) {
          toast.error(page.props.flash.error);
        } else {
          toast.success(t('Newsletter sent successfully!'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'object' && errors !== null) {
          const errorMessages = Object.values(errors).flat();
          toast.error(`Failed to send newsletter: ${errorMessages.join(', ')}`);
        } else {
          toast.error(t('Failed to send newsletter. Please try again.'));
        }
      }
    });
  };



  const handleAction = (action: string, item: any) => {
    if (action === 'delete') {
      setDeleteDialog({ open: true, item });
    }
  };

  const handleDeleteConfirm = () => {
    if (deleteDialog.item) {
      toast.loading(t('Deleting subscription...'));
      router.delete(route('newsletter.destroy', deleteDialog.item.id), {
        onSuccess: (page) => {
          setDeleteDialog({ open: false, item: null });
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(page.props.flash.success);
          } else if (page.props.flash.error) {
            toast.error(page.props.flash.error);
          }
        },
        onError: (errors) => {
          toast.dismiss();
          setDeleteDialog({ open: false, item: null });
          if (typeof errors === 'string') {
            toast.error(errors);
          } else if (errors.error) {
            toast.error(errors.error);
          } else {
            toast.error(`Failed to delete subscription: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Landing Page') },
    { title: t('Newsletter') }
  ];

  const columns = [
    { key: 'email', label: t('Email') },
    {
      key: 'subscribed_at',
      label: t('Subscribed At'),
      render: (value: string) => new Date(value).toLocaleDateString()
    },
    {
      key: 'unsubscribed_at',
      label: t('Status'),
      render: (value: string) => (
        <span className={`px-2 py-1 rounded-full text-xs font-weight-medium ${
          value ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
        }`}>
          {value ? t('Unsubscribed') : t('Subscribed')}
        </span>
      )
    },
  ];

  const actions = [
    {
      label: t('Delete'),
      action: 'delete',
      icon: 'Trash2',
      className: 'text-red-600 hover:text-red-900',
      requiredPermission: 'manage-contact-us'
    }
  ];

  return (
    <PageTemplate
      title={t("Newsletter")}
      url="/newsletter"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={() => searchTerm !== '' || (statusFilter !== '' && statusFilter !== 'all')}
          activeFilterCount={() => (searchTerm ? 1 : 0) + (statusFilter && statusFilter !== 'all' ? 1 : 0)}
          onResetFilters={() => {
            setSearchTerm('');
            setStatusFilter('all');
            router.get(route('newsletter.index'));
          }}
          onApplyFilters={() => {
            router.get(route('newsletter.index'), {
              page: 1,
              search: searchTerm || undefined,
              status: statusFilter || undefined,
              per_page: pageFilters.per_page
            });
          }}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            router.get(route('newsletter.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter || undefined
            });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={subscriptions?.data || []}
          from={subscriptions?.from || 1}
          onAction={handleAction}
          permissions={permissions}
          entityPermissions={{
            view: 'manage-contact-us',
            create: false,
            edit: false,
            delete: 'manage-contact-us'
          }}
          showActions={true}
        />

        <Pagination
          from={subscriptions?.from || 0}
          to={subscriptions?.to || 0}
          total={subscriptions?.total || 0}
          links={subscriptions?.links}
          entityName={t("subscriptions")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      <ConfirmDialog
        open={deleteDialog.open}
        onOpenChange={(open) => setDeleteDialog({ open, item: null })}
        title={t('Delete newsletter subscription')}
        description={t('Are you sure you want to delete {{email}}? This action cannot be undone.', { email: deleteDialog.item?.email })}
        onConfirm={handleDeleteConfirm}
        confirmText={t('Delete')}
        cancelText={t('Cancel')}
        variant="destructive"
      />
    </PageTemplate>
  );
}
