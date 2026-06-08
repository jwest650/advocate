import { useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { PageTemplate } from '@/components/page-template'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { useTranslation } from 'react-i18next'

interface EmailTemplate {
  id: number
  name: string
  is_enabled: boolean
}

interface Props {
  templates: EmailTemplate[]
}

export default function EmailNotificationSettings({ templates }: Props) {
  const { t } = useTranslation()
  const [settings, setSettings] = useState(
    templates.reduce((acc, template) => {
      acc[template.id] = template.is_enabled
      return acc
    }, {} as Record<number, boolean>)
  )

  const handleToggle = (templateId: number, enabled: boolean) => {
    setSettings(prev => ({
      ...prev,
      [templateId]: enabled
    }))
  }

  const handleSave = () => {
    const settingsArray = Object.entries(settings).map(([templateId, isEnabled]) => ({
      template_id: parseInt(templateId),
      is_enabled: isEnabled
    }))

    router.put(route('company.email-notifications.update'), {
      settings: settingsArray
    })
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Settings') },
    { title: t('Email Notification Settings') }
  ]

  return (
    <PageTemplate
      title={t('Email Notification Settings')}
      url={route('company.email-notifications.index')}
      breadcrumbs={breadcrumbs}
    >
      <Head title="Email Notification Settings" />

      <div className="bg-white rounded-lg shadow">
        <div className="flex items-center justify-between p-6 border-b">
          <div>
            <h2 className="text-lg font-semibold">{t('Email Notification Settings')}</h2>
            <p className="text-sm text-gray-600 mt-1">
              {t('Edit email notification settings')}
            </p>
          </div>
          <Button onClick={handleSave} className="bg-green-500 hover:bg-green-600">
            {t('Save Changes')}
          </Button>
        </div>
        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {templates.map((template) => (
              <div
                key={template.id}
                className="flex items-center justify-between py-3"
              >
                <div className="flex-1">
                  <span className="text-sm font-medium text-gray-900">{template.name}</span>
                </div>
                <Switch
                  checked={settings[template.id]}
                  onCheckedChange={(checked) => handleToggle(template.id, checked)}
                />
              </div>
            ))}
          </div>
        </div>
      </div>
    </PageTemplate>
  )
}