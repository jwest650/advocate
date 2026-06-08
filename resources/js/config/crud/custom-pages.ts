import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { t } from '@/utils/i18n';

export const customPagesConfig: CrudConfig = {
  entity: {
    name: 'custom-pages',
    endpoint: route('landing-page.custom-pages.index'),
    permissions: {
      view: 'view-custom-pages',
      create: 'create-custom-pages',
      edit: 'edit-custom-pages',
      delete: 'delete-custom-pages'
    }
  },
  modalSize: '4xl',
  description: t('Manage custom landing pages'),
  table: {
    columns: [
      { key: 'title', label: t('Title'), sortable: true },
      { 
        key: 'content', 
        label: t('Content'), 
        render: (value) => {
          const plainText = value.replace(/<[^>]*>/g, '');
          return (
            <div className="max-w-xs truncate" title={plainText}>
              {plainText.substring(0, 100)}...
            </div>
          );
        }
      },
      { 
        key: 'is_active', 
        label: t('Status'), 
        render: columnRenderers.status({
          true: 'bg-green-100 text-green-800',
          false: 'bg-gray-100 text-gray-800'
        })
      },
      { 
        key: 'sort_order', 
        label: t('Sort Order'), 
        sortable: true 
      },
      { 
        key: 'created_at', 
        label: t('Created At'), 
        sortable: true, 
        type: 'date'
      }
    ],
    actions: [
      { 
        label: t('Edit'), 
        icon: 'Edit', 
        action: 'edit', 
        className: 'text-amber-500'
      },
      { 
        label: t('Delete'), 
        icon: 'Trash2', 
        action: 'delete', 
        className: 'text-red-500'
      }
    ]
  },
  search: {
    enabled: true,
    placeholder: t('Search custom pages...'),
    fields: ['title', 'content']
  },
  filters: [
    {
      key: 'is_active',
      label: t('Status'),
      type: 'select',
      options: [
        { value: 'all', label: t('All Status') },
        { value: '1', label: t('Active') },
        { value: '0', label: t('Inactive') }
      ]
    }
  ],
  form: {
    fields: [
      {
        key: 'title',
        label: t('Page Title'),
        type: 'text',
        required: true,
        placeholder: t('About Us')
      },
      {
        key: 'content',
        label: t('Content'),
        type: 'richtext',
        required: true,
        placeholder: t('Page content...')
      },
      {
        key: 'meta_title',
        label: t('Meta Title (SEO)'),
        type: 'text',
        placeholder: t('SEO title')
      },
      {
        key: 'meta_description',
        label: t('Meta Description (SEO)'),
        type: 'textarea',
        placeholder: t('SEO description')
      },
      {
        key: 'is_active',
        label: t('Active'),
        type: 'switch',
        defaultValue: true
      },
      {
        key: 'sort_order',
        label: t('Sort Order'),
        type: 'number',
        defaultValue: 0
      }
    ]
  }
};