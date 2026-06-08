<?php

namespace App\Listeners;

use App\Events\NewTaskCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewTaskSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewTaskCreated $event): void
    {
        $task = $event->task;

       if (isNotificationTemplateEnabled('New Task', createdBy(), 'slack')) {
            $variables = [
                '{task_title}' => $task->title ?? '-',
                '{priority}' => $task->priority ?? '-',
                '{due_date}' => $task->due_date ?? '-',
                '{assigned_to}' => $task->assignedUser->name ?? '-',
                '{created_by}' => $task->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Task',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send task Create notification: ' . $e->getMessage());
            }
        }
    }
}
