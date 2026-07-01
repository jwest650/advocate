<?php

namespace App\Listeners;

use App\Events\NewLicenseCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewLicenseListener
{
    public function handle(NewLicenseCreated $event)
    {
         if(isEmailTemplateEnabled('New License', createdBy()) && !IsDemo()){


        try {


            $emailService = new EmailTemplateService();

            $license = $event->license;

            if (!$license) {
                return;
            }

            // Load related data
            $user = \App\Models\User::find($license->user_id);
            $regulatoryBody = \App\Models\RegulatoryBody::find($license->regulatory_body_id);

            if (!$user || !$user->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'User',
                '{team_member}' => $user->name,
                '{license_number}' => $license->license_number ?? '',
                '{license_type}' => $license->license_type ?? '',
                '{issuing_authority}' => $regulatoryBody ? $regulatoryBody->name : 'Not specified',
                '{jurisdiction}' => $license->jurisdiction ?? '',
                '{issue_date}' => $license->issue_date ?? '',
                '{expiry_date}' => $license->expiry_date ?? '',
                '{status}' => $license->status ?? '',
                '{notes}' => $license->notes ?? '',
                '{license_holder_name}' => $user->name,
                '{license_holder_email}' => $user->email,
                '{app_name}' => config('app.name'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New License',
                $variables,
                $user->email,
                $user->name,
                $userLanguage
            );
        } catch (Exception $e) {
            return back()->withErrors(['error' => __($e->getMessage())]);
        }
    }
}
}
