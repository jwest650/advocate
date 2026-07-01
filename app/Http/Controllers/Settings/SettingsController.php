<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Currency;
use App\Models\PaymentSetting;
use App\Models\Webhook;
use App\Models\CompanySetting;
use App\Models\EmailTemplate;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserEmailTemplate;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display the main settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Get system settings using helper function
        $systemSettings = settings();
        $currencies = Currency::all();
        $paymentSettings = PaymentSetting::getUserSettings(auth()->id());
        $webhooks = Webhook::where('user_id', auth()->id())->get();
        $companySettings = CompanySetting::where('created_by', createdBy())->get();

        // Get Slack settings
        // $slackSettings = SlackSettingsService::getSettings(auth()->id());

        // Get notification templates for Slack settings
        $notificationTemplates = NotificationTemplate::select('id', 'name')->get();

        $emailTemplates = [];
        if (Auth::user()->type === 'company') {
            $emailTemplatesQuery = EmailTemplate::with('emailTemplateLangs')->get();
            $userSettings = UserEmailTemplate::where('user_id', Auth::id())->get()->keyBy('template_id');

            $emailTemplates = $emailTemplatesQuery->map(function ($template) use ($userSettings) {
                // Get or create user setting for this template
                $userSetting = $userSettings->get($template->id);

                // If no record exists, create one as disabled
                if (!$userSetting) {
                    $userSetting = UserEmailTemplate::create([
                        'template_id' => $template->id,
                        'user_id' => Auth::id(),
                        'is_active' => 0
                    ]);
                }

                // Switch ON only if is_active = 1 AND not in demo mode
                $isActive = $userSetting->is_active == 1 && !config('app.is_demo', true);

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'is_active' => $isActive,
                    'template' => [
                        'id' => $template->id,
                        'name' => $template->name,
                        'from' => $template->from
                    ]
                ];
            });
        }



        return Inertia::render('settings/index', [
            'systemSettings' => $systemSettings,
            'settings' => $systemSettings, // For helper functions
            'cacheSize' => getCacheSize(),
            'currencies' => $currencies,
            'timezones' => config('timezones'),
            'dateFormats' => config('dateformat'),
            'timeFormats' => config('timeformat'),
            'paymentSettings' => $paymentSettings,
            'webhooks' => $webhooks,
            'companySettings' => $companySettings,
            'emailTemplates' => $emailTemplates,
            // 'slackSettings' => $slackSettings,
            'notificationTemplates' => $notificationTemplates,
        ]);
    }

    public function storeCompanySetting(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255'
        ]);

        CompanySetting::create([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'General',
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', 'Company setting created successfully.');
    }

    public function updateCompanySetting(Request $request, $id)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255'
        ]);

        $setting = CompanySetting::where('id', $id)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $setting->update([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'General'
        ]);

        return redirect()->back()->with('success', 'Company setting updated successfully.');
    }

    public function destroyCompanySetting($id)
    {
        $setting = CompanySetting::where('id', $id)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $setting->delete();

        return redirect()->back()->with('success', 'Company setting deleted successfully.');
    }

    /**
     * Update layout direction setting independently of language
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLayoutDirection(Request $request)
    {
        $request->validate([
            'direction' => 'required|in:left,right'
        ]);

        $direction = $request->input('direction');

        if (auth()->check()) {
            Setting::updateOrCreate(
                [
                    'key' => 'layoutDirection',
                    'user_id' => auth()->id()
                ],
                [
                    'value' => $direction
                ]
            );

            return response()->json([
                'success' => true,
                'message' => __('Layout direction updated successfully'),
                'direction' => $direction
            ]);
        }

        return response()->json([
            'error' => __('User not authenticated')
        ], 401);
    }

    /**
     * Get current settings as JSON for API calls
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $settings = settings();
        return response()->json([
            'settings' => $settings
        ]);
    }
}
