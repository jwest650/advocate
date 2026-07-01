<?php

namespace App\Listeners;

use App\Events\NewInvoiceCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewInvoiceListener
{
    public function handle(NewInvoiceCreated $event)
    {
         if(isEmailTemplateEnabled('New Invoice', createdBy()) && !IsDemo()){

        try {


            // Check if New Invoice email template is active for current user
            $emailService = new EmailTemplateService();

            $invoice = $event->invoice;

            if (!$invoice) {
                return;
            }

            // Load related data
            $client = \App\Models\Client::find($invoice->client_id);
            $case = $invoice->case_id ? \App\Models\CaseModel::find($invoice->case_id) : null;

            if (!$client || !$client->email) {
                return;
            }

            $variables = [
                '{user_name}' => auth()->user()->name ?? 'System Administrator',
                '{client}' => $client->name ?? 'Client',
                '{case}' => $case ? $case->title : 'N/A',
                '{invoice_date}' => $invoice->invoice_date ? $invoice->invoice_date->format('F j, Y') : 'Not specified',
                '{due_date}' => $invoice->due_date ? $invoice->due_date->format('F j, Y') : 'Not specified',
                '{total_amount}' => $invoice->total_amount ? number_format($invoice->total_amount, 2) : '0.00',
                '{invoice_number}' => $invoice->invoice_number ?? 'INV' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Invoice',
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
