<?php

namespace App\Listeners;

use App\Events\NewCourtCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class SendNewCourtTwilioNotification
{
    public function __construct(
        private TwilioService $twilioService
    ) {
        //
    }

    public function handle(NewCourtCreated $event): void
    {
        $court = $event->court;
        $contact = $court->phone;


        if (isNotificationTemplateEnabled('New Court', createdBy(), 'twilio')) {
            $variables = [
                '{court_name}' => $court->name ?? '-',
                '{location}' => $court->address ?? '-',
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
                        templateName: 'New Court',
                        toPhone: $contact,
                        variables: $variables,
                        language: $userLanguage,
                        userId: createdBy()
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio configuration is incomplete.');
                }
            } catch (Exception $e) {
                session()->flash('twilio_error', 'Failed to send court create notification: ' . $e->getMessage());
            }
        }
    }
}
