<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\NotificationTemplate;
use App\Models\UserNotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\StorageConfigService;

class SystemSettingsController extends Controller
{
    /**
     * Update the system settings.
     *
     * Handles system-wide configuration including:
     * - Language and localization settings
     * - Date/time formats and timezone
     * - Email verification requirements
     * - Landing page enable/disable toggle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'defaultLanguage' => 'required|string',
                'dateFormat' => 'required|string',
                'timeFormat' => 'required|string',
                'calendarStartDay' => 'required|string',
                'defaultTimezone' => 'required|string',
                'emailVerification' => 'boolean',
                'landingPageEnabled' => 'boolean',
                'termsConditionsUrl' => 'nullable|string',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('System settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update system settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the brand settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBrand(Request $request)
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.logoDark' => 'nullable|string',
                'settings.logoLight' => 'nullable|string',
                'settings.favicon' => 'nullable|string',
                'settings.titleText' => 'nullable|string|max:255',
                'settings.footerText' => 'nullable|string|max:500',
                'settings.themeColor' => 'nullable|string|in:blue,green,purple,orange,red,custom',
                'settings.customColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'settings.sidebarVariant' => 'nullable|string|in:inset,floating,minimal',
                'settings.sidebarStyle' => 'nullable|string|in:plain,colored,gradient',
                'settings.layoutDirection' => 'nullable|string|in:left,right,ltr,rtl',
                'settings.themeMode' => 'nullable|string|in:light,dark,system',
            ]);

            $userId = auth()->id();
            foreach ($validated['settings'] as $key => $value) {
                updateSetting($key, $value, $userId);
            }

            return redirect()->back()->with('success', __('Brand settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update brand settings: :error', ['error' => $e->getMessage()]));
        }
    }



    /**
     * Update the recaptcha settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRecaptcha(Request $request)
    {
        try {
            $validated = $request->validate([
                'recaptchaEnabled' => 'boolean',
                'recaptchaVersion' => 'required|in:v2,v3',
                'recaptchaSiteKey' => 'required|string',
                'recaptchaSecretKey' => 'required|string',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('ReCaptcha settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update ReCaptcha settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the chatgpt settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateChatgpt(Request $request)
    {
        try {
            $validated = $request->validate([
                'chatgptKey' => 'required|string',
                'chatgptModel' => 'required|string',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Chat GPT settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Chat GPT settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the storage settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStorage(Request $request)
    {
        try {
            $validated = $request->validate([
                'storage_type' => 'required|in:local,aws_s3,wasabi',
                'allowedFileTypes' => 'required|string',
                'maxUploadSize' => 'required|numeric|min:1',
                'awsAccessKeyId' => 'required_if:storage_type,aws_s3|string',
                'awsSecretAccessKey' => 'required_if:storage_type,aws_s3|string',
                'awsDefaultRegion' => 'required_if:storage_type,aws_s3|string',
                'awsBucket' => 'required_if:storage_type,aws_s3|string',
                'awsUrl' => 'required_if:storage_type,aws_s3|string',
                'awsEndpoint' => 'required_if:storage_type,aws_s3|string',
                'wasabiAccessKey' => 'required_if:storage_type,wasabi|string',
                'wasabiSecretKey' => 'required_if:storage_type,wasabi|string',
                'wasabiRegion' => 'required_if:storage_type,wasabi|string',
                'wasabiBucket' => 'required_if:storage_type,wasabi|string',
                'wasabiUrl' => 'required_if:storage_type,wasabi|string',
                'wasabiRoot' => 'required_if:storage_type,wasabi|string',
            ]);

            $userId = Auth::id();

            $settings = [
                'storage_type' => $validated['storage_type'],
                'storage_file_types' => $validated['allowedFileTypes'],
                'storage_max_upload_size' => $validated['maxUploadSize'],
            ];

            if ($validated['storage_type'] === 'aws_s3') {
                $settings['aws_access_key_id'] = $validated['awsAccessKeyId'];
                $settings['aws_secret_access_key'] = $validated['awsSecretAccessKey'];
                $settings['aws_default_region'] = $validated['awsDefaultRegion'];
                $settings['aws_bucket'] = $validated['awsBucket'];
                $settings['aws_url'] = $validated['awsUrl'];
                $settings['aws_endpoint'] = $validated['awsEndpoint'];
            }

            if ($validated['storage_type'] === 'wasabi') {
                $settings['wasabi_access_key'] = $validated['wasabiAccessKey'];
                $settings['wasabi_secret_key'] = $validated['wasabiSecretKey'];
                $settings['wasabi_region'] = $validated['wasabiRegion'];
                $settings['wasabi_bucket'] = $validated['wasabiBucket'];
                $settings['wasabi_url'] = $validated['wasabiUrl'];
                $settings['wasabi_root'] = $validated['wasabiRoot'];
            }

            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }

            // Clear storage config cache
            StorageConfigService::clearCache();

            // Also clear general cache to refresh global settings
            \Cache::forget('settings_' . $userId);

            return redirect()->back()->with('success', __('Storage settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update storage settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the cookie settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCookie(Request $request)
    {
        try {
            $validated = $request->validate([
                'enableLogging' => 'required|boolean',
                'strictlyNecessaryCookies' => 'required|boolean',
                'cookieTitle' => 'required|string|max:255',
                'strictlyCookieTitle' => 'required|string|max:255',
                'cookieDescription' => 'required|string',
                'strictlyCookieDescription' => 'required|string',
                'contactUsDescription' => 'required|string',
                'contactUsUrl' => 'required|url',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, is_bool($value) ? ($value ? '1' : '0') : $value);
            }

            return redirect()->back()->with('success', __('Cookie settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update cookie settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the SEO settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSeo(Request $request)
    {
        try {
            $validated = $request->validate([
                'metaKeywords' => 'required|string|max:255',
                'metaDescription' => 'required|string|max:160',
                'metaImage' => 'required|string',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('SEO settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update SEO settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the Google Calendar settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleCalendar(Request $request)
    {
        try {
            $validated = $request->validate([
                'googleCalendarEnabled' => 'boolean',
                'googleCalendarId' => 'nullable|string|max:255',
                'googleCalendarJson' => 'nullable|file|mimes:json|max:2048',
            ]);

            $userId = createdBy();
            $settings = [
                'googleCalendarEnabled' => $validated['googleCalendarEnabled'] ?? false,
                'googleCalendarId' => $validated['googleCalendarId'] ?? '',
            ];

            // Check if credentials are being changed
            $credentialsChanged = false;
            if (isset($settings['googleCalendarId']) && $settings['googleCalendarId'] !== getSetting('googleCalendarId', '', $userId)) {
                $credentialsChanged = true;
            }

            // Handle JSON file upload
            if ($request->hasFile('googleCalendarJson')) {
                $credentialsChanged = true;
                
                // Delete existing JSON file if it exists
                $existingPath = getSetting('googleCalendarJsonPath', null, $userId);
                if ($existingPath && \Storage::disk('public')->exists($existingPath)) {
                    \Storage::disk('public')->delete($existingPath);
                }
                
                $file = $request->file('googleCalendarJson');
                $path = $file->store('google-calendar', 'public');
                $settings['googleCalendarJsonPath'] = $path;
            }

            // Reset sync test status when credentials change
            if ($credentialsChanged) {
                $settings['is_googlecalendar_sync'] = '0';
            }

            foreach ($settings as $key => $value) {
                updateSetting($key, is_bool($value) ? ($value ? '1' : '0') : $value, $userId);
            }

            return redirect()->back()->with('success', __('Google Calendar settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Google Calendar settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Sync Google Calendar to test credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncGoogleCalendar(Request $request)
    {
        try {
            $userId = createdBy();
            $settings = settings($userId);
            
            \Log::info('Google Calendar sync attempt', [
                'user_id' => $userId,
                'settings' => [
                    'googleCalendarEnabled' => $settings['googleCalendarEnabled'] ?? 'not_set',
                    'googleCalendarId' => !empty($settings['googleCalendarId']) ? 'set' : 'empty',
                    'googleCalendarJsonPath' => !empty($settings['googleCalendarJsonPath']) ? 'set' : 'empty'
                ]
            ]);
            
            if (!($settings['googleCalendarEnabled'] ?? false) || $settings['googleCalendarEnabled'] !== '1') {
                return redirect()->back()->withErrors(['error' => __('Google Calendar integration is not enabled.')]);
            }
            
            if (empty($settings['googleCalendarId']) || trim($settings['googleCalendarId']) === '') {
                return redirect()->back()->withErrors(['error' => __('Google Calendar ID is not configured.')]);
            }
            
            if (empty($settings['googleCalendarJsonPath']) || trim($settings['googleCalendarJsonPath']) === '') {
                return redirect()->back()->withErrors(['error' => __('Google Calendar service account JSON is not uploaded.')]);
            }

            // Get the JSON file path
            $jsonPath = storage_path('app/public/' . $settings['googleCalendarJsonPath']);
            
            if (!file_exists($jsonPath)) {
                throw new \Exception('Service account JSON file not found.');
            }

            // Validate JSON file
            $jsonContent = file_get_contents($jsonPath);
            $credentials = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file format.');
            }

            if (!isset($credentials['type']) || $credentials['type'] !== 'service_account') {
                throw new \Exception('Invalid service account credentials.');
            }

            // Check if Google Client library is installed
            if (!class_exists('\Google_Client')) {
                throw new \Exception('Google Client library is not installed. Please run: composer require google/apiclient');
            }

            // Test Google Calendar API connection
            $client = new \Google_Client();
            $client->setAuthConfig($jsonPath);
            $client->addScope(\Google_Service_Calendar::CALENDAR_READONLY);
            
            $service = new \Google_Service_Calendar($client);
            
            // Test by fetching calendar info
            try {
                $calendar = $service->calendars->get($settings['googleCalendarId']);
              
                if (!$calendar) {   
                    throw new \Exception('Unable to access the specified calendar.');
                }
                // Store sync test success status
            updateSetting('is_googlecalendar_sync', '1', $userId);
            } catch (\Google_Service_Exception $calendarException) {
                // Handle specific calendar access errors
                $errorCode = $calendarException->getCode();
                if ($errorCode === 404) {
                    throw new \Exception('Calendar not found. Please check your Google Calendar ID.');
                } elseif ($errorCode === 403) {
                    throw new \Exception('Access denied. Please ensure the service account has access to this calendar.');
                } else {
                    throw new \Exception('Calendar access error: ' . $calendarException->getMessage());
                }
            }
            
            
            
            return redirect()->back()->with('success', __('Google Calendar sync test completed successfully. Connected to: :name', ['name' => $calendar->getSummary()]));
        } catch (\Google_Service_Exception $e) {
            // Clear sync test status on failure
            updateSetting('is_googlecalendar_sync', '0', $userId);
            
            $errorCode = $e->getCode();
            $errorMessage = 'Google API Error: ' . $e->getMessage();
            
            // Provide more specific error messages based on error codes
            if ($errorCode === 404) {
                $errorMessage = 'Calendar not found. Please verify your Google Calendar ID is correct.';
            } elseif ($errorCode === 403) {
                $errorMessage = 'Access denied. Please ensure the service account has proper permissions for this calendar.';
            } elseif ($errorCode === 401) {
                $errorMessage = 'Authentication failed. Please check your service account credentials.';
            }
            
            \Log::error('Google Calendar API error', [
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'calendar_id' => $settings['googleCalendarId'] ?? 'not_set'
            ]);
                return redirect()->back()->withErrors(['error' => __('Google Calendar sync failed: :error', ['error' => $e->getMessage()])]);
        } catch (\Exception $e) {
            // Clear sync test status on failure
            updateSetting('is_googlecalendar_sync', '0', $userId);
            
            \Log::error('Google Calendar sync failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
                return redirect()->back()->withErrors(['error' => __('Google Calendar sync failed: :error', ['error' => $e->getMessage()])]);
        }
    }

    /**
     * Update the Google Wallet settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleWallet(Request $request)
    {
        try {
            $validated = $request->validate([
                'googleWalletIssuerId' => 'nullable|string|max:255',
                'googleWalletJson' => 'nullable|file|mimes:json|max:2048',
            ]);

            $settings = [
                'googleWalletIssuerId' => $validated['googleWalletIssuerId'] ?? '',
            ];

            // Handle JSON file upload
            if ($request->hasFile('googleWalletJson')) {
                $file = $request->file('googleWalletJson');
                $path = $file->store('google-wallet', 'public');
                $settings['googleWalletJsonPath'] = $path;
            }

            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Google Wallet settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Google Wallet settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the Slack settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSlack(Request $request)
    {
        try {
            $userId = createdBy();
            $availableTemplates = \App\Models\NotificationTemplate::where('type', 'slack')->pluck('name', 'id')->toArray();

            $rules = [
                'slack_enabled' => 'boolean',
                'slack_webhook_url' => 'nullable|url'
            ];

            foreach ($availableTemplates as $templateId => $templateName) {
                $rules[$templateName] = 'boolean';
            }

            $validated = $request->validate($rules);

            // Update Slack configuration
            updateSetting('slack_enabled', $validated['slack_enabled'] ? '1' : '0', $userId);
            updateSetting('slack_webhook_url', $validated['slack_webhook_url'] ?? '', $userId);

            // Update notification settings
            foreach ($availableTemplates as $templateId => $templateName) {
                if (isset($validated[$templateName])) {
                    \App\Models\UserNotificationTemplate::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'template_id' => $templateId,
                            'type' => 'slack'
                        ],
                        ['is_active' => $validated[$templateName]]
                    );
                }
            }

            return redirect()->back()->with('success', __('Slack settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Slack settings: :error', ['error' => $e->getMessage()]));
        }
    }

    // public function getSlackNotifications()
    // {
    //     $userId = createdBy();
    //     $templates = \App\Models\NotificationTemplate::select('id', 'name')->get();
    //     $settings = [];

    //     foreach ($templates as $template) {
    //         $userTemplate = \App\Models\UserNotificationTemplate::where('user_id', $userId)
    //             ->where('template_id', $template->id)
    //             ->where('type', 'slack')
    //             ->first();

    //         $settings[$template->name] = $userTemplate ? (bool) $userTemplate->is_active : false;
    //     }

    //     return response()->json($settings);
    // }

    public function getAvailableSlackNotifications()
    {
        $templates = \App\Models\NotificationTemplate::where('type', 'slack')->select(['id', 'name'])->get();
        $notifications = [];

        foreach ($templates as $template) {
            $notifications[] = [
                'name' => $template->name,
                'label' => $template->name
            ];
        }

        return response()->json($notifications);
    }

    public function getSlackConfig()
    {
        $userId = createdBy();
        return response()->json([
            'slack_enabled' => getSetting('slack_enabled', '0', $userId) === '1',
            'slack_webhook_url' => getSetting('slack_webhook_url', '', $userId)
        ]);
    }

    public function updateSlackNotifications(Request $request)
    {
        try {
            $userId = createdBy();
            $availableTemplates = \App\Models\NotificationTemplate::where('type', 'slack')->pluck('name', 'id')->toArray();

            $rules = [
                'slack_enabled' => 'boolean',
                'slack_webhook_url' => 'nullable|url'
            ];

            foreach ($availableTemplates as $templateId => $templateName) {
                $rules[$templateName] = 'boolean';
            }

            $validated = $request->validate($rules);

            // Update Slack configuration
            updateSetting('slack_enabled', $validated['slack_enabled'] ? '1' : '0', $userId);
            updateSetting('slack_webhook_url', $validated['slack_webhook_url'] ?? '', $userId);

            // Update notification settings
            foreach ($availableTemplates as $templateId => $templateName) {
                if (isset($validated[$templateName])) {
                    \App\Models\UserNotificationTemplate::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'template_id' => $templateId,
                            'type' => 'slack'
                        ],
                        ['is_active' => $validated[$templateName]]
                    );
                }
            }

            return redirect()->back()->with('success', __('Slack settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Slack settings: :error', ['error' => $e->getMessage()]));
        }
    }

     public function getSlackNotifications()
    {
        $userId = createdBy();
        $templates = \App\Models\NotificationTemplate::where('type', 'slack')->select(['id', 'name'])->get();
        $settings = [];

        foreach ($templates as $template) {
            $userTemplate = \App\Models\UserNotificationTemplate::where('user_id', $userId)
                ->where('template_id', $template->id)
                ->where('type', 'slack')
                ->first();

            $settings[$template->name] = $userTemplate ? $userTemplate->is_active : false;
        }

        return response()->json($settings);
    }
    public function getAvailableTwilioNotifications()
    {
        $templates = \App\Models\NotificationTemplate::where('type', 'twilio')->select(['id', 'name'])->get();
        $notifications = [];

        foreach ($templates as $template) {
            $notifications[] = [
                'name' => $template->name,
                'label' => $template->name
            ];
        }

        return response()->json($notifications);
    }
    public function getTwilioNotifications()
    {
        $userId = createdBy();
        $templates = \App\Models\NotificationTemplate::where('type', 'twilio')->select(['id', 'name'])->get();
        $settings = [];

        foreach ($templates as $template) {
            $userTemplate = \App\Models\UserNotificationTemplate::where('user_id', $userId)
                ->where('template_id', $template->id)
                ->where('type', 'twilio')
                ->first();

            $settings[$template->name] = $userTemplate ? $userTemplate->is_active : false;
        }

        return response()->json($settings);
    }
    public function getTwilioConfig()
    {
        return response()->json([
            'twilio_sid' => getSetting('twilio_sid', ''),
            'twilio_token' => getSetting('twilio_token', ''),
            'twilio_from' => getSetting('twilio_from', '')
        ]);
    }
public function updateTwilioNotifications(Request $request)
    {
        try {
            $userId = createdBy();
            $availableTemplates = \App\Models\NotificationTemplate::where('type', 'twilio')->pluck('name', 'id')->toArray();

            $rules = [
                'twilio_sid' => 'nullable|string',
                'twilio_token' => 'nullable|string',
                'twilio_from' => 'nullable|string'
            ];

            foreach ($availableTemplates as $templateId => $templateName) {
                $rules[$templateName] = 'boolean';
            }

            $validated = $request->validate($rules);

            // Update Twilio configuration
            updateSetting('twilio_sid', $validated['twilio_sid'] ?? '');
            updateSetting('twilio_token', $validated['twilio_token'] ?? '');
            updateSetting('twilio_from', $validated['twilio_from'] ?? '');

            // Update notification settings in user_notification_templates
            foreach ($availableTemplates as $templateId => $templateName) {
                if (isset($validated[$templateName])) {
                    \App\Models\UserNotificationTemplate::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'template_id' => $templateId,
                            'type' => 'twilio'
                        ],
                        ['is_active' => $validated[$templateName]]
                    );
                }
            }

            return redirect()->back()->with('success', __('Twilio settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Twilio settings: :error', ['error' => $e->getMessage()]));
        }
    }

    public function testTwilioSMS(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        try {
            $twilioService = app(\App\Services\TwilioService::class);
            $result = $twilioService->sendTestMessage($request->phone, createdBy());

            if ($result) {
                return redirect()->back()->with('success', __('Test SMS sent successfully to :phone!', ['phone' => $request->phone]));
            }

            return redirect()->back()->with('error', __('Failed to send test SMS.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to send test SMS: :message', ['message' => $e->getMessage()]));
        }
    }

    public function testSlackWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url'
        ]);

        try {
            $slackService = app(\App\Services\SlackService::class);
            $result = $slackService->sendTestMessage($request->webhook_url);

            if ($result) {
                return redirect()->back()->with('success', __('Test message sent successfully to Slack!'));
            }

            return redirect()->back()->with('error', __('Failed to send test message.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to send test message: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Clear application cache.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            \Artisan::call('optimize:clear');

            return redirect()->back()->with('success', __('Cache cleared successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to clear cache: :error', ['error' => $e->getMessage()]));
        }
    }
}
