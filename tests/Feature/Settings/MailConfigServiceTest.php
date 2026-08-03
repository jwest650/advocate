<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\MailConfigService;

/**
 * A tenant with no SMTP settings of its own must keep using the application
 * defaults. Overwriting them with placeholders breaks every outbound mail,
 * including the registration verification link.
 */
beforeEach(function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'mail.env-default.test',
        'mail.mailers.smtp.port' => '465',
        'mail.mailers.smtp.username' => 'env@env-default.test',
        'mail.mailers.smtp.password' => 'env-secret',
        'mail.from.address' => 'env@env-default.test',
        'mail.from.name' => 'Env Default',
    ]);
});

function companyUnder(User $superAdmin): User
{
    return User::factory()->create([
        'type' => 'company',
        'created_by' => $superAdmin->id,
    ]);
}

test('application mail defaults survive when the tenant stored no smtp settings', function () {
    $superAdmin = User::factory()->create(['type' => 'superadmin']);
    $this->actingAs(companyUnder($superAdmin));

    MailConfigService::setDynamicConfig();

    expect(config('mail.mailers.smtp.host'))->toBe('mail.env-default.test')
        ->and(config('mail.mailers.smtp.username'))->toBe('env@env-default.test')
        ->and(config('mail.mailers.smtp.password'))->toBe('env-secret')
        ->and(config('mail.from.address'))->toBe('env@env-default.test');
});

test('stored tenant smtp settings override the application defaults', function () {
    $superAdmin = User::factory()->create(['type' => 'superadmin']);

    collect([
        'email_host' => 'smtp.tenant.test',
        'email_port' => '587',
        'email_username' => 'tenant@tenant.test',
        'email_password' => 'tenant-secret',
        'email_encryption' => 'tls',
        'email_from_address' => 'no-reply@tenant.test',
        'email_from_name' => 'Tenant Legal',
    ])->each(fn ($value, $key) => Setting::updateOrCreate(
        ['user_id' => $superAdmin->id, 'key' => $key],
        ['value' => $value],
    ));

    $this->actingAs(companyUnder($superAdmin));

    MailConfigService::setDynamicConfig();

    expect(config('mail.mailers.smtp.host'))->toBe('smtp.tenant.test')
        ->and(config('mail.mailers.smtp.username'))->toBe('tenant@tenant.test')
        ->and(config('mail.mailers.smtp.password'))->toBe('tenant-secret')
        ->and(config('mail.from.address'))->toBe('no-reply@tenant.test')
        ->and(config('mail.from.name'))->toBe('Tenant Legal');
});

test('an encryption setting of none clears the encryption config', function () {
    $superAdmin = User::factory()->create(['type' => 'superadmin']);

    collect(['email_host' => 'smtp.tenant.test', 'email_encryption' => 'none'])
        ->each(fn ($value, $key) => Setting::updateOrCreate(
            ['user_id' => $superAdmin->id, 'key' => $key],
            ['value' => $value],
        ));

    $this->actingAs(companyUnder($superAdmin));

    MailConfigService::setDynamicConfig();

    expect(config('mail.mailers.smtp.encryption'))->toBeNull();
});
