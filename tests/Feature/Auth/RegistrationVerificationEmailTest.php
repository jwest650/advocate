<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

/**
 * Registration only dispatches the verification link when the super admin has
 * the `emailVerification` system setting switched on. These tests pin both
 * branches so the toggle can't silently stop propagating to new sign-ups.
 */
function superAdminWithVerification(string $value): User
{
    // Registration builds the new company's roles from the permission catalogue,
    // so it has to exist before the request is made.
    test()->seed(Database\Seeders\PermissionSeeder::class);

    $superAdmin = User::factory()->create([
        'type' => 'superadmin',
        'email' => 'super@example.test',
    ]);

    Setting::updateOrCreate(
        ['user_id' => $superAdmin->id, 'key' => 'emailVerification'],
        ['value' => $value],
    );

    return $superAdmin;
}

function registerCompany(): Illuminate\Testing\TestResponse
{
    return test()->post('/register', [
        'name' => 'Case Owner',
        'email' => 'owner@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);
}

test('a verification link is sent when email verification is enabled', function () {
    Notification::fake();
    superAdminWithVerification('1');

    $response = registerCompany();

    $user = User::where('email', 'owner@example.test')->firstOrFail();

    Notification::assertSentTo($user, VerifyEmail::class);
    $response->assertRedirect(route('verification.notice', absolute: false));
    expect($user->hasVerifiedEmail())->toBeFalse();
});

test('no verification link is sent when email verification is disabled', function () {
    Notification::fake();
    superAdminWithVerification('0');

    registerCompany();

    Notification::assertNothingSent();
});

test('a failing mail server does not break the sign-up', function () {
    superAdminWithVerification('1');

    // Stand in for an unreachable or unauthenticated SMTP server.
    Notification::shouldReceive('send')
        ->andThrow(new RuntimeException('A non-empty secret is required.'));

    $response = registerCompany();

    $response->assertRedirect(route('verification.notice', absolute: false));
    $response->assertSessionHas('status', 'verification-link-failed');
    expect(User::where('email', 'owner@example.test')->exists())->toBeTrue();
});
