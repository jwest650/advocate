<?php

namespace App\Listeners;

use App\Events\NewLicenseCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewLicenseSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewLicenseCreated $event): void
    {
        $license = $event->license;

       if (isNotificationTemplateEnabled('New License', createdBy(), 'slack')) {
            $variables = [
                '{license_number}' => $license->license_number ?? '-',
                '{license_type}' => $license->license_type ?? '-',
                '{issuing_authority}' => $license->issuing_authority ?? '-',
                '{expiry_date}' => $license->expiry_date ?? '-',
                '{created_by}' => $license->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New License',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send license Create notification: ' . $e->getMessage());
            }
        }
    }
}
