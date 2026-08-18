<?php

use App\Models\Setting;
use App\Models\User;

/**
 * The System Settings form is the only supported way to turn registration
 * verification on, so the boolean round-trip through it has to hold: what the
 * switch sends must be what getSetting() later reads back.
 */
function postSystemSettings(array $overrides = []): Illuminate\Testing\TestResponse
{
    return test()->post(route('settings.system.update'), array_merge([
        'defaultLanguage' => 'en',
        'dateFormat' => 'MM/DD/YYYY',
        'timeFormat' => '12h',
        'calendarStartDay' => 'sunday',
        'defaultTimezone' => 'UTC',
        'emailVerification' => true,
        'landingPageEnabled' => true,
        'termsConditionsUrl' => '',
    ], $overrides));
}

test('switching email verification on is persisted and read back as enabled', function () {
    $superAdmin = User::factory()->create(['type' => 'superadmin']);
    $this->actingAs($superAdmin);

    postSystemSettings(['emailVerification' => true]);

    $stored = Setting::where('user_id', $superAdmin->id)
        ->where('key', 'emailVerification')
        ->value('value');

    expect($stored)->not->toBeEmpty()
        ->and((bool) getSetting('emailVerification', false))->toBeTrue();
});

test('switching email verification off is persisted and read back as disabled', function () {
    $superAdmin = User::factory()->create(['type' => 'superadmin']);
    Setting::updateOrCreate(
        ['user_id' => $superAdmin->id, 'key' => 'emailVerification'],
        ['value' => '1'],
    );
    $this->actingAs($superAdmin);

    postSystemSettings(['emailVerification' => false]);

    expect((bool) getSetting('emailVerification', false))->toBeFalse();
});

test('the toggle writes to the super admin row that new registrations inherit', function () {
    $superAdmin = User::factory()->create(['type' => 'superadmin']);
    $this->actingAs($superAdmin);

    postSystemSettings(['emailVerification' => true]);

    expect(Setting::where('user_id', $superAdmin->id)->where('key', 'emailVerification')->exists())
        ->toBeTrue();
});
