<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientBillingInfo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientBillingInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        // Get company users
        $companyUsers = User::where('type', 'company')->get();
        
        foreach ($companyUsers as $companyUser) {
            // Get clients for this company
            $clients = Client::where('created_by', $companyUser->id)->get();
            
            // Get currencies for this company (extract original codes)
            $currencies = \App\Models\ClientBillingCurrency::where('created_by', $companyUser->id)->get();
            $currencyCodes = $currencies->map(function($currency) {
                return explode('_', $currency->code)[0]; // Get original code before underscore
            })->toArray();
            $defaultCurrency = $currencyCodes[0] ?? 'USD';
            
            if ($clients->count() > 0) {
                // Create 2-3 billing records per client
                foreach ($clients as $clientIndex => $client) {
                    $billingCount = rand(2, 3);
                    
                    for ($i = 1; $i <= $billingCount; $i++) {
                        $billingData = [
                            'client_id' => $client->id,
                            'billing_address' => $client->address ?: ($clientIndex * 100 + $i * 10) . ' Billing St, Finance City, FC 1234' . $clientIndex,
                            'billing_contact_name' => $client->name,
                            'billing_contact_email' => $client->email,
                            'billing_contact_phone' => $client->phone,
                            'payment_terms' => ['net_15', 'net_30', 'net_45', 'net_60', 'due_on_receipt'][($i - 1) % 5],
                            'custom_payment_terms' => null,
                            'currency' => !empty($currencyCodes) ? $currencyCodes[($i - 1) % count($currencyCodes)] : $defaultCurrency,
                            'billing_notes' => 'Billing record #' . $i . ' for client ' . $client->name . '. Standard billing terms apply.',
                            'status' => 'active',
                            'created_by' => $companyUser->id,
                        ];
                        
                        ClientBillingInfo::firstOrCreate([
                            'client_id' => $client->id,
                            'billing_address' => $billingData['billing_address'],
                            'created_by' => $companyUser->id
                        ], $billingData);
                    }
                }
            }
        }
    }
}