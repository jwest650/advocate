<?php

namespace App\Http\Controllers;

use App\Models\UserEmailTemplate;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompanyEmailNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get all email templates
        $emailTemplates = EmailTemplate::with('emailTemplateLangs')->get();

        // Get user's notification settings
        $userSettings = UserEmailTemplate::where('user_id', $user->id)
            ->pluck('is_active', 'template_id')
            ->toArray();

        // Format templates with settings
        $templates = $emailTemplates->map(function ($template) use ($userSettings) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'is_active' => $userSettings[$template->id] ?? true,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'from' => $template->from
                ]
            ];
        });

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'templates' => $templates
            ]);
        }

        return Inertia::render('settings/email-notification-settings', [
            'templates' => $templates
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $settings = $request->input('settings', []);

        foreach ($settings as $setting) {
            UserEmailTemplate::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'template_id' => $setting['template_id']
                ],
                [
                    'is_active' => $setting['is_enabled'] ?? $setting['is_active'] ?? true
                ]
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Email notification settings updated successfully']);
        }
        
        return redirect()->back()->with('success', 'Email notification settings updated successfully');
    }
}