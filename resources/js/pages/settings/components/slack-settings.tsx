import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { Send, Save, MessageSquare, HelpCircle, Bell } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

export default function SlackSettings() {
    const { t } = useTranslation();
    const { props } = usePage();
    const isDemo = (props as any).is_demo;
    const [isEnabled, setIsEnabled] = useState(false);
    const [webhookUrl, setWebhookUrl] = useState('');

    // Initialize notifications state
    const [notifications, setNotifications] = useState<Record<string, boolean>>({});

    useEffect(() => {
        // Load current Slack notification settings
        axios.get(route('settings.slack-notifications.get'))
            .then(response => {
                setNotifications(response.data);
            })
            .catch(error => {
                console.error('Failed to load Slack notification settings:', error);
            });
    }, []);

    const [isSaving, setIsSaving] = useState(false);
    const [isTesting, setIsTesting] = useState(false);
    const [availableNotifications, setAvailableNotifications] = useState<any[]>([]);

    useEffect(() => {
        // Load available notifications
        axios.get(route('settings.slack-notifications.available'))
            .then(response => {
                setAvailableNotifications(response.data);
            })
            .catch(error => {
                console.error('Failed to load available notifications:', error);
            });

        // Load Slack config
        axios.get(route('settings.slack-config.get'))
            .then(response => {
                setWebhookUrl(response.data.slack_webhook_url || '');
                setIsEnabled(response.data.slack_enabled || false);
            })
            .catch(error => {
                console.error('Failed to load Slack config:', error);
            });
    }, []);

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();

        // Check if webhook URL is null or empty when Slack is enabled
        if (isEnabled && !webhookUrl) {
            toast.error(t('Please enter a webhook URL when Slack integration is enabled'));
            return;
        }

        setIsSaving(true);

        // toast.loading(t("Saving Slack settings..."));

        try {
            await router.post(route('settings.slack-notifications.update'), {
                slack_enabled: isEnabled,
                slack_webhook_url: webhookUrl,
                ...notifications
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                     setIsSaving(false);
                    const successMessage = page.props.flash?.success;
                    const errorMessage = page.props.flash?.error;

                    if (successMessage) {
                        toast.success(successMessage);
                    } else if (errorMessage) {
                        toast.error(errorMessage);
                    }
                },
                onError: (errors) => {
                    toast.dismiss();
                    toast.error(t("Failed to save Slack settings"));
                    console.error('Slack settings error:', errors);
                },
                onFinish: () => {
                    setIsSaving(false);
                }
            });
        } catch (error) {
            toast.dismiss();
            toast.error(t("Failed to save Slack settings"));
            setIsSaving(false);
        }
    };

    const handleTest = async () => {
        if (!webhookUrl) {
            toast.error(t("Please enter a webhook URL first"));
            return;
        }

        setIsTesting(true);
        toast.loading(t("Sending test message..."));

        try {
            await router.post(route('slack.test-webhook'), {
                webhook_url: webhookUrl,
                debug: false
            }, {
                preserveState: true,
                onSuccess: () => {
                    toast.dismiss();
                    toast.success(t("Test message sent successfully to Slack!"));
                },
                onError: (errors) => {
                    toast.dismiss();
                    toast.error(t("Failed to send test message"));
                    console.error('Slack test error:', errors);
                },
                onFinish: () => {
                    setIsTesting(false);
                }
            });
        } catch (error) {
            toast.dismiss();
            toast.error(t("Failed to send test message"));
            setIsTesting(false);
        }
    };

    const handleNotificationChange = (key: string, value: boolean) => {
        setNotifications(prev => ({
            ...prev,
            [key]: value
        }));
    };

    return (
        <SettingsSection
            title={t("Slack Integration")}
            description={t("Configure Slack webhook integration for real-time notifications")}
            action={
                <Button type="submit" form="slack-settings-form" size="sm">
                    <Save className="h-4 w-4 mr-2" />
                    {t("Save Changes")}
                </Button>
            }
        >
            <form id="slack-settings-form" onSubmit={handleSave}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Settings */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center space-x-2">
                                    <MessageSquare className="h-5 w-5 text-primary" />
                                    <h3 className="text-base font-medium">{t("Integration Settings")}</h3>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Enable Integration */}
                                <div className="flex items-center justify-between p-4 border rounded-md">
                                    <div>
                                        <Label className="font-medium">{t("Enable Slack Integration")}</Label>
                                        <p className="text-xs text-muted-foreground mt-1">{t("Turn on to receive notifications in Slack")}</p>
                                    </div>
                                    <Switch
                                        checked={isEnabled}
                                        onCheckedChange={setIsEnabled}
                                    />
                                </div>

                                {/* Webhook URL */}
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2">
                                        <Label className="font-medium">{t("Webhook URL")}</Label>
                                        <HelpCircle className="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Input
                                        type="url"
                                        placeholder={t("https://hooks.slack.com/services/...")}
                                        value={webhookUrl}
                                        onChange={(e) => setWebhookUrl(e.target.value)}
                                        disabled={!isEnabled}
                                        className="font-mono text-sm"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t("Create a Slack app and add an Incoming Webhook to get this URL")}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2 mb-6">
                                    <Bell className="h-5 w-5 text-emerald-500" />
                                    <h3 className="font-medium text-gray-900">{t(" Notification Settings")}</h3>
                                </div>
                                {/* Notification Types */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {availableNotifications.length === 0 ? (
                                        <div className="col-span-full text-center py-8 text-gray-500">
                                            <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                            <p>No notification templates found.</p>
                                            <p className="text-sm">Contact your administrator to set up notification templates.</p>
                                        </div>
                                    ) : (
                                        availableNotifications.map(item => (
                                            <div key={item.name} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                                <Label htmlFor={item.name} className="text-sm font-medium">{t(item.label)}</Label>
                                                <Switch
                                                    id={item.name}
                                                    checked={notifications[item.name] || false}
                                                    onCheckedChange={(checked) => handleNotificationChange(item.name, checked)}
                                                    disabled={!isEnabled}
                                                />
                                            </div>
                                        ))
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Test & Instructions */}
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="flex items-center gap-2 mb-4">
                                    <Send className="h-4 w-4 text-primary" />
                                    <h3 className="text-base font-medium">{t("Test Slack Integration")}</h3>
                                </div>

                                <p className="text-sm text-muted-foreground mb-4">
                                    {t("Send a test message to verify your Slack configuration is working correctly.")}
                                </p>

                                <Button
                                    type="button"
                                    onClick={handleTest}
                                    disabled={!isEnabled || !webhookUrl || isTesting}
                                    className="w-full"
                                >
                                    <Send className="h-4 w-4 mr-2" />
                                    {isTesting ? t("Sending...") : t("Send Test Message")}
                                </Button>

                                <p className="text-xs text-muted-foreground text-center mb-6 mt-4">
                                    {t("Enter a webhook URL to test the integration")}
                                </p>

                                {/* Setup Instructions */}
                                <div className="p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                                    <h4 className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                        {t("Setup Instructions")}
                                    </h4>
                                    <ol className="text-xs text-blue-800 dark:text-blue-200 space-y-1 list-decimal list-inside">
                                        <li>{t("Go to your Slack workspace")}</li>
                                        <li>{t("Create a new Slack app")}</li>
                                        <li>{t("Enable Incoming Webhooks")}</li>
                                        <li>{t("Add webhook to workspace")}</li>
                                        <li>{t("Copy the webhook URL here")}</li>
                                    </ol>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </SettingsSection>
    );
}
