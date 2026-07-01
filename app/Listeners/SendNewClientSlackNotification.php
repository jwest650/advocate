<?php

namespace App\Listeners;

use App\Events\NewClientCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewClientSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewClientCreated $event): void
    {
        $client = $event->client;

       if (isNotificationTemplateEnabled('New Client', createdBy(), 'slack')) {
            $variables = [
                '{client_name}' => $client->name ?? '-',
                '{client_type}' => $client->clientType->name ?? '-',
                '{email}' => $client->email ?? '-',
                '{created_by}' => $client->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Client',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send client Create notification: ' . $e->getMessage());
            }
        }
    }
}