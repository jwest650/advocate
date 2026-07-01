<?php

namespace App\Listeners;

use App\Events\NewCaseCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewCaseListener
{
    public function handle(NewCaseCreated $event)
    {
         if(isEmailTemplateEnabled('New Case', createdBy()) && !IsDemo()){

        try {


            // Check if New Case email template is active for current user
            $emailService = new EmailTemplateService();

            $case = $event->case;

            if (!$case) {
                return;
            }

            // Load related data
            $client = \App\Models\Client::find($case->client_id);
            $caseType = \App\Models\CaseType::find($case->case_type_id);
            $court = \App\Models\Court::find($case->court_id);

            if (!$client || !$client->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{case_id}' => $case->case_number ?? 'CS' . str_pad($case->id, 6, '0', STR_PAD_LEFT),
                '{title}' => $case->title ?? 'Case Title',
                '{client}' => $client->name ?? 'Client',
                '{type}' => $caseType ? $caseType->name : 'General Case',
                '{filling_date}' => $case->filing_date ? $case->filing_date->format('F j, Y') : 'Not specified',
                '{expected_complete_date}' => $case->expected_completion_date ? $case->expected_completion_date->format('F j, Y') : 'Not specified',
                '{opposing_party}' => $case->opposing_party ?? 'Not specified',
                '{court}' => $court ? $court->name : 'Court not assigned',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Case',
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
