<?php

use App\Models\Setting;
use App\Models\User;
use App\Models\Coupon;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateLang;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\Role;
use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Auth;

if (!function_exists('getCacheSize')) {
    /**
     * Get the total cache size in MB
     *
     * @return string
     */
    function getCacheSize()
    {
        $file_size = 0;
        $framework_path = storage_path('framework');

        if (is_dir($framework_path)) {
            foreach (\File::allFiles($framework_path) as $file) {
                $file_size += $file->getSize();
            }
        }

        return number_format($file_size / 1000000, 2);
    }
}

if (! function_exists('settings')) {
    function settings($user_id = null)
    {
        // Skip database queries during installation
        if (request()->is('install/*') || request()->is('update/*') || !file_exists(storage_path('installed'))) {
            return [];
        }

        if (is_null($user_id)) {
            if (auth()->user()) {
                if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
                    $user_id = auth()->user()->created_by;
                } else {
                    $user_id = auth()->id();
                }
            } else {
                $user = User::where('type', 'superadmin')->first();
                $user_id = $user ? $user->id : null;
            }
        }

        if (!$user_id) {
            return [];
        }

        $userSettings = Setting::where('user_id', $user_id)->pluck('value', 'key')->toArray();

        // Add demo mode flag from config
        $userSettings['is_demo'] = config('app.is_demo', false);
        $userSettings['demo'] = config('app.is_demo', false);

        return $userSettings;
    }
}

if (! function_exists('formatDateTime')) {
    function formatDateTime($date, $includeTime = true)
    {
        if (!$date) {
            return null;
        }

        $settings = settings();

        $dateFormat = $settings['dateFormat'] ?? 'Y-m-d';
        $timeFormat = $settings['timeFormat'] ?? 'H:i';
        $timezone = $settings['defaultTimezone'] ?? config('app.timezone', 'UTC');

        $format = $includeTime ? "$dateFormat $timeFormat" : $dateFormat;

        return Carbon::parse($date)->timezone($timezone)->format($format);
    }
}

if (! function_exists('getSetting')) {
    function getSetting($key, $default = null, $user_id = null)
    {
        $settings = settings($user_id);

        // If no value found and no default provided, try to get from defaultSettings
        if (!isset($settings[$key]) && $default === null) {
            $defaultSettings = defaultSettings();
            $default = $defaultSettings[$key] ?? null;
        }

        return $settings[$key] ?? $default;
    }
}
if (! function_exists('IsDemo')) {
    function IsDemo()
    {
        if (config('app.is_demo')) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('updateSetting')) {
    function updateSetting($key, $value, $user_id = null)
    {
        if (is_null($user_id)) {
            if (auth()->user()) {
                if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
                    $user_id = auth()->user()->created_by;
                } else {
                    $user_id = auth()->id();
                }
            } else {
                $user = User::where('type', 'superadmin')->first();
                $user_id = $user ? $user->id : null;
            }
        }

        if (!$user_id) {
            return false;
        }

        return Setting::updateOrCreate(
            ['user_id' => $user_id, 'key' => $key],
            ['value' => $value]
        );
    }
}

if (! function_exists('isLandingPageEnabled')) {
    function isLandingPageEnabled()
    {
        return getSetting('landingPageEnabled', true) === true || getSetting('landingPageEnabled', true) === '1';
    }
}

if (! function_exists('defaultRoleAndSetting')) {
    function defaultRoleAndSetting($user)
    {
        $companyRole = Role::where('name', 'company')->first();

        if ($companyRole) {
            $user->assignRole($companyRole);
        }

        // Create default settings for the user
        if ($user->type === 'superadmin') {
            createDefaultSettings($user->id);
            defultnotificationAndsetting($user->id);
        } elseif ($user->type === 'company') {
            // Only create the defaultLanguage setting, not all default settings
            $creator = auth()->user();
            $defaultLanguageSetting = \App\Models\Setting::where('user_id', $creator->id)
                ->where('key', 'defaultLanguage')
                ->first();
            
            if ($defaultLanguageSetting) {
                \App\Models\Setting::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'key' => 'defaultLanguage'
                    ],
                    [
                        'value' => $defaultLanguageSetting->value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Create client and team_member roles for this company
            createCompanyRoles($user);
            defultnotificationAndsetting($user->id);
            // Create default notification templates and settings
            createDefaultNotificationTemplates($user->id);
        }

        return true;
    }
}
if (! function_exists('defultnotificationAndsetting')) {
    function defultnotificationAndsetting($user)
    {
        $templates = NotificationTemplate::all();
        // Copy language content from created_by=1 to new company
        foreach ($templates as $template) {
            $defaultLangs = NotificationTemplateLang::where('parent_id', $template->id)
                ->where('created_by', 1)
                ->get();
            foreach ($defaultLangs as $defaultLang) {
                NotificationTemplateLang::updateOrCreate(
                    [
                        'parent_id' => $template->id,
                        'lang' => $defaultLang->lang,
                        'created_by' => $user
                    ],
                    [
                        'title' => $defaultLang->title,
                        'content' => $defaultLang->content
                    ]
                );
            }
        }
    }
}

if (! function_exists('createCompanyRoles')) {
    function createCompanyRoles($companyUser)
    {
        // Create client role
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

        // Create team_member role with permissions
        $teamRole = Role::firstOrCreate([
            'name' => 'team_member',
            'guard_name' => 'web',
            'created_by' => $companyUser->id
        ], [
            'label' => 'Team Member',
            'description' => 'Team member with limited access to company modules'
        ]);

        $teamPermissions = [
            // Dashboard
            'manage-dashboard',

            // Calender
            'manage-calendar',
            'view-calendar',
            'manage-own-calendar',

            // Tasks
            'manage-tasks',
            'view-tasks',
            'create-tasks',
            'edit-tasks',
            'assign-tasks',
            'toggle-status-tasks',

            // Time Entries
            'manage-time-entries',
            'view-time-entries',
            'create-time-entries',
            'edit-time-entries',
            'start-timer',
            'stop-timer',

            // Cases
            'manage-cases',
            'view-cases',
            'create-cases',
            'edit-cases',
            'toggle-status-cases',

            // Case Documents
            'manage-case-documents',
            'view-case-documents',
            'create-case-documents',
            'edit-case-documents',
            'download-case-documents',

            // Case Notes
            'manage-case-notes',
            'view-case-notes',
            'create-case-notes',
            'edit-case-notes',

            // Case Timelines
            'manage-case-timelines',
            'view-case-timelines',
            'create-case-timelines',
            'edit-case-timelines',

            // Clients
            'manage-clients',
            'view-clients',
            'create-clients',
            'edit-clients',

            // Client Communications


            // Documents
            'manage-documents',
            'view-documents',
            'create-documents',
            'edit-documents',
            'download-documents',



            // Document Comments (limited access)
            'manage-document-comments',
            'view-document-comments',
            'create-document-comments',
            'edit-document-comments',

            // Document Permissions (view only)
            'manage-document-permissions',
            'view-document-permissions',

            // Hearings
            'manage-hearings',
            'view-hearings',
            'create-hearings',
            'edit-hearings',

            'manage-media',
            'manage-own-media',
            // Research
            'manage-research-projects',
            'view-research-projects',
            'create-research-projects',
            'edit-research-projects',
            'manage-knowledge-articles',
            'view-knowledge-articles',
            'create-knowledge-articles',
            'edit-knowledge-articles',
            'manage-legal-precedents',
            'view-legal-precedents',
            'create-legal-precedents',
            'edit-legal-precedents',

            // Messages
            'manage-messages',
            'view-messages',
            'send-messages'
        ];

        $teamRole->syncPermissions($teamPermissions);

        // Create default team member user
        $teamMember = User::firstOrCreate([
            'email' => 'teammember' . $companyUser->id . '@company.com',
            'created_by' => $companyUser->id
        ], [
            'name' => 'John Doe',
            'password' => \Hash::make('password'),
            'type' => 'team_member',
            'lang' => $companyUser->lang ?? 'en',
            'status' => 'active',
            'referral_code' => 0
        ]);

        $teamMember->roles()->sync([$teamRole->id]);

        // Create default client user
        $client = User::firstOrCreate([
            'email' => 'client' . $companyUser->id . '@company.com',
            'created_by' => $companyUser->id
        ], [
            'name' => 'Jane Smith',
            'password' => \Hash::make('password'),
            'type' => 'client',
            'lang' => $companyUser->lang ?? 'en',
            'status' => 'active',
            'referral_code' => 0
        ]);

        $client->roles()->sync([$clientRole->id]);

        // Create default client type first
        $defaultClientType = \App\Models\ClientType::firstOrCreate([
            'name' => 'Individual',
            'created_by' => $companyUser->id
        ], [
            'description' => 'Individual client type',
            'status' => 'active'
        ]);

        // Create client record in clients table
        \App\Models\Client::firstOrCreate([
            'email' => $client->email,
            'created_by' => $companyUser->id
        ], [
            'name' => $client->name,
            'phone' => '+1 234 567 890',
            'address' => '123 Client Street, City, State',
            'client_type_id' => $defaultClientType->id,
            'status' => 'active',
            'company_name' => $companyUser->name,
            'tax_id' => null,
            'tax_rate' => 0.00,
            'date_of_birth' => null,
            'notes' => 'Default client created during company setup',
            'referral_source' => 'System Generated'
        ]);
    }
}

if (! function_exists('getPaymentSettings')) {
    /**
     * Get payment settings for a user
     *
     * @param int|null $userId
     * @return array
     */
    function getPaymentSettings($userId = null)
    {
        if (is_null($userId)) {
            $userId = auth()->id();
        }

        return PaymentSetting::getUserSettings($userId);
    }
}

if (! function_exists('updatePaymentSetting')) {
    /**
     * Update or create a payment setting
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $userId
     * @return \App\Models\PaymentSetting
     */
    function updatePaymentSetting($key, $value, $userId = null)
    {
        if (is_null($userId)) {
            $userId = auth()->id();
        }

        return PaymentSetting::updateOrCreateSetting($userId, $key, $value);
    }
}

if (! function_exists('isPaymentMethodEnabled')) {
    /**
     * Check if a payment method is enabled
     *
     * @param string $method (stripe, paypal, razorpay, mercadopago, bank)
     * @param int|null $userId
     * @return bool
     */
    function isPaymentMethodEnabled($method, $userId = null)
    {
        $userId = $userId ?: getPaymentSettingsUserId();
        $settings = getPaymentSettings($userId);
        $key = "is_{$method}_enabled";

        return isset($settings[$key]) && ($settings[$key] === true || $settings[$key] === '1');
    }
}

if (! function_exists('getPaymentMethodConfig')) {
    /**
     * Get configuration for a specific payment method
     *
     * @param string $method (stripe, paypal, razorpay, mercadopago)
     * @param int|null $userId
     * @return array
     */
    function getPaymentMethodConfig($method, $userId = null)
    {
        $userId = $userId ?: getPaymentSettingsUserId();
        $settings = getPaymentSettings($userId);

        switch ($method) {
            case 'stripe':
                return [
                    'enabled' => isPaymentMethodEnabled('stripe', $userId),
                    'key' => $settings['stripe_key'] ?? null,
                    'secret' => $settings['stripe_secret'] ?? null,
                ];

            case 'paypal':
                return [
                    'enabled' => isPaymentMethodEnabled('paypal', $userId),
                    'mode' => $settings['paypal_mode'] ?? 'sandbox',
                    'client_id' => $settings['paypal_client_id'] ?? null,
                    'secret' => $settings['paypal_secret_key'] ?? null,
                ];

            case 'razorpay':
                return [
                    'enabled' => isPaymentMethodEnabled('razorpay', $userId),
                    'key' => $settings['razorpay_key'] ?? null,
                    'secret' => $settings['razorpay_secret'] ?? null,
                ];

            case 'mercadopago':
                return [
                    'enabled' => isPaymentMethodEnabled('mercadopago', $userId),
                    'mode' => $settings['mercadopago_mode'] ?? 'sandbox',
                    'access_token' => $settings['mercadopago_access_token'] ?? null,
                ];

            case 'paystack':
                return [
                    'enabled' => isPaymentMethodEnabled('paystack', $userId),
                    'public_key' => $settings['paystack_public_key'] ?? null,
                    'secret_key' => $settings['paystack_secret_key'] ?? null,
                ];

            case 'flutterwave':
                return [
                    'enabled' => isPaymentMethodEnabled('flutterwave', $userId),
                    'public_key' => $settings['flutterwave_public_key'] ?? null,
                    'secret_key' => $settings['flutterwave_secret_key'] ?? null,
                ];

            case 'bank':
                return [
                    'enabled' => isPaymentMethodEnabled('bank', $userId),
                    'details' => $settings['bank_detail'] ?? null,
                ];

            case 'paytabs':
                return [
                    'enabled' => isPaymentMethodEnabled('paytabs', $userId),
                    'mode' => $settings['paytabs_mode'] ?? 'sandbox',
                    'profile_id' => $settings['paytabs_profile_id'] ?? null,
                    'server_key' => $settings['paytabs_server_key'] ?? null,
                    'region' => $settings['paytabs_region'] ?? 'ARE',
                ];

            case 'skrill':
                return [
                    'enabled' => isPaymentMethodEnabled('skrill', $userId),
                    'merchant_id' => $settings['skrill_merchant_id'] ?? null,
                    'secret_word' => $settings['skrill_secret_word'] ?? null,
                ];

            case 'coingate':
                return [
                    'enabled' => isPaymentMethodEnabled('coingate', $userId),
                    'mode' => $settings['coingate_mode'] ?? 'sandbox',
                    'api_token' => $settings['coingate_api_token'] ?? null,
                ];

            case 'payfast':
                return [
                    'enabled' => isPaymentMethodEnabled('payfast', $userId),
                    'mode' => $settings['payfast_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['payfast_merchant_id'] ?? null,
                    'merchant_key' => $settings['payfast_merchant_key'] ?? null,
                    'passphrase' => $settings['payfast_passphrase'] ?? null,
                ];

            case 'tap':
                return [
                    'enabled' => isPaymentMethodEnabled('tap', $userId),
                    'secret_key' => $settings['tap_secret_key'] ?? null,
                ];

            case 'xendit':
                return [
                    'enabled' => isPaymentMethodEnabled('xendit', $userId),
                    'api_key' => $settings['xendit_api_key'] ?? null,
                ];

            case 'paytr':
                return [
                    'enabled' => isPaymentMethodEnabled('paytr', $userId),
                    'merchant_id' => $settings['paytr_merchant_id'] ?? null,
                    'merchant_key' => $settings['paytr_merchant_key'] ?? null,
                    'merchant_salt' => $settings['paytr_merchant_salt'] ?? null,
                ];

            case 'mollie':
                return [
                    'enabled' => isPaymentMethodEnabled('mollie', $userId),
                    'api_key' => $settings['mollie_api_key'] ?? null,
                ];

            case 'toyyibpay':
                return [
                    'enabled' => isPaymentMethodEnabled('toyyibpay', $userId),
                    'category_code' => $settings['toyyibpay_category_code'] ?? null,
                    'secret_key' => $settings['toyyibpay_secret_key'] ?? null,
                    'mode' => $settings['toyyibpay_mode'] ?? 'sandbox',
                ];

            case 'cashfree':
                return [
                    'enabled' => isPaymentMethodEnabled('cashfree', $userId),
                    'mode' => $settings['cashfree_mode'] ?? 'sandbox',
                    'public_key' => $settings['cashfree_public_key'] ?? null,
                    'secret_key' => $settings['cashfree_secret_key'] ?? null,
                ];

            case 'iyzipay':
                return [
                    'enabled' => isPaymentMethodEnabled('iyzipay', $userId),
                    'mode' => $settings['iyzipay_mode'] ?? 'sandbox',
                    'public_key' => $settings['iyzipay_public_key'] ?? null,
                    'secret_key' => $settings['iyzipay_secret_key'] ?? null,
                ];

            case 'benefit':
                return [
                    'enabled' => isPaymentMethodEnabled('benefit', $userId),
                    'mode' => $settings['benefit_mode'] ?? 'sandbox',
                    'public_key' => $settings['benefit_public_key'] ?? null,
                    'secret_key' => $settings['benefit_secret_key'] ?? null,
                ];

            case 'ozow':
                return [
                    'enabled' => isPaymentMethodEnabled('ozow', $userId),
                    'mode' => $settings['ozow_mode'] ?? 'sandbox',
                    'site_key' => $settings['ozow_site_key'] ?? null,
                    'private_key' => $settings['ozow_private_key'] ?? null,
                    'api_key' => $settings['ozow_api_key'] ?? null,
                ];

            case 'easebuzz':
                return [
                    'enabled' => isPaymentMethodEnabled('easebuzz', $userId),
                    'merchant_key' => $settings['easebuzz_merchant_key'] ?? null,
                    'salt_key' => $settings['easebuzz_salt_key'] ?? null,
                    'environment' => $settings['easebuzz_environment'] ?? 'test',
                ];

            case 'khalti':
                return [
                    'enabled' => isPaymentMethodEnabled('khalti', $userId),
                    'public_key' => $settings['khalti_public_key'] ?? null,
                    'secret_key' => $settings['khalti_secret_key'] ?? null,
                ];

            case 'authorizenet':
                return [
                    'enabled' => isPaymentMethodEnabled('authorizenet', $userId),
                    'mode' => $settings['authorizenet_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['authorizenet_merchant_id'] ?? null,
                    'transaction_key' => $settings['authorizenet_transaction_key'] ?? null,
                    'supported_countries' => ['US', 'CA', 'GB', 'AU'],
                    'supported_currencies' => ['USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD'],
                ];

            case 'fedapay':
                return [
                    'enabled' => isPaymentMethodEnabled('fedapay', $userId),
                    'mode' => $settings['fedapay_mode'] ?? 'sandbox',
                    'public_key' => $settings['fedapay_public_key'] ?? null,
                    'secret_key' => $settings['fedapay_secret_key'] ?? null,
                ];

            case 'payhere':
                return [
                    'enabled' => isPaymentMethodEnabled('payhere', $userId),
                    'mode' => $settings['payhere_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['payhere_merchant_id'] ?? null,
                    'merchant_secret' => $settings['payhere_merchant_secret'] ?? null,
                    'app_id' => $settings['payhere_app_id'] ?? null,
                    'app_secret' => $settings['payhere_app_secret'] ?? null,
                ];

            case 'cinetpay':
                return [
                    'enabled' => isPaymentMethodEnabled('cinetpay', $userId),
                    'site_id' => $settings['cinetpay_site_id'] ?? null,
                    'api_key' => $settings['cinetpay_api_key'] ?? null,
                    'secret_key' => $settings['cinetpay_secret_key'] ?? null,
                ];

            case 'midtrans':
                return [
                    'enabled' => isPaymentMethodEnabled('midtrans', $userId),
                    'mode' => $settings['midtrans_mode'] ?? 'sandbox',
                    'secret_key' => $settings['midtrans_secret_key'] ?? null,
                    'client_key' => $settings['midtrans_client_key'] ?? null,
                ];

            case 'paiement':
                return [
                    'enabled' => isPaymentMethodEnabled('paiement', $userId),
                    'merchant_id' => $settings['paiement_merchant_id'] ?? null,
                ];

            case 'paymentwall':
                return [
                    'enabled' => isPaymentMethodEnabled('paymentwall', $userId),
                    'mode' => $settings['paymentwall_mode'] ?? 'sandbox',
                    'public_key' => $settings['paymentwall_public_key'] ?? null,
                    'private_key' => $settings['paymentwall_private_key'] ?? null,
                ];

            case 'sspay':
                return [
                    'enabled' => isPaymentMethodEnabled('sspay', $userId),
                    'secret_key' => $settings['sspay_secret_key'] ?? null,
                    'category_code' => $settings['sspay_category_code'] ?? null,
                ];

            case 'yookassa':
                return [
                    'enabled' => isPaymentMethodEnabled('yookassa', $userId),
                    'shop_id' => $settings['yookassa_shop_id'] ?? null,
                    'secret_key' => $settings['yookassa_secret_key'] ?? null,
                ];

            case 'aamarpay':
                return [
                    'enabled' => isPaymentMethodEnabled('aamarpay', $userId),
                    'store_id' => $settings['aamarpay_store_id'] ?? null,
                    'signature' => $settings['aamarpay_signature'] ?? null,
                    'mode' => $settings['aamarpay_mode'] ?? 'sandbox',
                ];

            default:
                return [];
        }
    }
}

if (! function_exists('getEnabledPaymentMethods')) {
    /**
     * Get all enabled payment methods
     *
     * @param int|null $userId
     * @return array
     */
    function getEnabledPaymentMethods($userId = null)
    {
        $userId = $userId ?: getPaymentSettingsUserId();
        $methods = ['stripe', 'paypal', 'razorpay', 'mercadopago', 'paystack', 'flutterwave', 'bank', 'paytabs', 'skrill', 'coingate', 'payfast', 'tap', 'xendit', 'paytr', 'mollie', 'toyyibpay', 'cashfree', 'iyzipay', 'benefit', 'ozow', 'easebuzz', 'khalti', 'authorizenet', 'fedapay', 'payhere', 'cinetpay', 'paiement', 'paymentwall', 'sspay', 'yookassa', 'aamarpay'];
        $enabled = [];

        foreach ($methods as $method) {
            if (isPaymentMethodEnabled($method, $userId)) {
                $enabled[$method] = getPaymentMethodConfig($method, $userId);
            }
        }

        return $enabled;
    }
}

if (! function_exists('validatePaymentMethodConfig')) {
    /**
     * Validate payment method configuration
     *
     * @param string $method
     * @param array $config
     * @return array [valid => bool, errors => array]
     */
    function validatePaymentMethodConfig($method, $config)
    {
        $errors = [];

        switch ($method) {
            case 'stripe':
                if (empty($config['key'])) {
                    $errors[] = 'Stripe publishable key is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'Stripe secret key is required';
                }
                break;

            case 'paypal':
                if (empty($config['client_id'])) {
                    $errors[] = 'PayPal client ID is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'PayPal secret key is required';
                }
                break;

            case 'razorpay':
                if (empty($config['key'])) {
                    $errors[] = 'Razorpay key ID is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'Razorpay secret key is required';
                }
                break;

            case 'mercadopago':
                if (empty($config['access_token'])) {
                    $errors[] = 'MercadoPago access token is required';
                }
                break;

            case 'bank':
                if (empty($config['details'])) {
                    $errors[] = 'Bank details are required';
                }
                break;

            case 'paytabs':
                if (empty($config['server_key'])) {
                    $errors[] = 'PayTabs server key is required';
                }
                if (empty($config['profile_id'])) {
                    $errors[] = 'PayTabs profile id is required';
                }
                if (empty($config['region'])) {
                    $errors[] = 'PayTabs region is required';
                }
                break;

            case 'skrill':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Skrill merchant ID is required';
                }
                if (empty($config['secret_word'])) {
                    $errors[] = 'Skrill secret word is required';
                }
                break;

            case 'coingate':
                if (empty($config['api_token'])) {
                    $errors[] = 'CoinGate API token is required';
                }
                break;

            case 'payfast':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Payfast merchant ID is required';
                }
                if (empty($config['merchant_key'])) {
                    $errors[] = 'Payfast merchant key is required';
                }
                break;

            case 'tap':
                if (empty($config['secret_key'])) {
                    $errors[] = 'Tap secret key is required';
                }
                break;

            case 'xendit':
                if (empty($config['api_key'])) {
                    $errors[] = 'Xendit api key is required';
                }
                break;

            case 'paytr':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'PayTR merchant ID is required';
                }
                if (empty($config['merchant_key'])) {
                    $errors[] = 'PayTR merchant key is required';
                }
                if (empty($config['merchant_salt'])) {
                    $errors[] = 'PayTR merchant salt is required';
                }
                break;

            case 'mollie':
                if (empty($config['api_key'])) {
                    $errors[] = 'Mollie API key is required';
                }
                break;

            case 'toyyibpay':
                if (empty($config['category_code'])) {
                    $errors[] = 'toyyibPay category code is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'toyyibPay secret key is required';
                }
                break;

            case 'cashfree':
                if (empty($config['public_key'])) {
                    $errors[] = 'Cashfree App ID is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Cashfree Secret Key is required';
                }
                break;

            case 'iyzipay':
                if (empty($config['public_key'])) {
                    $errors[] = 'Iyzipay API key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Iyzipay secret key is required';
                }
                break;

            case 'benefit':
                if (empty($config['public_key'])) {
                    $errors[] = 'Benefit API key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Benefit secret key is required';
                }
                break;

            case 'ozow':
                if (empty($config['site_key'])) {
                    $errors[] = 'Ozow site key is required';
                }
                if (empty($config['private_key'])) {
                    $errors[] = 'Ozow private key is required';
                }
                break;

            case 'easebuzz':
                if (empty($config['merchant_key'])) {
                    $errors[] = 'Easebuzz merchant key is required';
                }
                if (empty($config['salt_key'])) {
                    $errors[] = 'Easebuzz salt key is required';
                }
                break;

            case 'khalti':
                if (empty($config['public_key'])) {
                    $errors[] = 'Khalti public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Khalti secret key is required';
                }
                break;

            case 'authorizenet':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'AuthorizeNet merchant ID is required';
                }
                if (empty($config['transaction_key'])) {
                    $errors[] = 'AuthorizeNet transaction key is required';
                }
                break;

            case 'fedapay':
                if (empty($config['public_key'])) {
                    $errors[] = 'FedaPay public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'FedaPay secret key is required';
                }
                break;

            case 'payhere':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'PayHere merchant ID is required';
                }
                if (empty($config['merchant_secret'])) {
                    $errors[] = 'PayHere merchant secret is required';
                }
                break;

            case 'cinetpay':
                if (empty($config['site_id'])) {
                    $errors[] = 'CinetPay site ID is required';
                }
                if (empty($config['api_key'])) {
                    $errors[] = 'CinetPay API key is required';
                }
                break;

            case 'paiement':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Paiement Pro merchant ID is required';
                }
                break;

            case 'nepalste':
                if (empty($config['public_key'])) {
                    $errors[] = 'Nepalste public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Nepalste secret key is required';
                }
                break;

            case 'yookassa':
                if (empty($config['shop_id'])) {
                    $errors[] = 'YooKassa shop ID is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'YooKassa secret key is required';
                }
                break;

            case 'midtrans':
                if (empty($config['secret_key'])) {
                    $errors[] = 'Midtrans secret key is required';
                }
                break;

            case 'aamarpay':
                if (empty($config['store_id'])) {
                    $errors[] = 'Aamarpay store ID is required';
                }
                if (empty($config['signature'])) {
                    $errors[] = 'Aamarpay signature is required';
                }
                break;

            case 'paymentwall':
                if (empty($config['public_key'])) {
                    $errors[] = 'PaymentWall public key is required';
                }
                if (empty($config['private_key'])) {
                    $errors[] = 'PaymentWall private key is required';
                }
                break;

            case 'sspay':
                if (empty($config['secret_key'])) {
                    $errors[] = 'SSPay secret key is required';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

if (! function_exists('calculatePlanPricing')) {
    function calculatePlanPricing($plan, $couponCode = null, $billingCycle = 'monthly')
    {
        // Use the plan's method to get correct price for billing cycle
        $originalPrice = $plan->getPriceForCycle($billingCycle);
        $discountAmount = 0;
        $finalPrice = $originalPrice;
        $couponId = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('status', 1)
                ->first();

            if ($coupon) {
                if ($coupon->type === 'percentage') {
                    $discountAmount = ($originalPrice * $coupon->discount_amount) / 100;
                } else {
                    $discountAmount = min($coupon->discount_amount, $originalPrice);
                }
                $finalPrice = max(0, $originalPrice - $discountAmount);
                $couponId = $coupon->id;
            }
        }

        return [
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'coupon_id' => $couponId
        ];
    }
}

if (! function_exists('createPlanOrder')) {
    function createPlanOrder($data)
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $billingCycle = $data['billing_cycle'] ?? 'monthly';
        $pricing = calculatePlanPricing($plan, $data['coupon_code'] ?? null, $billingCycle);

        return PlanOrder::create([
            'user_id' => $data['user_id'],
            'plan_id' => $plan->id,
            'coupon_id' => $pricing['coupon_id'],
            'billing_cycle' => $billingCycle,
            'payment_method' => $data['payment_method'],
            'coupon_code' => $data['coupon_code'] ?? null,
            'original_price' => $pricing['original_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_price' => $pricing['final_price'],
            'payment_id' => $data['payment_id'],
            'status' => $data['status'] ?? 'pending',
            'ordered_at' => now(),
        ]);
    }
}

if (! function_exists('assignPlanToUser')) {
    function assignPlanToUser($user, $plan, $billingCycle)
    {
        // Validate billing cycle
        if (!in_array($billingCycle, ['monthly', 'yearly'])) {
            throw new \InvalidArgumentException('Invalid billing cycle: ' . $billingCycle);
        }

        // Calculate expiration date based on billing cycle
        $expiresAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

        \Log::info('Assigning plan ' . $plan->id . ' to user ' . $user->id . ' with billing cycle ' . $billingCycle);

        // Update user with new plan and clear trial status
        $updated = $user->update([
            'plan_id' => $plan->id,
            'plan_expire_date' => $expiresAt,
            'plan_is_active' => 1,
            // Clear trial status when assigning paid plan
            'is_trial' => null,
            'trial_day' => 0,
            'trial_expire_date' => null,
        ]);

        \Log::info('Plan assignment result: ' . ($updated ? 'success' : 'failed'));

        return $updated;
    }
}

if (! function_exists('processPaymentSuccess')) {
    function processPaymentSuccess($data)
    {
        try {

            $plan = Plan::findOrFail($data['plan_id']);
            $user = User::findOrFail($data['user_id']);

            if (!$user) {
                throw new \Exception('User not found for payment success processing');
            }

            if (!$user->email) {
                \Log::warning('User found but email is null', [
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);
            }

            $planOrder = createPlanOrder(array_merge($data, ['status' => 'approved']));
            assignPlanToUser($user, $plan, $data['billing_cycle']);

            // Verify the plan was assigned
            $user->refresh();

            // Create referral record if user was referred
            \App\Http\Controllers\ReferralController::createReferralRecord($user);

            return $planOrder;
        } catch (\Exception $e) {
            \Log::error('processPaymentSuccess failed: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

if (! function_exists('getPaymentGatewaySettings')) {
    function getPaymentGatewaySettings($userId = null)
    {
        // If no user ID provided, try to get from current context or fallback to superadmin
        if (!$userId) {
            $userId = User::where('type', 'superadmin')->first()?->id;
        }

        return [
            'payment_settings' => PaymentSetting::getUserSettings($userId),
            'general_settings' => Setting::getUserSettings($userId),
            'user_id' => $userId
        ];
    }
}

if (! function_exists('validatePaymentRequest')) {
    function validatePaymentRequest($request, $additionalRules = [])
    {
        $baseRules = [
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'coupon_code' => 'nullable|string',
        ];

        return $request->validate(array_merge($baseRules, $additionalRules));
    }
}

if (! function_exists('handlePaymentError')) {
    function handlePaymentError($e, $method = 'payment')
    {
        return back()->withErrors(['error' => __('Paymenth processing failed: :message', ['message' => $e->getMessage()])]);
    }
}

if (! function_exists('getPaymentSettingsUserId')) {
    /**
     * Get the correct user ID for payment settings based on context
     *
     * @param int|null $invoiceCreatorId
     * @return int|null
     */
    function getPaymentSettingsUserId($invoiceCreatorId = null)
    {
        // If invoice creator ID is provided, use it
        if ($invoiceCreatorId) {
            return $invoiceCreatorId;
        }

        // If authenticated user is company or superadmin, use their ID
        if (auth()->check() && in_array(auth()->user()->type, ['superadmin', 'company'])) {
            return auth()->id();
        }

        // Return null instead of falling back to superadmin
        return null;
    }
}

if (! function_exists('getPaymentMethodConfig')) {
    /**
     * Get payment method configuration for a specific gateway
     *
     * @param string $method
     * @param int|null $userId
     * @return array
     */
    function getPaymentMethodConfig($method, $userId = null)
    {
        $userId = $userId ?: getPaymentSettingsUserId();

        $settings = \App\Models\PaymentSetting::where('user_id', $userId)
            ->pluck('value', 'key')
            ->toArray();

        // Handle specific method configurations
        if ($method === 'skrill') {
            return [
                'enabled' => ($settings["is_{$method}_enabled"] ?? '0') === '1',
                'merchant_id' => $settings['skrill_merchant_id'] ?? null,
                'secret_word' => $settings['skrill_secret_word'] ?? null,
            ];
        }

        $apiKey = null;
        if ($method === 'xendit') {
            $apiKey = $settings['xendit_api_key'] ?? $settings['xendit_secret_key'] ?? null;
        } elseif ($method === 'paystack') {
            $apiKey = $settings['paystack_secret_key'] ?? null;
        } else {
            $apiKey = $settings["{$method}_api_key"] ?? $settings["{$method}_secret_key"] ?? null;
        }

        return [
            'enabled' => ($settings["is_{$method}_enabled"] ?? '0') === '1',
            'api_key' => $apiKey,
            'api_key_exists' => !empty($apiKey),
        ];
    }
}

if (! function_exists('defaultSettings')) {
    /**
     * Get default settings for System, Brand, Storage, and Currency configurations
     *
     * @return array
     */
    function defaultSettings()
    {
        return [
            // System Settings
            'defaultLanguage' => 'en',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
            'calendarStartDay' => 'sunday',
            'defaultTimezone' => 'UTC',
            'emailVerification' => false,
            'landingPageEnabled' => true,

            // Brand Settings
            'logoDark' => '/images/logos/logo-dark.png',
            'logoLight' => '/images/logos/logo-light.png',
            'favicon' => '/images/logos/favicon.png',
            'titleText' => 'Advocate',
            'footerText' => '© 2026 Advocate. All rights reserved.',
            'themeColor' => 'green',
            'customColor' => '#10b77f',
            'sidebarVariant' => 'inset',
            'sidebarStyle' => 'plain',
            'layoutDirection' => 'left',
            'themeMode' => 'light',

            // Storage Settings
            'storage_type' => 'local',
            'storage_file_types' => 'jpg,png,webp,gif,pdf,doc,docx,txt,csv',
            'storage_max_upload_size' => '2048',
            'aws_access_key_id' => '',
            'aws_secret_access_key' => '',
            'aws_default_region' => 'us-east-1',
            'aws_bucket' => '',
            'aws_url' => '',
            'aws_endpoint' => '',
            'wasabi_access_key' => '',
            'wasabi_secret_key' => '',
            'wasabi_region' => 'us-east-1',
            'wasabi_bucket' => '',
            'wasabi_url' => '',
            'wasabi_root' => '',

            // Currency Settings
            'decimalFormat' => '2',
            'defaultCurrency' => 'USD',
            'decimalSeparator' => '.',
            'thousandsSeparator' => ',',
            'floatNumber' => true,
            'currencySymbolSpace' => false,
            'currencySymbolPosition' => 'before',

            // Slack Settings
            'slack_enabled' => false,
            'slack_webhook_url' => '',

            // ReCaptcha Settings
            'recaptchaEnabled' => false,
            'recaptchaVersion' => 'v2',
            'recaptchaSiteKey' => '',
            'recaptchaSecretKey' => '',

            // Cookie Settings
            'enableLogging' => false,
            'strictlyNecessaryCookies' => true,
            'cookieTitle' => 'Cookie Consent',
            'strictlyCookieTitle' => 'Strictly Necessary Cookies',
            'cookieDescription' => 'We use cookies to enhance your browsing experience and provide personalized content.',
            'strictlyCookieDescription' => 'These cookies are essential for the website to function properly.',
            'contactUsDescription' => 'If you have any questions about our cookie policy, please contact us.',
            'contactUsUrl' => 'https://example.com/contact',
        ];
    }
}

if (! function_exists('createDefaultSettings')) {
    /**
     * Create default settings for a user
     *
     * @param int $userId
     * @return void
     */
    function createDefaultSettings($userId)
    {
        $defaults = defaultSettings();
        $settingsData = [];

        foreach ($defaults as $key => $value) {
            $settingsData[] = [
                'user_id' => $userId,
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string)$value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Setting::insertOrIgnore($settingsData);
    }
}

if (! function_exists('copySettingsFromSuperAdmin')) {
    /**
     * Copy system and brand settings from superadmin to company user
     *
     * @param int $companyUserId
     * @return void
     */
    function copySettingsFromSuperAdmin($companyUserId)
    {
        $superAdmin = User::where('type', 'superadmin')->first();
        if (!$superAdmin) {
            createDefaultSettings($companyUserId);
            return;
        }

        // Settings to copy from superadmin (system settings only, not theme settings)
        $settingsToCopy = [
            'defaultLanguage',
            'dateFormat',
            'timeFormat',
            'calendarStartDay',
            'defaultTimezone',
            'emailVerification',
            'landingPageEnabled',
            'logoDark',
            'logoLight',
            'favicon',
            'titleText',
            'footerText'
        ];

        $superAdminSettings = Setting::where('user_id', $superAdmin->id)
            ->whereIn('key', $settingsToCopy)
            ->get();

        $settingsData = [];

        // Only copy existing superadmin settings
        foreach ($superAdminSettings as $setting) {
            $settingsData[] = [
                'user_id' => $companyUserId,
                'key' => $setting->key,
                'value' => $setting->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Setting::insertOrIgnore($settingsData);
    }
}

if (! function_exists('formatCurrencyForPlansAndReferrals')) {
    /**
     * Format currency using super admin settings for plans and referrals
     *
     * @param float $amount
     * @return string
     */
    function formatCurrencyForPlansAndReferrals($amount)
    {
        $superAdmin = User::where('type', 'superadmin')->first();
        if (!$superAdmin) {
            return '$' . number_format($amount, 2);
        }

        $superAdminSettings = settings($superAdmin->id);
        $currencyCode = $superAdminSettings['defaultCurrency'] ?? 'USD';

        // Get currency symbol from database
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();
        $symbol = $currency ? $currency->symbol : '$';

        $decimalPlaces = (int)($superAdminSettings['decimalFormat'] ?? 2);
        $thousandsSeparator = $superAdminSettings['thousandsSeparator'] ?? ',';
        $symbolSpace = ($superAdminSettings['currencySymbolSpace'] ?? false) === '1';
        $symbolPosition = $superAdminSettings['currencySymbolPosition'] ?? 'before';

        $formattedAmount = number_format($amount, $decimalPlaces, '.', $thousandsSeparator);
        $space = $symbolSpace ? ' ' : '';

        return $symbolPosition === 'after' ? $formattedAmount . $space . $symbol : $symbol . $space . $formattedAmount;
    }
}

if (! function_exists('formatCurrencyForCompany')) {
    /**
     * Format currency using company settings
     *
     * @param float $amount
     * @return string
     */
    function formatCurrencyForCompany($amount)
    {
        if (!auth()->check()) {
            return '$' . number_format($amount, 2);
        }

        $userId = auth()->user()->type === 'company' ? auth()->id() : auth()->user()->created_by;

        // Get currency from company_settings table
        $companySetting = \App\Models\CompanySetting::where('created_by', $userId)
            ->where('setting_key', 'currency')
            ->first();
        $currencyCode = $companySetting ? $companySetting->setting_value : 'USD';

        // Get currency symbol from database
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();
        $symbol = $currency ? $currency->symbol : '$';

        // Get other formatting settings
        $userSettings = settings($userId);
        $decimalPlaces = (int)($userSettings['decimalFormat'] ?? 2);
        $thousandsSeparator = $userSettings['thousandsSeparator'] ?? ',';
        $symbolSpace = ($userSettings['currencySymbolSpace'] ?? false) === '1';
        $symbolPosition = $userSettings['currencySymbolPosition'] ?? 'before';

        $formattedAmount = number_format($amount, $decimalPlaces, '.', $thousandsSeparator);
        $space = $symbolSpace ? ' ' : '';

        return $symbolPosition === 'after' ? $formattedAmount . $space . $symbol : $symbol . $space . $formattedAmount;
    }
}

if (! function_exists('createdBy')) {
    function createdBy()
    {
        if (Auth::user()->type == 'superadmin') {
            return Auth::user()->id;
        } else if (Auth::user()->type == 'company') {
            return Auth::user()->id;
        } else {
            return Auth::user()->created_by;
        }
    }
}
if (! function_exists('getCompanyOwnerId')) {
    /**
     * Get the company owner ID for notifications
     * Always returns the company owner ID regardless of who is performing the action
     *
     * @return int
     */
    function getCompanyOwnerId()
    {
        $user = Auth::user();

        if ($user->type === 'superadmin') {
            return $user->id;
        } elseif ($user->type === 'company') {
            return $user->id;
        } else {
            // For employees, advocates, staff - return the company owner ID
            return $user->created_by;
        }
    }
}

if (! function_exists('createDefaultNotificationTemplates')) {
    /**
     * Create default notification templates for a new company
     *
     * @param int $companyId
     * @return void
     */
    function createDefaultNotificationTemplates($companyId)
    {
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        $langCodes = collect($languages)->pluck('code')->toArray();

        $templates = \App\Models\NotificationTemplate::all();

        foreach ($templates as $template) {
            foreach ($langCodes as $langCode) {
                $existingContent = \App\Models\NotificationTemplateLang::where('parent_id', $template->id)
                    ->where('lang', $langCode)
                    ->where('created_by', $companyId)
                    ->first();

                if ($existingContent) {
                    continue;
                }

                $globalContent = \App\Models\NotificationTemplateLang::where('parent_id', $template->id)
                    ->where('lang', $langCode)
                    ->where('created_by', 1)
                    ->first();

                if ($globalContent) {
                    \App\Models\NotificationTemplateLang::create([
                        'parent_id' => $template->id,
                        'lang' => $langCode,
                        'title' => $globalContent->title,
                        'content' => $globalContent->content,
                        'created_by' => $companyId
                    ]);
                }
            }
        }

        // Also create default notification settings
        createDefaultNotificationSettings($companyId);
    }
}
if (! function_exists('isEmailTemplateEnabled')) {
    /**
     * Check if an email template is enabled for a user
     *
     * @param string $templateName
     * @param int|null $userId
     * @return bool
     */
    function isEmailTemplateEnabled($templateName, $userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        $template = \App\Models\EmailTemplate::where('name', $templateName)->first();
        if (!$template) {
            return false;
        }

        $userTemplate = \App\Models\UserEmailTemplate::where('user_id', $userId)
            ->where('template_id', $template->id)
            ->first();

        return $userTemplate ? $userTemplate->is_active : false;
    }
}

if (!function_exists('parseBrowserData')) {
    function parseBrowserData(string $userAgent): array
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $deviceType = 'desktop';

        // Browser detection
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent)) {
            $browser = 'Edge';
        }

        // OS detection
        if (preg_match('/Windows NT/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
            $deviceType = 'mobile';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
            $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        return [
            'browser_name' => $browser,
            'os_name' => $os,
            'browser_language' => 'en',
            'device_type' => $deviceType,
        ];
    }
}

if (! function_exists('getCompanyAndUsersId')) {
    function getCompanyAndUsersId()
    {
        $user = Auth::user();
        if ($user->hasRole(['company'])) {
            $companyUserIds = User::where('created_by', $user->id)->pluck('id')->toArray();
            $companyUserIds[] = $user->id;
            return $companyUserIds;
        } else {
            $userCreatedBy = User::where('id', Auth::user()->created_by)->value('id');
            $companyUserIds = User::where('created_by', $userCreatedBy)->pluck('id')->toArray();
            $companyUserIds[] = $userCreatedBy;
            return $companyUserIds;
        }
    }
}

if (! function_exists('getTwilioConfig')) {
    function getTwilioConfig()
    {
        return [
            'twilio_sid' => getSetting('twilio_sid', ''),
            'twilio_token' => getSetting('twilio_token', ''),
            'twilio_from' => getSetting('twilio_from', '')
        ];
    }
}

if (! function_exists('isTwilioEnabled')) {
    /**
     * Check if Twilio is enabled for a user
     *
     * @param int|null $userId
     * @return bool
     */
    function isTwilioEnabled($userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        return getSetting('twilio_enabled', false, $userId) === '1';
    }
}

if (! function_exists('isNotificationTemplateEnabled')) {
    /**
     * Check if a notification template is enabled for a user and specific type
     *
     * @param string $templateName
     * @param int|null $userId
     * @param string|null $type (slack, twilio, email)
     * @return bool
     */
    function isNotificationTemplateEnabled($templateName, $userId = null, $type = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        $templateQuery = \App\Models\NotificationTemplate::where('name', $templateName);

        // If type is specified, filter by type in notification template
        if ($type) {
            $templateQuery->where('type', $type);
        }

        $template = $templateQuery->first();
        if (!$template) {
            return false;
        }

        $query = \App\Models\UserNotificationTemplate::where('user_id', $userId)
            ->where('template_id', $template->id);

        // If type is specified, also filter by type in user template
        if ($type) {
            $query->where('type', $type);
        }

        $userTemplate = $query->first();

        return $userTemplate ? (bool) $userTemplate->is_active : false;
    }
}

if (! function_exists('isNotificationTypeEnabled')) {
    /**
     * Check if a specific notification type (twilio/slack) is enabled for a template and user
     *
     * @param string $templateName
     * @param string $type (twilio, slack, email)
     * @param int|null $userId
     * @return bool
     */
    function isNotificationTypeEnabled($templateName, $type, $userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        return \App\Models\UserNotificationTemplate::isNotificationActive($templateName, $userId, $type);
    }
}

if (! function_exists('setNotificationTypeStatus')) {
    /**
     * Set notification type status for a template and user
     *
     * @param string $templateName
     * @param string $type (twilio, slack, email)
     * @param bool $isActive
     * @param int|null $userId
     * @return bool
     */
    function setNotificationTypeStatus($templateName, $type, $isActive, $userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        return \App\Models\UserNotificationTemplate::setNotificationStatus($templateName, $userId, $type, $isActive);
    }
}

if (! function_exists('createDefaultNotificationSettings')) {
    /**
     * Create default notification settings for a new company
     *
     * @param int $companyId
     * @return void
     */
    function createDefaultNotificationSettings($companyId)
    {
        $templates = \App\Models\NotificationTemplate::all();
        $types = ['email', 'twilio', 'slack'];

        foreach ($templates as $template) {
            foreach ($types as $type) {
                \App\Models\UserNotificationTemplate::updateOrCreate(
                    [
                        'user_id' => $companyId,
                        'template_id' => $template->id,
                        'type' => $type
                    ],
                    ['is_active' => false] // Default to disabled
                );
            }
        }
    }
}

if (! function_exists('syncNotificationTemplatesForAllCompanies')) {
    /**
     * Sync notification templates for all existing companies
     *
     * @return void
     */
    function syncNotificationTemplatesForAllCompanies()
    {
        $companies = \App\Models\User::where('type', 'company')->get();

        foreach ($companies as $company) {
            createDefaultNotificationTemplates($company->id);
        }
    }
}

if (! function_exists('getDemoCalendarEvents')) {
    /**
     * Get static demo calendar events for December 2025 to December 2026
     * Each month contains exactly 5 events with realistic spacing
     *
     * @return array
     */
    function getDemoCalendarEvents()
    {
        $events = [];
        $eventId = 1;

        // Categorized event templates by type
        $hearingEvents = [
            ['title' => 'Client Consultation',     'color' => '#3b82f6'],
            ['title' => 'Court Hearing',           'color' => '#ef4444'],
            ['title' => 'Deposition',              'color' => '#8b5cf6'],
            ['title' => 'Mediation Session',       'color' => '#84cc16'],
            ['title' => 'Settlement Conference',   'color' => '#ec4899'],
            ['title' => 'Client Interview',       'color' => '#059669'],
            ['title' => 'Arbitration Hearing',    'color' => '#0891b2'],
            ['title' => 'Plea Bargain Meeting',   'color' => '#be185d']
        ];

        $taskEvents = [
            ['title' => 'Document Review',         'color' => '#f59e0b'],
            ['title' => 'Case Filing',             'color' => '#06b6d4'],
            ['title' => 'Expert Witness Prep',    'color' => '#6366f1'],
            ['title' => 'Legal Research',          'color' => '#7c3aed'],
            ['title' => 'Motion Filing',          'color' => '#ea580c'],
            ['title' => 'Evidence Review',        'color' => '#c2410c'],
            ['title' => 'Compliance Audit',       'color' => '#374151']
        ];

        $timelineEvents = [
            ['title' => 'Strategy Meeting',        'color' => '#10b77f'],
            ['title' => 'Discovery Meeting',       'color' => '#f97316'],
            ['title' => 'Trial Preparation',      'color' => '#dc2626'],
            ['title' => 'Contract Negotiation',   'color' => '#65a30d'],
            ['title' => 'Appeal Preparation',     'color' => '#7c2d12']
        ];

        // Month configuration with event distribution logic
        $monthConfig = [
            '2025-12' => ['days' => [3, 7, 12, 18, 25], 'distribution' => [2, 2, 1]], // 2 hearings, 2 tasks, 1 timeline
            '2026-01' => ['days' => [5, 10, 15, 22],    'distribution' => [2, 1, 1]], // 2 hearings, 1 task, 1 timeline
            '2026-02' => ['days' => [4, 9, 14, 20, 26], 'distribution' => [3, 1, 1]], // 3 hearings, 1 task, 1 timeline
            '2026-03' => ['days' => [6, 11, 17, 23],    'distribution' => [1, 2, 1]], // 1 hearing, 2 tasks, 1 timeline
            '2026-04' => ['days' => [2, 8, 13, 19, 27], 'distribution' => [2, 2, 1]], // 2 hearings, 2 tasks, 1 timeline
            '2026-05' => ['days' => [1, 7, 12, 18],     'distribution' => [2, 1, 1]], // 2 hearings, 1 task, 1 timeline
            '2026-06' => ['days' => [3, 9, 15, 21, 28], 'distribution' => [2, 2, 1]], // 2 hearings, 2 tasks, 1 timeline
            '2026-07' => ['days' => [4, 10, 16, 22],    'distribution' => [1, 2, 1]], // 1 hearing, 2 tasks, 1 timeline
            '2026-08' => ['days' => [5, 11, 17, 23, 29], 'distribution' => [3, 1, 1]], // 3 hearings, 1 task, 1 timeline
            '2026-09' => ['days' => [2, 8, 14, 20],     'distribution' => [2, 1, 1]], // 2 hearings, 1 task, 1 timeline
            '2026-10' => ['days' => [6, 12, 18, 24, 30], 'distribution' => [2, 2, 1]], // 2 hearings, 2 tasks, 1 timeline
            '2026-11' => ['days' => [3, 9, 15, 21],     'distribution' => [1, 2, 1]], // 1 hearing, 2 tasks, 1 timeline
            '2026-12' => ['days' => [4, 10, 16, 22, 28], 'distribution' => [2, 2, 1]]  // 2 hearings, 2 tasks, 1 timeline
        ];

        $times = ['09:00', '10:30', '14:00', '15:30', '16:00'];

        foreach ($monthConfig as $month => $config) {
            $currentDate = Carbon::createFromFormat('Y-m', $month);
            $daysInMonth = $currentDate->daysInMonth;
            $days = $config['days'];
            $distribution = $config['distribution']; // [hearings, tasks, timelines]

            // Create event pool for this month with no duplicates
            $monthEvents = [];
            $usedHearings = [];
            $usedTasks = [];
            $usedTimelines = [];

            // Add unique hearings
            for ($i = 0; $i < $distribution[0]; $i++) {
                do {
                    $randomIndex = array_rand($hearingEvents);
                } while (in_array($randomIndex, $usedHearings));
                $usedHearings[] = $randomIndex;
                $monthEvents[] = array_merge($hearingEvents[$randomIndex], ['type' => 'hearing']);
            }

            // Add unique tasks
            for ($i = 0; $i < $distribution[1]; $i++) {
                do {
                    $randomIndex = array_rand($taskEvents);
                } while (in_array($randomIndex, $usedTasks));
                $usedTasks[] = $randomIndex;
                $monthEvents[] = array_merge($taskEvents[$randomIndex], ['type' => 'task']);
            }

            // Add unique timelines
            for ($i = 0; $i < $distribution[2]; $i++) {
                do {
                    $randomIndex = array_rand($timelineEvents);
                } while (in_array($randomIndex, $usedTimelines));
                $usedTimelines[] = $randomIndex;
                $monthEvents[] = array_merge($timelineEvents[$randomIndex], ['type' => 'timeline']);
            }

            // Shuffle events for random distribution
            shuffle($monthEvents);

            foreach ($days as $index => $day) {
                if (!isset($monthEvents[$index])) continue;

                $eventDate = $currentDate->copy()->day(min($day, $daysInMonth));
                $template = $monthEvents[$index];

                // Generate type-specific attributes
                $typeAttributes = [];
                switch ($template['type']) {
                    case 'hearing':
                        $typeAttributes = [
                            'court_name' => 'Superior Court of ' . ['California', 'New York', 'Texas', 'Florida'][array_rand(['California', 'New York', 'Texas', 'Florida'])],
                            'judge_name' => 'Hon. ' . ['Smith', 'Johnson', 'Williams', 'Brown', 'Davis'][array_rand(['Smith', 'Johnson', 'Williams', 'Brown', 'Davis'])],
                            'duration' => [60, 90, 120][array_rand([60, 90, 120])],
                            'location' => 'Courtroom ' . ($index + 1)
                        ];
                        break;
                    case 'task':
                        $typeAttributes = [
                            'court_name' => null,
                            'judge_name' => null,
                            'duration' => [30, 45, 60][array_rand([30, 45, 60])],
                            'location' => 'Office'
                        ];
                        break;
                    case 'timeline':
                        $typeAttributes = [
                            'court_name' => null,
                            'judge_name' => null,
                            'duration' => [90, 120, 180][array_rand([90, 120, 180])],
                            'location' => 'Conference Room ' . ($index + 1)
                        ];
                        break;
                }

                $events[] = [
                    'id' => $eventId++,
                    'title' => $template['title'],
                    'type' => $template['type'],
                    'start_date' => $eventDate->format('Y-m-d'),
                    'end_date' => $eventDate->format('Y-m-d'),
                    'date' => $eventDate->format('Y-m-d'),
                    'time' => $times[$index % count($times)],
                    'description' => 'Demo ' . $template['type'] . ' event for calendar preview',
                    'color' => $template['color'],
                    'status' => $eventDate->isPast() ? 'completed' : 'scheduled',
                    'case_title' => 'Demo Case #' . str_pad($eventId, 3, '0', STR_PAD_LEFT),
                    'client_name' => ['John Doe', 'Jane Smith', 'ABC Corp', 'XYZ Ltd'][array_rand(['John Doe', 'Jane Smith', 'ABC Corp', 'XYZ Ltd'])],
                    'court_name' => $typeAttributes['court_name'],
                    'judge_name' => $typeAttributes['judge_name'],
                    'priority' => ['low', 'medium', 'high'][array_rand(['low', 'medium', 'high'])],
                    'duration' => $typeAttributes['duration'],
                    'google_synced' => false,
                    'details' => [
                        'location' => $typeAttributes['location'],
                        'notes' => 'This ' . $template['type'] . ' event is generated for demo mode'
                    ]
                ];
            }
        }

        return $events;
    }
}

if (! function_exists('getDemoUpcomingHearings')) {
    /**
     * Get static demo data for upcoming hearings
     *
     * @return array
     */
    function getDemoUpcomingHearings()
    {
        return [
            [
                'id' => 1,
                'case_title' => 'Smith vs. Johnson Construction',
                'hearing_type' => 'Motion Hearing',
                'date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'time' => '09:30',
                'court_name' => 'Superior Court of California',
                'judge_name' => 'Hon. Margaret Davis',
                'courtroom' => 'Courtroom 4A',
                'client_name' => 'Robert Smith',
                'status' => 'scheduled',
                'priority' => 'high',
                'case_number' => 'CV-2024-001234'
            ],
            [
                'id' => 2,
                'case_title' => 'Estate of Williams',
                'hearing_type' => 'Probate Hearing',
                'date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'time' => '14:00',
                'court_name' => 'Probate Court',
                'judge_name' => 'Hon. James Rodriguez',
                'courtroom' => 'Courtroom 2B',
                'client_name' => 'Sarah Williams',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'PR-2024-005678'
            ],
            [
                'id' => 3,
                'case_title' => 'Tech Corp vs. Innovation Ltd',
                'hearing_type' => 'Summary Judgment',
                'date' => Carbon::now()->addDays(12)->format('Y-m-d'),
                'time' => '10:00',
                'court_name' => 'Federal District Court',
                'judge_name' => 'Hon. Patricia Chen',
                'courtroom' => 'Courtroom 1',
                'client_name' => 'Tech Corp',
                'status' => 'scheduled',
                'priority' => 'high',
                'case_number' => 'CV-2024-009876'
            ],
            [
                'id' => 4,
                'case_title' => 'Martinez Family Custody',
                'hearing_type' => 'Custody Hearing',
                'date' => Carbon::now()->addDays(18)->format('Y-m-d'),
                'time' => '11:30',
                'court_name' => 'Family Court',
                'judge_name' => 'Hon. Michael Thompson',
                'courtroom' => 'Courtroom 3C',
                'client_name' => 'Maria Martinez',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'FL-2024-002468'
            ],
            [
                'id' => 5,
                'case_title' => 'Green Energy Settlement',
                'hearing_type' => 'Settlement Conference',
                'date' => Carbon::now()->addDays(25)->format('Y-m-d'),
                'time' => '15:30',
                'court_name' => 'Superior Court',
                'judge_name' => 'Hon. Lisa Anderson',
                'courtroom' => 'Conference Room A',
                'client_name' => 'Green Energy Solutions',
                'status' => 'scheduled',
                'priority' => 'low',
                'case_number' => 'CV-2024-013579'
            ],
            [
                'id' => 6,
                'case_title' => 'Brown vs. City Council',
                'hearing_type' => 'Pretrial Conference',
                'date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'time' => '10:30',
                'court_name' => 'Municipal Court',
                'judge_name' => 'Hon. Steven Moore',
                'courtroom' => 'Courtroom 5',
                'client_name' => 'Daniel Brown',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'MC-2024-000912'
            ],
            [
                'id' => 7,
                'case_title' => 'Anderson Divorce Proceedings',
                'hearing_type' => 'Divorce Hearing',
                'date' => Carbon::now()->addDays(35)->format('Y-m-d'),
                'time' => '13:00',
                'court_name' => 'Family Court',
                'judge_name' => 'Hon. Rebecca Hall',
                'courtroom' => 'Courtroom 1A',
                'client_name' => 'Emily Anderson',
                'status' => 'scheduled',
                'priority' => 'high',
                'case_number' => 'FL-2024-004321'
            ],
            [
                'id' => 8,
                'case_title' => 'Global Imports Tax Appeal',
                'hearing_type' => 'Tax Appeal',
                'date' => Carbon::now()->addDays(40)->format('Y-m-d'),
                'time' => '09:00',
                'court_name' => 'Tax Tribunal',
                'judge_name' => 'Hon. Alan Peterson',
                'courtroom' => 'Tribunal Room 2',
                'client_name' => 'Global Imports Ltd',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'TX-2024-007654'
            ],
            [
                'id' => 9,
                'case_title' => 'Healthcare Group Arbitration',
                'hearing_type' => 'Arbitration Hearing',
                'date' => Carbon::now()->addDays(45)->format('Y-m-d'),
                'time' => '16:00',
                'court_name' => 'Arbitration Center',
                'judge_name' => 'Hon. Laura Bennett',
                'courtroom' => 'Room 6',
                'client_name' => 'Healthcare Group Inc.',
                'status' => 'scheduled',
                'priority' => 'low',
                'case_number' => 'AR-2024-002987'
            ],
            [
                'id' => 10,
                'case_title' => 'State vs. Reynolds',
                'hearing_type' => 'Criminal Hearing',
                'date' => Carbon::now()->addDays(50)->format('Y-m-d'),
                'time' => '11:00',
                'court_name' => 'Criminal Court',
                'judge_name' => 'Hon. Anthony White',
                'courtroom' => 'Courtroom 7D',
                'client_name' => 'Mark Reynolds',
                'status' => 'scheduled',
                'priority' => 'high',
                'case_number' => 'CR-2024-006543'
            ],
            [
                'id' => 11,
                'case_title' => 'Ocean Freight Dispute',
                'hearing_type' => 'Commercial Hearing',
                'date' => Carbon::now()->addDays(55)->format('Y-m-d'),
                'time' => '14:30',
                'court_name' => 'Commercial Court',
                'judge_name' => 'Hon. Victor Alvarez',
                'courtroom' => 'Courtroom 9',
                'client_name' => 'Ocean Freight Ltd',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'CC-2024-008741'
            ],
            [
                'id' => 12,
                'case_title' => 'Riverfront Property Dispute',
                'hearing_type' => 'Land Dispute',
                'date' => Carbon::now()->addDays(60)->format('Y-m-d'),
                'time' => '10:00',
                'court_name' => 'Civil Court',
                'judge_name' => 'Hon. Samuel Green',
                'courtroom' => 'Courtroom 3',
                'client_name' => 'Riverfront Holdings',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'CV-2024-009321'
            ],
            [
                'id' => 13,
                'case_title' => 'Union Wage Negotiation',
                'hearing_type' => 'Labor Hearing',
                'date' => Carbon::now()->addDays(65)->format('Y-m-d'),
                'time' => '15:00',
                'court_name' => 'Labor Court',
                'judge_name' => 'Hon. Denise Carter',
                'courtroom' => 'Courtroom 2',
                'client_name' => 'Workers Union',
                'status' => 'scheduled',
                'priority' => 'high',
                'case_number' => 'LB-2024-001876'
            ],
            [
                'id' => 14,
                'case_title' => 'Startup IP Protection',
                'hearing_type' => 'Intellectual Property Hearing',
                'date' => Carbon::now()->addDays(70)->format('Y-m-d'),
                'time' => '09:45',
                'court_name' => 'IP Court',
                'judge_name' => 'Hon. Olivia King',
                'courtroom' => 'Courtroom 5',
                'client_name' => 'InnovateX Startup',
                'status' => 'scheduled',
                'priority' => 'medium',
                'case_number' => 'IP-2024-003210'
            ],
            [
                'id' => 15,
                'case_title' => 'Insurance Claim Appeal',
                'hearing_type' => 'Appeal Hearing',
                'date' => Carbon::now()->addDays(75)->format('Y-m-d'),
                'time' => '13:30',
                'court_name' => 'Appeals Court',
                'judge_name' => 'Hon. Robert Lewis',
                'courtroom' => 'Courtroom 8',
                'client_name' => 'Thomas Miller',
                'status' => 'scheduled',
                'priority' => 'low',
                'case_number' => 'AP-2024-004567'
            ],
        ];
    }
}

if (! function_exists('isSlackEnabled')) {
    /**
     * Check if Slack is enabled for a user
     *
     * @param int|null $userId
     * @return bool
     */
    function isSlackEnabled($userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        return getSetting('slack_enabled', false, $userId) === '1';
    }
}

if (! function_exists('getSlackWebhookUrl')) {
    /**
     * Get Slack webhook URL for a user
     *
     * @param int|null $userId
     * @return string
     */
    function getSlackWebhookUrl($userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        return getSetting('slack_webhook_url', '', $userId);
    }
}

if (! function_exists('updateSlackSetting')) {
    /**
     * Update or create a Slack setting
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $userId
     * @return \App\Models\Setting
     */
    function updateSlackSetting($key, $value, $userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        return updateSetting($key, $value, $userId);
    }
}

if (! function_exists('isNotEditableRoles')) {
    /**
     * Get list of roles that cannot be edited
     *
     * @return array
     */
    function isNotEditableRoles()
    {
        return ['team_member'];
    }
}

if (! function_exists('isNotDeletableRoles')) {
    /**
     * Get list of roles that cannot be deleted
     *
     * @return array
     */
    function isNotDeletableRoles()
    {
        return ['client', 'team_member','team-member'];
    }
}