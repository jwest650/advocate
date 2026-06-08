<?php

namespace Database\Seeders;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LoginHistorySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('type', ['superadmin', 'company'])->get();
        $staffUsers = User::whereIn('type', ['manager', 'team_member', 'client'])->get();

        if ($users->isEmpty() && $staffUsers->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $allUsers = $users->merge($staffUsers);

        $ipAddresses = [
            '192.168.1.100', '10.0.0.50', '172.16.0.25', '203.0.113.45',
            '198.51.100.78', '127.0.0.1', '192.168.0.15', '10.1.1.200'
        ];

        $browserData = [
            ['browser_name' => 'Chrome', 'os_name' => 'Windows', 'device_type' => 'desktop', 'browser_language' => 'en'],
            ['browser_name' => 'Firefox', 'os_name' => 'Linux', 'device_type' => 'desktop', 'browser_language' => 'en'],
            ['browser_name' => 'Safari', 'os_name' => 'macOS', 'device_type' => 'desktop', 'browser_language' => 'en'],
            ['browser_name' => 'Chrome', 'os_name' => 'Android', 'device_type' => 'mobile', 'browser_language' => 'en'],
            ['browser_name' => 'Safari', 'os_name' => 'iOS', 'device_type' => 'mobile', 'browser_language' => 'en']
        ];

        $locationData = [
            [
                'country' => 'India', 'countryCode' => 'IN', 'region' => 'GJ', 'regionName' => 'Gujarat',
                'city' => 'Surat', 'zip' => '395007', 'lat' => 21.1981, 'lon' => 72.8298,
                'timezone' => 'Asia/Kolkata', 'isp' => 'Reliance Jio', 'org' => 'Reliance Jio',
                'as' => 'AS55836 Reliance Jio'
            ],
            [
                'country' => 'United States', 'countryCode' => 'US', 'region' => 'CA', 'regionName' => 'California',
                'city' => 'San Francisco', 'zip' => '94102', 'lat' => 37.7749, 'lon' => -122.4194,
                'timezone' => 'America/Los_Angeles', 'isp' => 'Comcast Cable', 'org' => 'Comcast Cable',
                'as' => 'AS7922 Comcast Cable'
            ]
        ];

        $recordsCreated = 0;

        // Create 13 superadmin login records
        $superadminUsers = User::where('type', 'superadmin')->get();
        if ($superadminUsers->isNotEmpty()) {
            for ($i = 0; $i < 5; $i++) {
                $user = $superadminUsers->random();
                $browser = $browserData[array_rand($browserData)];
                $location = $locationData[array_rand($locationData)];
                $ip = $ipAddresses[array_rand($ipAddresses)];

                $details = array_merge($browser, $location, [
                    'status' => 'success',
                    'query' => $ip,
                    'referrer_host' => fake()->randomElement(['localhost', 'example.com', null]),
                    'referrer_path' => fake()->randomElement(['/login', '/dashboard', null])
                ]);

                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip' => $ip,
                    'date' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
                    'details' => $details,
                    'type' => 'superadmin',
                    'created_by' => $user->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                    'updated_at' => Carbon::now()
                ]);

                $recordsCreated++;
            }
        }

        // Create 8 company login records
        $companyUsers = User::where('type', 'company')->get();
        if ($companyUsers->isNotEmpty()) {
            for ($i = 0; $i < 5; $i++) {
                $user = $companyUsers->random();
                $browser = $browserData[array_rand($browserData)];
                $location = $locationData[array_rand($locationData)];
                $ip = $ipAddresses[array_rand($ipAddresses)];

                $details = array_merge($browser, $location, [
                    'status' => 'success',
                    'query' => $ip,
                    'referrer_host' => fake()->randomElement(['localhost', 'example.com', null]),
                    'referrer_path' => fake()->randomElement(['/login', '/dashboard', null])
                ]);

                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip' => $ip,
                    'date' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
                    'details' => $details,
                    'type' => 'company',
                    'created_by' => $user->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                    'updated_at' => Carbon::now()
                ]);

                $recordsCreated++;
            }
        }

        // Create remaining records for other users
        $remainingUsers = $allUsers->where('type', '!=', 'superadmin');
        foreach ($remainingUsers->take(12) as $user) {
            $browser = $browserData[array_rand($browserData)];
            $location = $locationData[array_rand($locationData)];
            $ip = $ipAddresses[array_rand($ipAddresses)];

            $details = array_merge($browser, $location, [
                'status' => 'success',
                'query' => $ip,
                'referrer_host' => fake()->randomElement(['localhost', 'example.com', null]),
                'referrer_path' => fake()->randomElement(['/login', '/dashboard', null])
            ]);

            $roleType = $user->getRoleNames()->first() ?? $user->type;
            $createdBy = in_array($user->type, ['superadmin', 'company']) ? $user->id : ($user->created_by ?? $user->id);

            LoginHistory::create([
                'user_id' => $user->id,
                'ip' => $ip,
                'date' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
                'details' => $details,
                'type' => $roleType,
                'created_by' => $createdBy,
                'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                'updated_at' => Carbon::now()
            ]);

            $recordsCreated++;
        }

        $this->command->info("{$recordsCreated} login history records created (13 superadmin, 8 company) successfully.");
    }
}
