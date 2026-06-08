<?php

namespace App\Listeners;

use App\Events\TeamMemberCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendTeamMemberCreatedSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(TeamMemberCreated $event): void
    {
        $teamMember = $event->teamMember;

       if (isNotificationTemplateEnabled('Team Member Created', createdBy(), 'slack')) {
            $variables = [
                '{member_name}' => $teamMember->name ?? '-',
                '{email}' => $teamMember->email ?? '-',
                '{role}' => $teamMember->getRoleNames()->first() ?? '-',
                '{created_by}' => $teamMember->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Team Member Created',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send team member Create notification: ' . $e->getMessage());
            }
        }
    }
}
