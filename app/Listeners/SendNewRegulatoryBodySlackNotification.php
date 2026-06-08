<?php

namespace App\Listeners;

use App\Events\NewRegulatoryBodyCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewRegulatoryBodySlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewRegulatoryBodyCreated $event): void
    {
        $regulatoryBody = $event->regulatoryBody;

       if (isNotificationTemplateEnabled('New Regulatory Body', createdBy(), 'slack')) {
            $variables = [
                '{body_name}' => $regulatoryBody->name ?? '-',
                '{jurisdiction}' => $regulatoryBody->jurisdiction ?? '-',
                '{contact_info}' => $regulatoryBody->contact_phone ?? '-',
                '{created_by}' => $regulatoryBody->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Regulatory Body',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send regulatory body Create notification: ' . $e->getMessage());
            }
        }
    }
}
