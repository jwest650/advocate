<?php

namespace App\Listeners;

use App\Events\NewClientCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewClientListener
{
    public function handle(NewClientCreated $event)
    {
         if(isEmailTemplateEnabled('New Client', createdBy()) && !IsDemo()){

        try {


            // Check if New Client email template is active for current user
            $emailService = new EmailTemplateService();

            $client = $event->client;

            if (!$client) {
                return;
            }

            // Load related data
            $clientType = \App\Models\ClientType::find($client->client_type_id);

            if (!$client || !$client->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{name}' => $client->name ?? 'Client Name',
                '{email}' => $client->email ?? 'Not provided',
                '{phone_no}' => $client->phone ?? 'Not provided',
                '{dob}' => $client->date_of_birth ? $client->date_of_birth->format('F j, Y') : 'Not provided',
                '{client_type}' => $clientType ? $clientType->name : 'General Client',
                '{tax_id}' => $client->tax_id ?? 'Not provided',
                '{tax_rate}' => $client->tax_rate ? $client->tax_rate . '%' : 'Not provided',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Client',
                $variables,
                (string) $client->email,
                (string) $client->name,
                $userLanguage
            );
        } catch (Exception $e) {
            return back()->withErrors(['error' => __($e->getMessage())]);
        }
    }
}
}
