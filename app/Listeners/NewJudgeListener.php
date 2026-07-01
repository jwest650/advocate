<?php

namespace App\Listeners;

use App\Events\NewJudgeCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewJudgeListener
{
    public function handle(NewJudgeCreated $event)
    {
         if(isEmailTemplateEnabled('New Judge', createdBy()) && !IsDemo()){

        try {


            // Check if New Judge email template is active for current user
            $emailService = new EmailTemplateService();

            $judge = $event->judge;

            if (!$judge) {
                return;
            }

            // Load related data
            $court = \App\Models\Court::find($judge->court_id);

            // For judges, we typically notify the admin user since judges don't have their own email in this system
            $adminUser = auth()->user();

            if (!$adminUser || !$adminUser->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{judge_name}' => $judge->name ?? 'Judge Name',
                '{court_name}' => $court ? $court->name : 'Court not assigned',
                '{email}' => $judge->email ?? 'Not provided',
                '{contact_no}' => $judge->phone ?? 'Not provided',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Judge',
                $variables,
                (string) $adminUser->email,
                (string) $adminUser->name,
                $userLanguage
            );
        } catch (Exception $e) {
            return back()->withErrors(['error' => __($e->getMessage())]);
        }
    }
}
}
