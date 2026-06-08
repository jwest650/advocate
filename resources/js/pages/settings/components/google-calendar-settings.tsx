import { toast } from '@/components/custom-toast';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { router } from '@inertiajs/react';
import { Save, Calendar, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface GoogleCalendarSettingsProps {
    settings?: Record<string, string>;
}

export default function GoogleCalendarSettings({ settings = {} }: GoogleCalendarSettingsProps) {
    const { t } = useTranslation();

    const [googleCalendarSettings, setGoogleCalendarSettings] = useState({
        googleCalendarEnabled: settings.googleCalendarEnabled === 'true' || settings.googleCalendarEnabled === '1',
        googleCalendarId: settings.googleCalendarId || '',
    });

    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isSyncing, setIsSyncing] = useState(false);

    const handleSettingsChange = (field: string, value: string | boolean) => {
        setGoogleCalendarSettings((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file && file.type === 'application/json') {
            setSelectedFile(file);
        } else {
            toast.error(t('Please select a valid JSON file'));
        }
    };

    const submitGoogleCalendarSettings = (e: React.FormEvent) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('googleCalendarEnabled', googleCalendarSettings.googleCalendarEnabled ? '1' : '0');
        formData.append('googleCalendarId', googleCalendarSettings.googleCalendarId);

        if (selectedFile) {
            formData.append('googleCalendarJson', selectedFile);
        }

        router.post(route('settings.google-calendar.update'), formData, {
            preserveScroll: true,
            onSuccess: (page) => {
                toast.dismiss();
                const successMessage = page.props.flash?.success;
                const errorMessage = page.props.flash?.error;

                if (successMessage) {
                    toast.success(successMessage);
                } else if (errorMessage) {
                    toast.error(errorMessage);
                } else {
                    toast.success(t('Email settings saved successfully'));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save email settings');
                toast.error(errorMessage);
            }
        });
    };

    const handleSync = () => {
        setIsSyncing(true);

        router.post(route('settings.google-calendar.sync'), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const successMessage = page.props.flash?.success;
                if (successMessage) {
                    toast.success(successMessage);
                }
            },
            onError: (errors) => {
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Sync failed');
                toast.error(errorMessage);
            },
            onFinish: () => {
                setIsSyncing(false);
            }
        });
    };

    return (
        <SettingsSection
            title={t('Google Calendar Settings')}
            description={t('Configure Google Calendar integration for event synchronization')}
            action={
                <Button type="submit" form="google-calendar-settings-form" size="sm">
                    <Save className="mr-2 h-4 w-4" />
                    {t('Save Changes')}
                </Button>
            }
        >
            <form id="google-calendar-settings-form" onSubmit={submitGoogleCalendarSettings} className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                        <Label htmlFor="googleCalendarEnabled" className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Enable Google Calendar Integration')}
                        </Label>
                        <p className="text-muted-foreground text-sm">
                            {t('Enable synchronization with Google Calendar for events and appointments')}
                        </p>
                    </div>
                    <Switch
                        id="googleCalendarEnabled"
                        checked={googleCalendarSettings.googleCalendarEnabled}
                        onCheckedChange={(checked) => handleSettingsChange('googleCalendarEnabled', checked)}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="googleCalendarId">{t('Google Calendar ID')}</Label>
                    <Input
                        id="googleCalendarId"
                        type="text"
                        placeholder={t('Enter your Google Calendar ID')}
                        value={googleCalendarSettings.googleCalendarId}
                        onChange={(e) => handleSettingsChange('googleCalendarId', e.target.value)}
                        disabled={!googleCalendarSettings.googleCalendarEnabled}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="googleCalendarJson">{t('Service Account JSON File')}</Label>
                    <Input
                        id="googleCalendarJson"
                        type="file"
                        accept=".json"
                        onChange={handleFileSelect}
                        disabled={!googleCalendarSettings.googleCalendarEnabled}
                    />
                    {selectedFile && (
                        <p className="text-sm text-green-600">
                            {t('Selected file')}: {selectedFile.name}
                        </p>
                    )}
                </div>

                <div className="flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleSync}
                        disabled={!googleCalendarSettings.googleCalendarEnabled || isSyncing}
                    >
                        <RefreshCw className={`mr-2 h-4 w-4 ${isSyncing ? 'animate-spin' : ''}`} />
                        {isSyncing ? t('Syncing...') : t('Test Sync')}
                    </Button>
                </div>
            </form>
        </SettingsSection>
    );
}
