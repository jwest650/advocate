<?php

namespace App\Listeners;

use App\Events\NewHearingCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewHearingListener
{
    public function handle(NewHearingCreated $event)
    {
         if(isEmailTemplateEnabled('New Hearing', createdBy()) && !IsDemo()){

        try {


            // Check if New Hearing email template is active for current user
            $emailService = new EmailTemplateService();

            $hearing = $event->hearing;

            if (!$hearing) {
                return;
            }

            // Load related data
            $case = \App\Models\CaseModel::find($hearing->case_id);
            $client = $case ? \App\Models\Client::find($case->client_id) : null;
            $court = \App\Models\Court::find($hearing->court_id);
            $hearingType = \App\Models\HearingType::find($hearing->hearing_type_id);

            if (!$client || !$client->email) {
                return;
            }

            // Combine date and time
            $dateTime = 'Not specified';
            if ($hearing->hearing_date && $hearing->hearing_time) {
                $date = \Carbon\Carbon::parse($hearing->hearing_date)->format('Y-m-d');
                $time = \Carbon\Carbon::parse($hearing->hearing_time)->format('H:i:s');
                $dateTime = \Carbon\Carbon::parse($date . ' ' . $time)->format('F j, Y \a\t g:i A');
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{hearing_number}' => $hearing->hearing_id ?? 'HR' . str_pad($hearing->id, 6, '0', STR_PAD_LEFT),
                '{type}' => $hearingType ? $hearingType->name : 'General Hearing',
                '{hearing_date}' => $hearing->hearing_date ? $hearing->hearing_date->format('F j, Y') : 'Date not specified',
                '{hearing_time}' => $hearing->hearing_time ? $hearing->hearing_time->format('g:i A') : 'Time not specified',
                '{date_time}' => $dateTime,
                '{court_name}' => $court ? $court->name : 'Court not assigned',
                '{duration}' => ($hearing->duration_minutes ? $hearing->duration_minutes : 60) . ' minutes',
                '{case_number}' => $case ? ($case->case_number ?: $case->title) : 'Case not assigned',
                '{client_name}' => $client ? $client->name : 'Client',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Hearing',
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
