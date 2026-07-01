<?php

namespace App\Listeners;

use App\Events\NewJudgeCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class SendNewJudgeTwilioNotification
{
    public function __construct(
        private TwilioService $twilioService
    ) {
        //
    }

    public function handle(NewJudgeCreated $event): void
    {
        $judge = $event->judge;
        $contact = $judge->phone;

        if (isNotificationTemplateEnabled('New Judge', createdBy(), 'twilio')) {
            $variables = [
                '{judge_name}' => $judge->name ?? '-',
                '{court}' => $judge->court->name ?? '-',
                '{specialization}' => $judge->notes ?? '-',
                '{email}' => $judge->email ?? '-',
                '{created_by}' => $judge->creator->name ?? '-',
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
                        templateName: 'New Judge',
                        variables: $variables,
                        toPhone: $contact,
                        language: $userLanguage,
                        userId: createdBy()
                    );
                } else {
                    \Log::error('Twilio configuration incomplete');
                    session()->flash('twilio_error', 'Twilio configuration is incomplete.');
                }
            } catch (Exception $e) {
                \Log::error('Twilio notification failed: ' . $e->getMessage());
                session()->flash('twilio_error', 'Failed to send judge create notification: ' . $e->getMessage());
            }
        }
    }
}
