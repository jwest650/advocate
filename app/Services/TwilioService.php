<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\User;
use Exception;
use Twilio\Rest\Client;

class TwilioService
{
   

    public function sendTemplateMessageToPhone(string $templateName, array $variables, string $toPhone, string $language = 'en', int $userId = null)
    {
        try {
            // Check if Twilio notification is enabled for this template
            if (!$this->isTwilioNotificationEnabled($templateName)) {
                return false;
            }

            // Get notification template with type check
            $template = NotificationTemplate::where('name', $templateName)
                ->where('type', 'twilio')
                ->first();

            if (!$template) {
                throw new Exception("Notification template '{$templateName}' not found");
            }

            // Get template content for the specified language
            $templateLang = $template->notificationTemplateLangs()
                ->where('lang', $language)
                ->where('created_by', createdBy())
                ->first();

            // Fallback to English if language not found
            if (!$templateLang) {
                $templateLang = $template->notificationTemplateLangs()
                    ->where('lang', 'en')
                    ->where('created_by', createdBy())
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            // Replace variables in content
            $message = $this->replaceVariables($templateLang->content, $variables);

            // Send SMS to specified phone
            return $this->sendSMS($toPhone, $message);
        } catch (Exception $e) {
            \Log::error('Twilio SMS sending failed: ' . $e->getMessage());
            session()->flash('twilio_error', $e->getMessage());
            return false;
        }
    }

    private function replaceVariables(string $content, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    private function isTwilioNotificationEnabled(string $templateName): bool
    {
        return isNotificationTemplateEnabled($templateName, createdBy(), 'twilio');
    }

    private function getNotificationPhoneNumber(int $userId = null): ?string
    {
        $userId = $userId ?: createdBy();

        // Try to get user's phone number from profile
        $user = User::find($userId);
        if ($user && isset($user->phone) && $user->phone) {
            return $user->phone;
        }

        // Fallback to admin phone from settings
        $adminPhone = getSetting('admin_phone', null, $userId);
        if ($adminPhone) {
            return $adminPhone;
        }

        // No phone number available
        return null;
    }

    private function sendSMS(string $toPhone, string $message): bool
    {
        $twilioSid = getSetting('twilio_sid', '', createdBy());
        $twilioToken = getSetting('twilio_token', '', createdBy());
        $twilioFrom = getSetting('twilio_from', '', createdBy());

        if (!$twilioSid || !$twilioToken || !$twilioFrom) {
            throw new Exception("Twilio settings not configured. Please configure Twilio settings.");
        }

        $twilio = new Client($twilioSid, $twilioToken);
    //      dd([
    //     'debug_mode' => true,
    //     'to' => $toPhone,
    //     'from' => $twilioFrom,
    //     'message' => $message,
    //     'twilio_sid' => $twilioSid,
    //     'twilio_token_hidden' => substr($twilioToken, 0, 6) . '******', // hide sensitive token
    // ]);

        $twilio->messages->create($toPhone, [
            'from' => $twilioFrom,
            'body' => $message
        ]);

        return true;
    }

    public function sendTestMessage(string $toPhone, int $userId = null): bool
    {
        $message = 'This is a test message from your Advocate system. Twilio SMS is working correctly!';
        return $this->sendSMS($toPhone, $message);
    }
}
