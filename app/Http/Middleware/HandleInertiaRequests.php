<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use App\Models\Currency;
use App\Models\User;
use App\Models\Setting;
use App\Services\StorageConfigService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Set application locale and share with view
        $userLanguage = $this->getUserLanguage($request);
        \App::setLocale($userLanguage);
        \View::share('userLanguage', $userLanguage);

        // Skip database queries during installation
        if ($request->is('install/*') || $request->is('update/*') || !file_exists(storage_path('installed'))) {
            // Get available languages even during installation
            $languagesFile = resource_path('lang/language.json');
            $availableLanguages = [];
            if (file_exists($languagesFile)) {
                $availableLanguages = json_decode(file_get_contents($languagesFile), true) ?? [];
            }

            $globalSettings = [
                'currencySymbol' => '$',
                'currencyNname' => 'US Dollar',
                'base_url' => config('app.url'),
                'image_url' => config('app.url'),
                'is_demo' => config('app.is_demo', false),
                'availableLanguages' => $availableLanguages,
            ];
            $storageSettings = [
                'allowed_file_types' => 'jpg,png,webp,gif',
                'max_file_size_mb' => 2
            ];
        } else {
            // Get system settings
            $settings = settings();
            // Get currency symbol
            $currencyCode = $settings['defaultCurrency'] ?? 'USD';
            $currency = Currency::where('code', $currencyCode)->first();
            $currencySettings = [];
            if ($currency) {
                $currencySettings = [
                    'currencySymbol' => $currency->symbol,
                    'currencyNname' => $currency->name
                ];
            } else {
                $currencySettings = [
                    'currencySymbol' =>  '$',
                    'currencyNname' => 'US Dollar'
                ];
            }

            // Get storage settings from superadmin
            $storageSettings = [];
            try {
                $superAdmin = User::where('type', 'superadmin')->first();
                if ($superAdmin) {
                    $storageSettingsData = Setting::where('user_id', $superAdmin->id)
                        ->whereIn('key', ['storage_file_types', 'storage_max_upload_size'])
                        ->pluck('value', 'key')
                        ->toArray();

                    $maxSizeKB = (int)($storageSettingsData['storage_max_upload_size'] ?? 2048);
                    $storageSettings = [
                        'allowed_file_types' => $storageSettingsData['storage_file_types'] ?? 'jpg,png,webp,gif',
                        'max_file_size_mb' => round($maxSizeKB / 1024, 2)
                    ];
                } else {
                    $storageSettings = [
                        'allowed_file_types' => 'jpg,png,webp,gif',
                        'max_file_size_mb' => 2
                    ];
                }
            } catch (\Exception $e) {
                // Fallback to default settings if service fails
                $storageSettings = [
                    'allowed_file_types' => 'jpg,png,webp,gif',
                    'max_file_size_mb' => 2
                ];
            }

            // Get super admin currency settings for plans and referrals
            $superAdminCurrencySettings = [];
            try {
                $superAdmin = User::where('type', 'superadmin')->first();
                if ($superAdmin) {
                    $superAdminSettings = Setting::where('user_id', $superAdmin->id)
                        ->whereIn('key', ['decimalFormat', 'defaultCurrency', 'thousandsSeparator', 'currencySymbolSpace', 'currencySymbolPosition'])
                        ->pluck('value', 'key')
                        ->toArray();

                    $superAdminCurrencyCode = $superAdminSettings['defaultCurrency'] ?? 'USD';
                    $superAdminCurrency = Currency::where('code', $superAdminCurrencyCode)->first();

                    $superAdminCurrencySettings = [
                        'superAdminCurrencySymbol' => $superAdminCurrency ? $superAdminCurrency->symbol : '$',
                        'superAdminDecimalFormat' => $superAdminSettings['decimalFormat'] ?? '2',
                        'superAdminThousandsSeparator' => $superAdminSettings['thousandsSeparator'] ?? ',',
                        'superAdminCurrencySymbolSpace' => ($superAdminSettings['currencySymbolSpace'] ?? false) === '1',
                        'superAdminCurrencySymbolPosition' => $superAdminSettings['currencySymbolPosition'] ?? 'before',
                    ];
                }
            } catch (\Exception $e) {
                // Fallback to default super admin currency settings
                $superAdminCurrencySettings = [
                    'superAdminCurrencySymbol' => '$',
                    'superAdminDecimalFormat' => '2',
                    'superAdminThousandsSeparator' => ',',
                    'superAdminCurrencySymbolSpace' => false,
                    'superAdminCurrencySymbolPosition' => 'before',
                ];
            }

            // Get available languages
            $languagesFile = resource_path('lang/language.json');
            $availableLanguages = [];
            if (file_exists($languagesFile)) {
                $availableLanguages = json_decode(file_get_contents($languagesFile), true) ?? [];
            }

            // Get superadmin enableLogging setting for cookie consent
            $superAdminEnableLogging = false;
            try {
                $superAdmin = User::where('type', 'superadmin')->first();
                if ($superAdmin) {
                    $enableLoggingSetting = Setting::where('user_id', $superAdmin->id)
                        ->where('key', 'enableLogging')
                        ->first();
                    $superAdminEnableLogging = $enableLoggingSetting ? $enableLoggingSetting->value : false;
                }
            } catch (\Exception $e) {
                $superAdminEnableLogging = false;
            }

            // Merge currency settings with other settings
            $globalSettings = array_merge($settings, $currencySettings, $superAdminCurrencySettings);
            $globalSettings['base_url'] = config('app.url');
            $globalSettings['image_url'] = config('app.url');
            $globalSettings['is_demo'] = config('app.is_demo', false);
            $globalSettings['availableLanguages'] = $availableLanguages;
            $globalSettings['enableLogging'] = $superAdminEnableLogging;

        //     // Add cookie consent setting
        //     $cookieSetting = Setting::where('key', 'strictlyNecessaryCookies')->first();
        //     $globalSettings['strictlyNecessaryCookies'] = $cookieSetting ? (int)$cookieSetting->value : 0;
        //
        // Get layout direction from Super Admin settings for public pages
            if (config('app.is_demo')) {
                $globalSettings['layoutDirection'] = $request->cookie('layoutDirection', 'left');
            } else {
                // Only set layoutDirection if explicitly set in database, otherwise don't include it
                // This prevents automatic RTL application on login
                if (isset($globalSettings['layoutDirection']) && $globalSettings['layoutDirection'] === 'right') {
                    $globalSettings['layoutDirection'] = 'right';
                } else {
                    $globalSettings['layoutDirection'] = 'left';
                }
            }
        }

        return [
            ...parent::share($request),
            'name'  => config('app.name'),
            'base_url'  => config('app.url'),
            'image_url'  => config('app.url'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'csrf_token' => csrf_token(),
            'auth'  => [
                'user'        => $request->user(),
                'roles'       => fn() => $request->user()?->roles->pluck('name'),
                'permissions' => fn() => $request->user()?->getAllPermissions()->pluck('name'),
            ],
            'userLanguage' => $userLanguage,
            'isImpersonating' => session('impersonated_by') ? true : false,
            'ziggy' => fn(): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'globalSettings' => $globalSettings,
            'storageSettings' => $storageSettings,
            'is_demo' => config('app.is_demo',false)
        ];
    }

    /**
     * Get user language based on context
     */
    private function getUserLanguage(Request $request)
    {
        if (config('app.is_demo', false)) {
            return $request->cookie('app_language', $this->getSuperAdminDefaultLanguage() ?? 'en');
        }
        
        $user = $request->user();
        if ($user) {
            return $user->lang ?? 'en';
        }
        
        return $this->getSuperAdminDefaultLanguage() ?? 'en';
    }

    /**
     * Get superadmin's defaultLanguage setting
     */
    private function getSuperAdminDefaultLanguage()
    {
        try {
            $superAdmin = User::where('type', 'superadmin')->first();
            if ($superAdmin) {
                $setting = Setting::where('user_id', $superAdmin->id)
                    ->where('key', 'defaultLanguage')
                    ->first();
                return $setting ? $setting->value : null;
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
