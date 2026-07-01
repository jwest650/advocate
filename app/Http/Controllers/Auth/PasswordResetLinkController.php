<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
            'settings' => settings(),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $this->configureMail($request->email);

            Password::sendResetLink(
                $request->only('email')
            );

            return back()->with('status', __('A reset link will be sent if the account exists.'));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '530') || str_contains($e->getMessage(), 'Authentication required')) {
                return back()->with('error', __('Email configuration is not set up properly. Please contact administrator.'));
            }
            
            return back()->with('error', __('Failed to send reset link. Please try again or contact administrator.'));
        }
    }

    /**
     * Configure mail settings based on user type
     */
    private function configureMail(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return;
        }

        $configUserId = $user->created_by ?: $user->id;

        $settings = settings($configUserId);

        // Only set mail config if we have valid settings
        if (!empty($settings['email_host']) && !empty($settings['email_username']) && !empty($settings['email_password'])) {
            Config::set([
                'mail.default' => $settings['email_driver'] ?? 'smtp',
                'mail.mailers.smtp.host' => $settings['email_host'],
                'mail.mailers.smtp.port' => $settings['email_port'] ?? 587,
                'mail.mailers.smtp.encryption' => ($settings['email_encryption'] ?? 'tls') === 'none' ? null : ($settings['email_encryption'] ?? 'tls'),
                'mail.mailers.smtp.username' => $settings['email_username'],
                'mail.mailers.smtp.password' => $settings['email_password'],
                'mail.from.address' => $settings['email_from_address'] ?? $settings['email_username'],
                'mail.from.name' => $settings['email_from_name'] ?? config('app.name'),
            ]);
        }
    }
}
