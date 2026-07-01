<?php

namespace App\Listeners;

use App\Events\NewRegulatoryBodyCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class SendNewRegulatoryBodyTwilioNotification
{
    public function __construct(
        private TwilioService $twilioService
    ) {
        //
    }

    public function handle(NewRegulatoryBodyCreated $event): void
    {
        $regulatoryBody = $event->regulatoryBody;
        $contact = $regulatoryBody->contact_phone;

        if (isNotificationTemplateEnabled('New Regulatory Body', createdBy(), 'twilio')) {
            $variables = [
                '{body_name}' => $regulatoryBody->name ?? '-',
                '{jurisdiction}' => $regulatoryBody->jurisdiction ?? '-',
                '{contact_info}' => $regulatoryBody->contact_phone ?? '-',
                '{created_by}' => $regulatoryBody->creator->name ?? '-',
            ];

            try {
                session()->forget('twilio_error');

                $twilioSid = getSetting('twilio_sid', '', createdBy());
                $twilioToken = getSetting('twilio_token', '', createdBy());
                $twilioFrom = getSetting('twilio_from', '', createdBy());

                if (filled($twilioSid) && filled($twilioToken) && filled($twilioFrom)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->twilioService->sendTemplateMessageToPhone(
                        templateName: 'New Regulatory Body',
                        variables: $variables,
                        toPhone: $contact,
                        language: $userLanguage,
                        userId: createdBy()
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio configuration is incomplete.');
                }
            } catch (Exception $e) {
                  \Log::error('Twilio notification failed: ' . $e->getMessage());
                session()->flash('twilio_error', 'Failed to send regulatory body create notification: ' . $e->getMessage());
            }
        }
    }
}
