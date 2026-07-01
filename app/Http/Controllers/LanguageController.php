<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class LanguageController extends Controller
{
    // Show the manage language Inertia page
    public function managePage(Request $request, $lang = null)
    {
        $langListPath = resource_path('lang/language.json');
        $languages = [];
        if (File::exists($langListPath)) {
            $languages = json_decode(File::get($langListPath), true);
        }
        $defaultLang = 'en';
        $selectedLang = $defaultLang;
        if ($lang && collect($languages)->pluck('code')->contains($lang)) {
            $selectedLang = $lang;
        }
        $defaultData = [];
        if (File::exists(resource_path("lang/{$selectedLang}.json"))) {
            $defaultData = json_decode(File::get(resource_path("lang/{$selectedLang}.json")), true);
        }

        // Get available languages with flags and enabled status
        $availableLanguages = collect($languages)
            ->map(function ($lang) {
                return [
                    'code' => $lang['code'],
                    'name' => $lang['name'],
                    'countryCode' => $lang['countryCode'] ?? 'US',
                    'flag' => $this->getCountryFlag($lang['countryCode'] ?? 'US'),
                    'enabled' => $lang['enabled'] ?? true
                ];
            })->values()->toArray();

        // Get current language status
        $currentLangData = collect($languages)->firstWhere('code', $selectedLang);
        $isCurrentLanguageEnabled = $currentLangData['enabled'] ?? true;

        return Inertia::render('manage-language', [
            'languages' => $languages,
            'defaultLang' => $selectedLang,
            'defaultData' => $defaultData,
            'availableLanguages' => $availableLanguages,
            'isCurrentLanguageEnabled' => $isCurrentLanguageEnabled,
        ]);
    }

    // Load a language file
    public function load(Request $request)
    {
        $langListPath = resource_path('lang/language.json');
        $languages = collect();
        if (\Illuminate\Support\Facades\File::exists($langListPath)) {
            $languages = collect(json_decode(\Illuminate\Support\Facades\File::get($langListPath), true));
        }
        $lang = $request->get('lang', 'en');
        if (!$languages->pluck('code')->contains($lang)) {
            return response()->json(['error' => __('Language not found')], 404);
        }
        $langPath = resource_path("lang/{$lang}.json");
        if (!\Illuminate\Support\Facades\File::exists($langPath)) {
            return response()->json(['error' => __('Language file not found')], 404);
        }
        $data = json_decode(\Illuminate\Support\Facades\File::get($langPath), true);
        return response()->json(['data' => $data]);
    }

    //  public function changeLanguage(Request $request)
    // {
    //     $languageCode = $request->input('language');

    //     // Validate language code
    //     if (!$languageCode) {
    //         return redirect()->back()->withErrors(['language' => __('Language code is required')]);
    //     }

    //     // Check if language exists and is enabled
    //     $languagesFile = resource_path('lang/language.json');
    //     if (File::exists($languagesFile)) {
    //         $languages = json_decode(File::get($languagesFile), true);
    //         $languageData = collect($languages)->firstWhere('code', $languageCode);
            
    //         if (!$languageData) {
    //             return redirect()->back()->withErrors(['language' => __('Invalid language code')]);
    //         }
            
    //         if (isset($languageData['enabled']) && $languageData['enabled'] === false) {
    //             return redirect()->back()->withErrors(['language' => __('Language is disabled')]);
    //         }
    //     }

    //     // RTL languages that should automatically set layoutDirection to 'right'
    //     $rtlLanguages = ['ar', 'he'];
    //     $layoutDirection = in_array($languageCode, $rtlLanguages) ? 'right' : 'left';

    //     if (config('app.is_demo')) {
    //         // In demo mode, don't set any cookies - let frontend handle it
    //         return redirect()->back();
    //     }

    //     if ($request->user()) {
    //         $request->user()->update(['lang' => $languageCode]);

    //         // Update layoutDirection setting in database
    //         \App\Models\Setting::updateOrCreate(
    //             [
    //                 'key' => 'layoutDirection',
    //                 'user_id' => $request->user()->id
    //             ],
    //             ['value' => $layoutDirection]
    //         );
    //     }

    //     return redirect()->back();
    // }
    public function changeLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|string|max:10'
        ]);
        $language = $request->input('language');
        // Validate that the language exists in our supported languages
        $langListPath = resource_path('lang/language.json');
        if (!\Illuminate\Support\Facades\File::exists($langListPath)) {
            return response()->json(['error' => __('Language configuration not found')], 404);
        }
        $languages = json_decode(\Illuminate\Support\Facades\File::get($langListPath), true);
        $supportedLanguages = collect($languages)->pluck('code')->toArray();
        if (!in_array($language, $supportedLanguages)) {
            return response()->json(['error' => __('Language not supported')], 400);
        }
        try {
            if (auth()->check()) {
                $user = auth()->user();
                // Update user's language in the users table
                $user->update(['lang' => $language]);
                
                // Only update layout direction for RTL languages if no manual setting exists
                // or if switching from/to RTL languages
                $currentLayoutSetting = Setting::where('key', 'layoutDirection')
                    ->where('user_id', $user->id)
                    ->first();
                
                $isRtlLanguage = in_array($language, ['ar', 'he']);
                
                // Only auto-update layout direction for RTL languages
                // Don't change it for LTR languages to preserve user's manual choice
                if ($isRtlLanguage) {
                    Setting::updateOrCreate(
                        [
                            'key' => 'layoutDirection',
                            'user_id' => $user->id
                        ],
                        [
                            'value' => 'right'
                        ]
                    );
                }
                // For LTR languages, don't modify the existing layout direction setting
                
                // Refresh the user model to ensure fresh data is available
                $user->refresh();
            }
            // return response()->json([
            //     'success' => true,
            //     'message' => __('Language changed successfully'),
            //     'language' => $language
            // ]);
            return redirect()->back();
        } catch (\Exception $e) {
            // return response()->json([
            //     'error' => __('Failed to change language: :error', ['error' => $e->getMessage()])
            // ], 500);
            return redirect()->back();
        }
    }

    // Save a language file
    public function save(Request $request)
    {
        try {
            $langListPath = resource_path('lang/language.json');
            $languages = collect();
            if (\Illuminate\Support\Facades\File::exists($langListPath)) {
                $languages = collect(json_decode(\Illuminate\Support\Facades\File::get($langListPath), true));
            }
            $lang = $request->get('lang');
            $data = $request->get('data');
            if (!$lang || !is_array($data) || !$languages->pluck('code')->contains($lang)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => __('Invalid request')], 400);
                }
                return redirect()->back()->with('error', __('Invalid request'));
            }
            $langPath = resource_path("lang/{$lang}.json");
            if (!\Illuminate\Support\Facades\File::exists($langPath)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => __('Language file not found')], 404);
                }
                return redirect()->back()->with('error', __('Language file not found'));
            }
            \Illuminate\Support\Facades\File::put($langPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($request->expectsJson()) {
                return response()->json(['success' => __('Language updated successfully')]);
            }
            return redirect()->back()->with('success', __('Language updated successfully'));
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => __('Failed to update language file: ') . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', __('Failed to update language file: ') . $e->getMessage());
        }
    }

    // Create a new language
    public function createLanguage(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'countryCode' => 'required|string|size:2'
        ], [
            'code.required' => __('Language code is required.'),
            'code.string' => __('Language code must be a valid string.'),
            'code.max' => __('Language code must not exceed 10 characters.'),
            'name.required' => __('Language name is required.'),
            'name.string' => __('Language name must be a valid string.'),
            'name.max' => __('Language name must not exceed 255 characters.'),
            'countryCode.required' => __('Country code is required.'),
            'countryCode.string' => __('Country code must be a valid string.'),
            'countryCode.size' => __('Country code must be exactly 2 characters.'),
        ]);

        try {
            // Check if language already exists in language.json
            $languagesFile = resource_path('lang/language.json');

            if (!is_writable($languagesFile)) {
                return response()->json(['error' => __('Language file is not writable. Please check file permissions.')], 500);
            }

            $languages = json_decode(\Illuminate\Support\Facades\File::get($languagesFile), true);

            $existingLanguage = collect($languages)->firstWhere('code', $request->code);
            if ($existingLanguage) {
                return response()->json(['error' => __('The language code already exists')], 422);
            }

            $languages[] = [
                'code' => $request->code,
                'name' => $request->name,
                'countryCode' => strtoupper($request->countryCode),
                'enabled' => true
            ];

            $result = \Illuminate\Support\Facades\File::put($languagesFile, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($result === false) {
                return response()->json(['error' => __('Failed to write to language file. Please check file permissions.')], 500);
            }

            // Copy en.json to new language
            $enFile = resource_path('lang/en.json');
            $newLangFile = resource_path("lang/{$request->code}.json");
            if (\Illuminate\Support\Facades\File::exists($enFile)) {
                $enContent = \Illuminate\Support\Facades\File::get($enFile);
                \Illuminate\Support\Facades\File::put($newLangFile, $enContent);
            } else {
                // Create empty translation file if en.json doesn't exist
                \Illuminate\Support\Facades\File::put($newLangFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return response()->json(['success' => true, 'message' => __('The language has been created successfully.')]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create language: ' . $e->getMessage()], 500);
        }
    }

    public function deleteLanguage($languageCode)
    {
        if ($languageCode === 'en') {
            return response()->json(['error' => __('Cannot delete English language')], 422);
        }

        // try {
            // Update all users using this language to English before deletion
            \App\Models\User::where('lang', $languageCode)->update(['lang' => 'en']);

            // Remove from language.json
            $languagesFile = resource_path('lang/language.json');
            $languages = json_decode(File::get($languagesFile), true);
            $languages = array_filter($languages, fn($lang) => $lang['code'] !== $languageCode);
            File::put($languagesFile, json_encode(array_values($languages), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Delete main language file
            $mainLangFile = resource_path("lang/{$languageCode}.json");
            if (File::exists($mainLangFile)) {
                File::delete($mainLangFile);
            }

            return response()->json(['success' => true, 'message' => __('The language has been deleted.')]);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => __('Failed to delete language: :error', ['error' => $e->getMessage()])], 500);
        // }
    }

    public function toggleLanguageStatus($languageCode)
    {
        if ($languageCode === 'en') {
            return response()->json(['error' => __('Cannot disable English language')], 422);
        }

        try {
            $languagesFile = resource_path('lang/language.json');
            $languages = json_decode(File::get($languagesFile), true);

            $isBeingDisabled = false;
            foreach ($languages as &$language) {
                if ($language['code'] === $languageCode) {
                    $currentStatus = $language['enabled'] ?? true;
                    $language['enabled'] = !$currentStatus;
                    $isBeingDisabled = $currentStatus; // If it was enabled, now it's being disabled
                    break;
                }
            }

            File::put($languagesFile, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // // If language is being disabled, update all users using this language to English
            // if ($isBeingDisabled) {
            //     \App\Models\User::where('lang', $languageCode)->update(['lang' => 'en']);
            // }

            return response()->json(['success' => true, 'message' => __('The language status updated successfully.')]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to update language status: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    private function getCountryFlag(string $countryCode): string
    {
        if (strlen($countryCode) !== 2) {
            return '🌐'; // Default flag for invalid codes
        }

        $codePoints = str_split(strtoupper($countryCode));
        $codePoints = array_map(fn($char) => 127397 + ord($char), $codePoints);
        return mb_convert_encoding('&#' . implode(';&#', $codePoints) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}
