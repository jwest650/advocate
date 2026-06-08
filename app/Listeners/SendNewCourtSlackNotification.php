<?php

namespace App\Listeners;

use App\Events\NewCourtCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewCourtSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewCourtCreated $event): void
    {
        $court = $event->court;

       if (isNotificationTemplateEnabled('New Court', createdBy(), 'slack')) {
            $variables = [
                '{court_name}' => $court->name ?? '-',
                '{court_type}' => $court->courtType->name ?? '-',
                '{jurisdiction}' => $court->jurisdiction ?? '-',
                '{location}' => $court->address ?? '-',
                '{created_by}' => $court->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Court',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send court Create notification: ' . $e->getMessage());
            }
        }
    }
}
