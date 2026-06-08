<?php

namespace App\Listeners;

use App\Events\NewClientCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class SendNewClientTwilioNotification
{
    public function __construct(
        private TwilioService $twilioService
    ) {
        //
    }

    public function handle(NewClientCreated $event): void
    {
        $client = $event->client;
        $contact =$client->phone;

        if (isNotificationTemplateEnabled('New Client', createdBy(), 'twilio')) {
            $variables = [
                '{client_name}' => $client->name ?? '-',
                '{client_type}' => $client->clientType->name ?? '-',
               
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
                        templateName: 'New Client',
                        toPhone: $contact,
                        variables: $variables,
                        language: $userLanguage,
                        userId: createdBy()
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio configuration is incomplete.');
                }
            } catch (Exception $e) {
                session()->flash('twilio_error', 'Failed to send client create notification: ' . $e->getMessage());
            }
        }
    }
}
