<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Models\Setting;
use App\Models\User;

class TranslationController extends BaseController
{
    public function getTranslations($locale)
    {
        // In demo mode, check cookie first and preserve it
        if (config('app.is_demo')) {
            $cookieLang = request()->cookie('app_language');
            if ($cookieLang && $cookieLang !== $locale) {
                // Use the cookie language instead of the parameter
                $locale = $cookieLang;
            }
        }
        
        $path = resource_path("lang/{$locale}.json");
        
        if (!File::exists($path)) {
            $path = resource_path("lang/en.json");
            $locale = 'en';
        }
        
        // Validate the language in demo mode
        if (config('app.is_demo')) {
            $locale = $this->validateLanguage($locale);
            $path = resource_path("lang/{$locale}.json");
        }
        
        // Always determine direction based on locale
        $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';
        
        // For unauthenticated users on auth pages, use superadmin's language
        if (!config('app.is_demo') && !auth()->check() && request()->is('login', 'register', 'password/*', 'email/*')) {
            $superAdmin = User::where('type', 'superadmin')->first();
            if ($superAdmin) {
                $locale = $superAdmin->lang ?? 'en';
                $path = resource_path("lang/{$locale}.json");
                
                if (!File::exists($path)) {
                    $path = resource_path("lang/en.json");
                    $locale = 'en';
                }
                
                $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';
            }
        }

        $translations = json_decode(File::get($path), true);
        
        return response()->json([
            'translations' => $translations,
            'layoutDirection' => $layoutDirection,
            'locale' => $locale
        ]);
    }
    
    public function refreshLanguage($locale)
    {
        $path = resource_path("lang/{$locale}.json");
        
        if (!File::exists($path)) {
            $path = resource_path("lang/en.json");
            $locale = 'en';
        }
        
        $direction = in_array($locale, ['ar', 'he']) ? 'right' : 'left';
        $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';
        
        // Store language preference in cookie for persistence
        Cookie::queue('app_language', $locale, 60 * 24 * 30); // 30 days
        Cookie::queue('app_direction', $layoutDirection, 60 * 24 * 30);
        
        // Demo mode handling - only update database in non-demo mode
        if (config('app.is_demo') !== true && auth()->check()) {
            auth()->user()->update(['lang' => $locale]);
            
            Setting::updateOrCreate(
                [
                    'key' => 'layoutDirection',
                    'user_id' => auth()->id()
                ],
                [
                    'value' => $direction
                ]
            );
        }

        $translations = json_decode(File::get($path), true);
        
        return response()->json([
            'translations' => $translations,
            'layoutDirection' => $layoutDirection,
            'locale' => $locale
        ]);
    }
    
    public function getInitialLocale()
    {
        if (config('app.is_demo') === true) {
            $cookieLang = Cookie::get('app_language');
            return $cookieLang ?: 'en';
        }
        
        // First check cookie for all users for consistency
        $cookieLang = Cookie::get('app_language');
        if ($cookieLang) {
            return $cookieLang;
        }
        
        if (auth()->check()) {
            // For authenticated users, get from user preferences
            return auth()->user()->lang ?? 'en';
        } else if (request()->is('login', 'register', 'password/*', 'email/*')) {
            // For auth pages, get from superadmin
            $superAdmin = User::where('type', 'superadmin')->first();
            return $superAdmin->lang ?? 'en';
        }
        
        // Default fallback
        return 'en';
    }
    
    /**
     * Validate language code and ensure it exists
     */
    private function validateLanguage($locale)
    {
        // Check if language file exists
        $path = resource_path("lang/{$locale}.json");
        if (!File::exists($path)) {
            return 'en'; // Default to English if language file doesn't exist
        }
        
        // Check if language is enabled in language.json
        $languagesFile = resource_path('lang/language.json');
        if (File::exists($languagesFile)) {
            $languages = json_decode(File::get($languagesFile), true);
            $languageData = collect($languages)->firstWhere('code', $locale);
            
            if (!$languageData || (isset($languageData['enabled']) && $languageData['enabled'] === false)) {
                return 'en'; // Default to English if language is disabled
            }
        }
        
        return $locale;
    }
}