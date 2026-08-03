<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    /**
     * Point the mailer at the tenant's own SMTP credentials.
     *
     * Anything the tenant has not configured falls back to the application
     * defaults from .env rather than to a placeholder host, so a partially
     * filled — or entirely empty — settings row cannot silently redirect mail
     * at smtp.example.com and fail every send.
     */
    public static function setDynamicConfig()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $owner = $user->type == 'superadmin'
            ? User::where('type', 'superadmin')->first()
            : User::find($user->created_by);

        if (!$owner) {
            return;
        }

        $getSettings = settings($owner->id);

        // With no host stored there is nothing tenant-specific to apply; leaving
        // the .env configuration untouched keeps mail working out of the box.
        if (empty($getSettings['email_host'])) {
            return;
        }

        $settings = [
            'driver' => $getSettings['email_driver'] ?? config('mail.default'),
            'host' => $getSettings['email_host'],
            'port' => $getSettings['email_port'] ?? config('mail.mailers.smtp.port'),
            'username' => $getSettings['email_username'] ?? config('mail.mailers.smtp.username'),
            'password' => $getSettings['email_password'] ?? config('mail.mailers.smtp.password'),
            'encryption' => $getSettings['email_encryption'] ?? config('mail.mailers.smtp.encryption'),
            'fromAddress' => $getSettings['email_from_address'] ?? config('mail.from.address'),
            'fromName' => $getSettings['email_from_name'] ?? config('mail.from.name'),
        ];

        Config::set([
            'mail.default' => $settings['driver'],
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.encryption' => $settings['encryption'] === 'none' ? null : $settings['encryption'],
            'mail.mailers.smtp.username' => $settings['username'],
            'mail.mailers.smtp.password' => $settings['password'],
            'mail.from.address' => $settings['fromAddress'],
            'mail.from.name' => $settings['fromName'],
        ]);
    }
}
