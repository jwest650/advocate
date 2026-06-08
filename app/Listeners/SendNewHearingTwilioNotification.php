<?php

namespace App\Listeners;

use App\Events\NewHearingCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class SendNewHearingTwilioNotification
{
    public function __construct(
        private TwilioService $twilioService
    ) {
        //
    }

    public function handle(NewHearingCreated $event): void
    {
        $hearing = $event->hearing;
        $contact=$hearing->case->client->phone;
        if (isNotificationTemplateEnabled('New Hearing', createdBy(), 'twilio')) {
             $variables = [
                '{hearing_number}' => $hearing->hearing_id ?? '-',
                '{case_number}' => $hearing->case->case_id ?? '-',
                '{hearing_date}' => $hearing->hearing_date ? $hearing->hearing_date->format('M d, Y') : '-',
                '{court}' => $hearing->court->name ?? '-',
                '{judge}' => $hearing->judge->name ?? '-',
                '{created_by}' => $hearing->creator->name ?? '-',
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
                        templateName: 'New Hearing',
                        variables: $variables,
                        toPhone: $contact,
                        language: $userLanguage,
                        userId: createdBy()
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio configuration is incomplete.');
                }
            } catch (Exception $e) {
                session()->flash('twilio_error', 'Failed to send hearing create notification: ' . $e->getMessage());
            }
        }
    }
}
