<?php

namespace App\Listeners;

use App\Events\InvoiceSent;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SendInvoiceSentSlackNotification
{

    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(InvoiceSent $event): void
    {
        $invoice = $event->invoice;

       if (isNotificationTemplateEnabled('Invoice Sent', createdBy(), 'slack')) {
            $variables = [
                '{invoice_number}' => $invoice->invoice_number ?? '-',
                '{client_name}' => $invoice->client->name ?? '-',
                '{amount}' => $invoice->total_amount ? number_format($invoice->total_amount, 2) : '0.00',
                '{sent_date}'=> $invoice->updated_at->format('d-m-Y') ?? '-',
                '{sent_by}' => auth()->user()->name ?? '-',
            ];

            try {
                // Clear any existing slack error
                session()->forget('slack_error');

                $slackWebhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($slackWebhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Invoice Sent',
                        variables: $variables,
                        webhookUrl: $slackWebhookUrl,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('slack_error', 'Failed to send invoice sent notification: ' . $e->getMessage());
            }
        }
    }
}
