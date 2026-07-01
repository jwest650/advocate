<?php

namespace App\Listeners;

use App\Events\NewCaseCreated;
use App\Services\TwilioService;
use App\Models\User;
use Exception;

class SendNewCaseTwilioNotification
{
    public function __construct(
        private TwilioService $twilioService
    ) {}

    public function handle(NewCaseCreated $event): void
    {
        $case = $event->case;

        $userId = $case->created_by ?? auth()->id();
        $client = $case->client;
        $contact = $case->client->phone;


        if (!$userId) {
            return;
        }
                if (isNotificationTemplateEnabled('New Court', createdBy(), 'twilio')) {


        $variables = [
            '{case_number}' => $case->case_id ?? '-',
            '{case_type}' => $case->caseType->name ?? '-',
            '{created_by}' => $case->creator->name ?? '-',
        ];

        try {
            $createdByUser = User::find($userId);
            $userLanguage = $createdByUser->lang ?? 'en';


            // Send notification to client if they have a phone number
            if ($client && !empty($client->phone)) {
                $this->twilioService->sendTemplateMessageToPhone(
                    templateName: 'New Case',
                    variables: $variables,
                    toPhone: $contact,
                    language: $userLanguage,
                    userId: $userId
                );
            }
        } catch (Exception $e) {
            \Log::error('Failed to send New Case Twilio notification: ' . $e->getMessage());
        }
    }
}
}
