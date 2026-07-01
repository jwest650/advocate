<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isDemo = config('app.is_demo', true);

        // Create Super Admin User
        
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'type' => 'superadmin',
                'lang' => 'en'
            ]
        );

        // Assign super admin role
        $superAdmin->assignRole('superadmin');

        // Create default settings for superadmin if not exists
        if (!Setting::where('user_id', $superAdmin->id)->exists()) {
            createDefaultSettings($superAdmin->id);
        }

        // Get default plan
        $defaultPlan = Plan::where('is_default', true)->first();

        // Create Company User
        $company = User::firstOrCreate(
            ['email' => 'company@example.com'],
            [
                'name' => 'Company',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'type' => 'company',
                'lang' => 'en',
                'plan_id' => $defaultPlan ? $defaultPlan->id : null,
                'referral_code' => rand(100000, 999999),
                'created_by' => $superAdmin->id,
            ]
        );

        // Assign company role
        $company->assignRole('company');

        // Create default settings for company user if not exists
        if (!Setting::where('user_id', $company->id)->exists()) {
            copySettingsFromSuperAdmin($company->id);
        }

        // Get all available plans
        $freePlan = Plan::where('name', 'Free')->first();
        $starterPlan = Plan::where('name', 'Starter')->first();
        $professionalPlan = Plan::where('name', 'Professional')->first();
        $enterprisePlan = Plan::where('name', 'Enterprise')->first();

        if ($isDemo) {

            $companies = [
                [
                    'name' => 'Smith & Associates Law Firm',
                    'email' => 'admin@smithlaw.com',
                    'plan' => $starterPlan
                ],
                [
                    'name' => 'Johnson Legal Group',
                    'email' => 'contact@johnsonlegal.com',
                    'plan' => $professionalPlan
                ],
                [
                    'name' => 'Williams Corporate Law',
                    'email' => 'info@williamscorp.com',
                    'plan' => $enterprisePlan
                ],
                [
                    'name' => 'Davis Family Law',
                    'email' => 'office@davisfamily.com',
                    'plan' => $freePlan
                ],
                [
                    'name' => 'Miller Criminal Defense',
                    'email' => 'defense@millerlaw.com',
                    'plan' => $professionalPlan
                ],
                [
                    'name' => 'Brown Immigration Services',
                    'email' => 'help@brownimmigration.com',
                    'plan' => $starterPlan
                ]
            ];
        } else {
            // Main/Production mode - create only 1 company (already created above)
            $companies = [];
        }

        foreach ($companies as $companyData) {
            $newCompany = User::firstOrCreate(
                ['email' => $companyData['email']],
                [
                    'name' => $companyData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'type' => 'company',
                    'lang' => 'en',
                    'plan_id' => $companyData['plan'] ? $companyData['plan']->id : null,
                    'referral_code' => rand(100000, 999999),
                    'created_by' => $superAdmin->id,
                ]
            );

            $newCompany->assignRole('company');

            if (!Setting::where('user_id', $newCompany->id)->exists()) {
                copySettingsFromSuperAdmin($newCompany->id);
            }
        }

        // Assign default plan to all company users with null plan_id
        if ($defaultPlan) {
            User::where('type', 'company')
                ->whereNull('plan_id')
                ->update(['plan_id' => $defaultPlan->id]);
        }
    }
}
