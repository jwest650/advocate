<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\UserEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailNotificationController extends Controller
{
    public function getNotificationSettings()
    {
        $user = Auth::user();

        $emailTemplates = EmailTemplate::with('emailTemplateLangs')->get();

        $userSettings = UserEmailTemplate::where('user_id', $user->id)
            ->get()
            ->keyBy('template_id');

        $templates = $emailTemplates->map(function ($template) use ($userSettings, $user) {
            // Get or create user setting for this template
            $userSetting = $userSettings->get($template->id);

            // If no record exists, create one as disabled
            if (!$userSetting) {
                $userSetting = UserEmailTemplate::create([
                    'template_id' => $template->id,
                    'user_id' => $user->id,
                    'is_active' => 0
                ]);
            }

            // Switch ON only if is_active = 1
            $isEnabled = $userSetting->is_active == 1;

            return [
                'id' => $template->id,
                'name' => $template->name,
                'is_active' => $isEnabled,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'from' => $template->from
                ]
            ];
        });

        return response()->json([
            'templates' => $templates
        ]);
    }

    public function updateNotificationSettings(Request $request)
    {
         try {
            $user = Auth::user();
            $settings = $request->input('settings', []);

            foreach ($settings as $setting) {
                UserEmailTemplate::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'template_id' => $setting['template_id']
                    ],
                    [
                        'is_active' => $setting['is_enabled'] ?? false
                    ]
                );
            }

            return back()->with('success', 'Email notification settings updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
