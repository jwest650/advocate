<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Services\MailConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Registration only emails a verification link when four separate things line
 * up, and three of them are per-environment (database row, .env, cached config).
 * This reports all of them at once so a broken environment can be diagnosed
 * without a shell full of tinker calls.
 */
class DiagnoseVerificationEmail extends Command
{
    protected $signature = 'diagnose:verification-email {--send= : Send a real test message to this address}';

    protected $description = 'Report why registration is or is not sending email verification links';

    private bool $failed = false;

    public function handle()
    {
        $this->newLine();
        $this->line('<options=bold>Email verification diagnostics</> — ' . app()->environment());
        $this->newLine();

        $superAdmin = $this->checkSuperAdmin();

        if ($superAdmin) {
            $this->checkToggle($superAdmin);
            $this->checkTenantSmtp($superAdmin);
        }

        $this->checkAppUrl();
        $this->checkConfigCache();
        $this->checkSmtpConnection();

        if ($address = $this->option('send')) {
            $this->sendTestMessage($address);
        }

        $this->newLine();

        if ($this->failed) {
            $this->error('One or more checks failed — see above.');

            return 1;
        }

        $this->info('All checks passed. Registration will email a verification link.');

        return 0;
    }

    private function checkSuperAdmin(): ?User
    {
        $superAdmin = User::where('type', 'superadmin')->first();

        if (!$superAdmin) {
            $this->problem('super admin', 'none found — new companies inherit no settings at all');

            return null;
        }

        $this->pass('super admin', "#{$superAdmin->id} {$superAdmin->email}");

        return $superAdmin;
    }

    private function checkToggle(User $superAdmin): void
    {
        $value = Setting::where('user_id', $superAdmin->id)
            ->where('key', 'emailVerification')
            ->value('value');

        // The stored string is read straight into an if(), so "0" and "" are off.
        if (empty($value) || $value === '0') {
            $this->problem(
                'emailVerification toggle',
                'OFF (' . var_export($value, true) . ') — enable it under Settings → System Settings as super admin'
            );

            return;
        }

        $this->pass('emailVerification toggle', 'ON (' . var_export($value, true) . ')');
    }

    private function checkTenantSmtp(User $superAdmin): void
    {
        $host = Setting::where('user_id', $superAdmin->id)->where('key', 'email_host')->value('value');

        if (config('mail.use_env_only')) {
            $stored = empty($host) ? 'nothing stored' : "stored host {$host} is ignored";
            $this->pass('tenant SMTP override', "disabled by MAIL_USE_ENV_ONLY — {$stored}");

            return;
        }

        if (empty($host)) {
            $this->pass('tenant SMTP override', 'none stored — using .env defaults');

            return;
        }

        $password = Setting::where('user_id', $superAdmin->id)->where('key', 'email_password')->value('value');

        if (empty($password)) {
            $this->problem('tenant SMTP override', "host {$host} stored with an empty password — sends will fail auth");

            return;
        }

        $this->pass('tenant SMTP override', "host {$host}");
    }

    private function checkAppUrl(): void
    {
        $url = config('app.url');

        if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            $message = app()->environment('production')
                ? "{$url} — emailed links will be unreachable"
                : "{$url} — fine locally, must change before deploying";

            app()->environment('production')
                ? $this->problem('APP_URL', $message)
                : $this->warn('  ~ APP_URL: ' . $message);

            return;
        }

        $this->pass('APP_URL', $url);
    }

    private function checkConfigCache(): void
    {
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->warn('  ~ config cache: present — run `php artisan config:clear` after editing .env');

            return;
        }

        $this->pass('config cache', 'not cached — .env is read live');
    }

    private function checkSmtpConnection(): void
    {
        // Apply the same per-tenant overrides a real send would go through.
        if ($superAdmin = User::where('type', 'superadmin')->first()) {
            Auth::login($superAdmin);
            MailConfigService::setDynamicConfig();
        }

        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $username = config('mail.mailers.smtp.username');
        $this->line("    mailer={$mailer} host={$host} port={$port} username={$username}");

        if (config('mail.default') !== 'smtp') {
            $this->pass('SMTP connection', 'skipped — mailer is ' . config('mail.default'));

            return;
        }

        if (empty(config('mail.mailers.smtp.password'))) {
            $this->problem('SMTP connection', 'MAIL_PASSWORD is empty — auth cannot succeed');

            return;
        }

        try {
            $transport = Mail::mailer('smtp')->getSymfonyTransport();
            $transport->start();
            $transport->stop();
            $this->pass('SMTP connection', "connect + auth OK ({$transport})");
        } catch (\Throwable $e) {
            $this->problem('SMTP connection', $e->getMessage());
        }
    }

    private function sendTestMessage(string $address): void
    {
        try {
            Mail::raw(
                'Verification email diagnostics from ' . config('app.name') . ' — if you received this, the mail pipeline works.',
                fn ($message) => $message->to($address)->subject('Verification email diagnostics')
            );
            $this->pass('test message', "sent to {$address}");
        } catch (\Throwable $e) {
            $this->problem('test message', $e->getMessage());
        }
    }

    private function pass(string $label, string $detail): void
    {
        $this->line("  <fg=green>✓</> {$label}: {$detail}");
    }

    private function problem(string $label, string $detail): void
    {
        $this->failed = true;
        $this->line("  <fg=red>✗</> {$label}: {$detail}");
    }
}
