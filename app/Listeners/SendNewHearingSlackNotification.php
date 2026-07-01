<?php

namespace App\Listeners;

use App\Events\NewHearingCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewHearingSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewHearingCreated $event): void
    {
        $hearing = $event->hearing;

       if (isNotificationTemplateEnabled('New Hearing', createdBy(), 'slack')) {
            $variables = [
                '{hearing_number}' => $hearing->hearing_id ?? '-',
                '{case_number}' => $hearing->case->case_id ?? '-',
                '{hearing_date}' => $hearing->hearing_date ? $hearing->hearing_date->format('M d, Y') : '-',
                '{court}' => $hearing->court->name ?? '-',
                '{judge}' => $hearing->judge->name ?? '-',
                '{created_by}' => $hearing->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Hearing',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send hearing Create notification: ' . $e->getMessage());
            }
        }
    }
}
