<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Get company users
        $companyUsers = User::where('type', 'company')->get();

        foreach ($companyUsers as $companyUser) {
            // Create client role for this company
            $clientRole = Role::firstOrCreate([
                'name' => 'client',
                'guard_name' => 'web',
                'created_by' => $companyUser->id
            ], [
                'label' => 'Client',
                'description' => 'Client with limited access to their cases and documents'
            ]);

            $clientRole->syncPermissions([
                // Dashboard
                'manage-dashboard',

                // Calender
                'manage-calendar',
                'view-calendar',

                // Cases
                'manage-cases',
                'view-cases',

                // Case Documents
                'manage-case-documents',
                'view-case-documents',
                'download-case-documents',

                // Case Notes
                'manage-case-notes',
                'view-case-notes',

                // Case Timelines
                'manage-case-timelines',
                'view-case-timelines',

                // Expenses
                'manage-expenses',
                'view-expenses',

                // Client Documents
                'manage-client-documents',
                'view-client-documents',
                'download-client-documents',

                // Client Billing
                'manage-client-billing',
                'view-client-billing',

                // Hearings
                'manage-hearings',
                'view-hearings',

                // Documents
                'manage-documents',
                'view-documents',
                'download-documents',



                // Document Comments (limited access)
                'manage-document-comments',
                'view-document-comments',
                'create-document-comments',

                // Messages
                'manage-messages',
                'view-messages',
                'send-messages',

                // Invoices
                'manage-invoices',
                'view-invoices',

                // Payments
                'manage-payments',
                'view-payments',
                'create-payments',

                // Time Entries
                'manage-time-entries',
                'view-time-entries',

                // Knowledge Articles
                'manage-knowledge-articles',
                'view-knowledge-articles',

                // Legal Precedents
                'manage-legal-precedents',
                'view-legal-precedents'
            ]);

            // Get client types for this company
            $clientTypes = ClientType::where('created_by', $companyUser->id)->get();

            // Create default client types if none exist
            if ($clientTypes->count() === 0) {
                $defaultTypes = [
                    ['name' => 'Individual', 'description' => 'Individual clients'],
                    ['name' => 'Corporate', 'description' => 'Corporate clients']
                ];

                foreach ($defaultTypes as $typeData) {
                    ClientType::create([
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'status' => 'active',
                        'created_by' => $companyUser->id
                    ]);
                }

                $clientTypes = ClientType::where('created_by', $companyUser->id)->get();
            }

            if ($clientTypes->count() > 0) {
                // Create 3-5 clients for each company
                $clientCount = rand(3, 5);
                $clientNames = [
                    'John Smith',
                    'Sarah Johnson',
                    'Michael Brown',
                    'Emily Davis',
                    'David Wilson',
                    'Lisa Anderson',
                    'Robert Taylor',
                    'Jennifer Martinez',
                    'William Garcia',
                    'Mary Rodriguez'
                ];

                for ($i = 1; $i <= $clientCount; $i++) {
                    $clientType = $clientTypes->random();
                    $isCorporate = $clientType->name === 'Corporate';
                    $clientName = $clientNames[($companyUser->id + $i - 1) % count($clientNames)];

                    $email = strtolower(str_replace(' ', '_', $clientName)) . '_' . $companyUser->id . '@example.com';

                    $clientData = [
                        'name' => $clientName,
                        'email' => $email,
                        'phone' => '+1-555-' . str_pad($companyUser->id . $i, 4, '0', STR_PAD_LEFT),
                        'address' => ($i * 100) . ' Main St, New York, NY 1000' . $i,
                        'client_type_id' => $clientType->id,
                        'status' => ($clientName === 'Lisa Anderson' || $clientName === 'Michael Brown') ? 'active' : (rand(1, 10) > 8 ? 'inactive' : 'active'),
                        'company_name' => $isCorporate ? $clientNames[($companyUser->id + $i - 1) % count($clientNames)] . ' Corp' : null,
                        'tax_id' => $isCorporate ? 'TAX' . str_pad($companyUser->id . $i, 6, '0', STR_PAD_LEFT) : null,
                        'tax_rate' => rand(0, 15) / 100, // 0% to 15% tax rate
                        'date_of_birth' => !$isCorporate ? '198' . rand(0, 9) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT) : null,
                        'referral_source' => ['Website', 'Referral', 'Advertisement', 'Social Media', 'Word of Mouth', 'Legal Directory'][rand(0, 5)],
                        'notes' => 'Client #' . $i . ' for ' . $companyUser->name . '. ' . ($isCorporate ? 'Corporate client with business needs.' : 'Individual client seeking legal assistance.'),
                    ];

                    // Create client record
                    $client = Client::firstOrCreate([
                        'email' => $clientData['email'],
                        'created_by' => $companyUser->id
                    ], [
                        ...$clientData,
                        'created_by' => $companyUser->id,
                    ]);

                    // Create client user account
                    $clientUser = User::updateOrCreate([
                        'email' => $clientData['email']
                    ], [
                        'name' => $clientData['name'],
                        'password' => Hash::make('password'),
                        'type' => 'client',
                        'lang' => $companyUser->lang ?? 'en',
                        'status' => 'active',
                        'referral_code' => 0,
                        'created_by' => $companyUser->id
                    ]);

                    $clientUser->roles()->sync([$clientRole->id]);
                }
            }
        }
    }
}
