<?php

namespace App\Listeners;

use App\Events\NewJudgeCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewJudgeSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewJudgeCreated $event): void
    {
        $judge = $event->judge;

       if (isNotificationTemplateEnabled('New Judge', createdBy(), 'slack')) {
            $variables = [
                '{judge_name}' => $judge->name ?? '-',
                '{court}' => $judge->court->name ?? '-',
                '{specialization}' => $judge->notes ?? '-',
                '{email}' => $judge->email ?? '-',
                '{created_by}' => $judge->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Judge',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack is not enabled or webhook URL is not set.');
                }
            } catch (Exception $e) {
                  \Log::error('slack notification failed: ' . $e->getMessage());
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send judge Create notification: ' . $e->getMessage());
            }
        }
    }
}
