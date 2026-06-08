<?php

namespace App\Listeners;

use App\Events\NewInvoiceCreated;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendNewInvoiceSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(NewInvoiceCreated $event): void
    {
        $invoice = $event->invoice;

       if (isNotificationTemplateEnabled('New Invoice', createdBy(), 'slack')) {
            $variables = [
                '{invoice_number}' => $invoice->invoice_number ?? '-',
                '{client_name}' => $invoice->client->name ?? '-',
                '{amount}' => $invoice->total_amount ? number_format($invoice->total_amount, 2) : '0.00',
                '{due_date}' => $invoice->due_date ? $invoice->due_date->format('d M, Y') : '-',
                '{created_by}' => $invoice->creator->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'New Invoice',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send invoice Create notification: ' . $e->getMessage());
            }
        }
    }
}
