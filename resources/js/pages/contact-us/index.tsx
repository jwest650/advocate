import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { toast } from '@/components/custom-toast';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { CrudFormModal } from '@/components/CrudFormModal';

export default function ContactUsPage() {
  const { t } = useTranslation();
  const { auth, contacts, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [deleteDialog, setDeleteDialog] = useState({ open: false, item: null });
  const [isViewModalOpen, setIsViewModalOpen] = useState(false);
  const [currentContact, setCurrentContact] = useState(null);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route('contact-us.index'), {
      page: 1,
      search: searchTerm || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    router.get(route('contact-us.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Landing Page') },
    { title: t('Contact Us') }
  ];

  const handleAction = (action: string, item: any) => {
    if (action === 'delete') {
      setDeleteDialog({ open: true, item });
    } else if (action === 'view') {
      setCurrentContact(item);
      setIsViewModalOpen(true);
    }
  };

  const handleDeleteConfirm = () => {
    if (deleteDialog.item) {
      toast.loading(t('Deleting contact message...'));
      router.delete(route('contact-us.destroy', deleteDialog.item.id), {
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
            toast.error(`Failed to delete contact message: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const columns = [
    { key: 'name', label: t('Name') },
    { key: 'email', label: t('Email') },
    { key: 'subject', label: t('Subject') },
    {
      key: 'created_at',
      label: t('Date'),
      type: 'date',
    }
  ];

  const actions = [
    {
      label: t('View'),
      action: 'view',
      icon: 'Eye',
      className: 'text-blue-500 hover:text-blue-700',
      requiredPermission: 'manage-contact-us'
    },
    {
      label: t('Delete'),
      action: 'delete',
      icon: 'Trash2',
      className: 'text-red-500 hover:text-red-700',
      requiredPermission: 'manage-contact-us'
    }
  ];

  return (
    <PageTemplate
      title={t("Contact Us")}
      url="/contact-us"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[]}
          showFilters={false}
          setShowFilters={() => {}}
          hasActiveFilters={() => searchTerm !== ''}
          activeFilterCount={() => searchTerm ? 1 : 0}
          onResetFilters={() => {
            setSearchTerm('');
            router.get(route('contact-us.index'));
          }}
          onApplyFilters={() => {}}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            router.get(route('contact-us.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined
            });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={contacts?.data || []}
          from={contacts?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
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
          from={contacts?.from || 0}
          to={contacts?.to || 0}
          total={contacts?.total || 0}
          links={contacts?.links}
          entityName={t("contact messages")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      <ConfirmDialog
        open={deleteDialog.open}
        onOpenChange={(open) => setDeleteDialog({ open, item: null })}
        title={t('Delete contact message')}
        description={t('Are you sure you want to delete the message from {{name}}? This action cannot be undone.', { name: deleteDialog.item?.name })}
        onConfirm={handleDeleteConfirm}
        confirmText={t('Delete')}
        cancelText={t('Cancel')}
        variant="destructive"
      />

      <CrudFormModal
        isOpen={isViewModalOpen}
        onClose={() => setIsViewModalOpen(false)}
        onSubmit={() => {}}
        formConfig={{
          fields: [
            { name: 'name', label: t('Name'), type: 'text', readOnly: true },
            { name: 'email', label: t('Email'), type: 'email', readOnly: true },
            { name: 'subject', label: t('Subject'), type: 'text', readOnly: true },
            { name: 'message', label: t('Message'), type: 'textarea', readOnly: true },
            { name: 'created_at', label: t('Date'), type: 'text', readOnly: true }
          ],
          modalSize: 'lg'
        }}
        initialData={{
          ...currentContact,
                    // created_at: currentContact?.created_at ? new Date(currentContact.created_at).toLocaleString() : ''

          created_at: currentContact?.created_at ? (() => {
            try {
              const formattedDate = window.appSettings?.formatDateTime(currentContact.created_at, true);
              return formattedDate || new Date(currentContact.created_at).toLocaleString();
            } catch (error) {
              console.warn('Date formatting error in modal:', error);
              return new Date(currentContact.created_at).toLocaleString();
            }
          })() : ''
        }}
        title={t('View Contact Details')}
        mode='view'
      />
    </PageTemplate>
  );
}
