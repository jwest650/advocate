<?php

namespace App\Listeners;

use App\Events\NewCleRecordCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewCleRecordListener
{
    public function handle(NewCleRecordCreated $event)
    {
         if(isEmailTemplateEnabled('New CLE Record', createdBy()) && !IsDemo()){

        try {


            // Check if New CLE Record email template is active for current user
            $emailService = new EmailTemplateService();

            $cleRecord = $event->cleRecord;

            if (!$cleRecord) {
                return;
            }

            // Get the user associated with this CLE record
            $user = \App\Models\User::find($cleRecord->user_id);

            if (!$user || !$user->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{team_member}' => $user->name ?? 'Team Member',
                '{course_name}' => $cleRecord->course_name ?? 'CLE Course',
                '{provider}' => $cleRecord->provider ?? 'Provider',
                '{credit_earned}' => $cleRecord->credits_earned ?? '0',
                '{credit_required}' => $cleRecord->credits_required ?? 'Not specified',
                '{certificate_num}' => $cleRecord->certificate_number ?? 'Not provided',
                '{completion_date}' => $cleRecord->completion_date ? $cleRecord->completion_date->format('F j, Y') : 'Not specified',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New CLE Record',
                $variables,
                (string) $user->email,
                (string) $user->name,
                $userLanguage
            );
        } catch (Exception $e) {
            return back()->withErrors(['error' => __($e->getMessage())]);
        }
    }
}
}
