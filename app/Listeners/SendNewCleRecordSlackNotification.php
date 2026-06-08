<?php

namespace App\Listeners;

use App\Events\NewCleRecordCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewCleRecordSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewCleRecordCreated $event): void
    {
        $cleRecord = $event->cleRecord;

       if (isNotificationTemplateEnabled('New CLE Record', createdBy(), 'slack')) {
            $variables = [
                '{course_title}' => $cleRecord->course_name ?? '-',
                '{provider}' => $cleRecord->provider ?? '-',
                '{credits_earned}' => $cleRecord->credits_earned ?? '0',
                '{created_by}' => $cleRecord->creator->name ?? '-',
                '{completion_date}' => $cleRecord->completion_date ?? '-',

            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New CLE Record',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send CLE record Create notification: ' . $e->getMessage());
            }
        }
    }
}
