<?php

namespace App\Listeners;

use App\Events\NewRegulatoryBodyCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewRegulatoryBodyListener
{
    public function handle(NewRegulatoryBodyCreated $event)
    {
         if(isEmailTemplateEnabled('New Regulatory Body', createdBy()) && !IsDemo()){

        try {


            // Check if New Regulatory Body email template is active for current user
            $emailService = new EmailTemplateService();

            $regulatoryBody = $event->regulatoryBody;

            if (!$regulatoryBody) {
                return;
            }

            // For regulatory bodies, we typically notify the admin user
            $adminUser = auth()->user();

            if (!$adminUser || !$adminUser->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{name}' => $regulatoryBody->name ?? 'Regulatory Body',
                '{jurisdiction}' => $regulatoryBody->jurisdiction ?? 'Not specified',
                '{email}' => $regulatoryBody->contact_email ?? 'Not provided',
                '{phoneno}' => $regulatoryBody->contact_phone ?? 'Not provided',
                '{address}' => $regulatoryBody->address ?? 'Not provided',
                '{website}' => $regulatoryBody->website ?? 'Not provided',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Regulatory Body',
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
