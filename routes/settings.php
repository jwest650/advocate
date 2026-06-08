<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\EmailSettingController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SystemSettingsController;
use App\Http\Controllers\Settings\CurrencySettingController;
use App\Http\Controllers\PlanOrderController;
use App\Http\Controllers\Settings\PaymentSettingController;
use App\Http\Controllers\Settings\WebhookController;
use App\Http\Controllers\Settings\EmailNotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\PayPalPaymentController;
use App\Http\Controllers\BankPaymentController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| Here are the routes for settings management
|
*/

// Payment routes accessible without plan check
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment-methods', [PaymentSettingController::class, 'getPaymentMethods'])->name('payment.methods');
    Route::get('/enabled-payment-methods', [PaymentSettingController::class, 'getEnabledMethods'])->name('payment.enabled-methods');
    Route::post('/plan-orders', [PlanOrderController::class, 'create'])->name('plan-orders.create');
    Route::post('/stripe-payment', [StripePaymentController::class, 'processPayment'])->name('settings.stripe.payment');
});

Route::middleware(['auth', 'verified', 'plan.access'])->group(function () {
    // Payment Settings (admin only)
    Route::post('/payment-settings', [PaymentSettingController::class, 'store'])->name('payment.settings');

    // Profile settings page with profile and password sections
    Route::get('profile', function () {
        return Inertia::render('settings/profile-settings');
    })->name('profile');

    // Routes for form submissions
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile', [ProfileController::class, 'update']); // For file uploads with method spoofing
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('profile/password', [PasswordController::class, 'update'])->name('password.update');

    // Email settings page
    Route::get('settings/email', function () {
        return Inertia::render('settings/components/email-settings');
    })->name('settings.email');

    // Email settings routes
    Route::get('settings/email/get', [EmailSettingController::class, 'getEmailSettings'])->name('settings.email.get');
    Route::post('settings/email/update', [EmailSettingController::class, 'updateEmailSettings'])->name('settings.email.update');
    Route::post('settings/email/test', [EmailSettingController::class, 'sendTestEmail'])->name('settings.email.test');

    // General settings page with system and company settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('api/settings', [SettingsController::class, 'getSettings'])->name('settings.api');
    Route::post('settings/layout-direction', [SettingsController::class, 'updateLayoutDirection'])->name('settings.layout-direction.update');

    // System Settings routes
    Route::post('settings/system', [SystemSettingsController::class, 'update'])->name('settings.system.update');
    Route::post('settings/brand', [SystemSettingsController::class, 'updateBrand'])->name('settings.brand.update');
    Route::post('settings/storage', [SystemSettingsController::class, 'updateStorage'])->name('settings.storage.update');
    Route::post('settings/recaptcha', [SystemSettingsController::class, 'updateRecaptcha'])->name('settings.recaptcha.update');
    Route::post('settings/chatgpt', [SystemSettingsController::class, 'updateChatgpt'])->name('settings.chatgpt.update');
    Route::post('settings/cookie', [SystemSettingsController::class, 'updateCookie'])->name('settings.cookie.update');
    Route::post('settings/seo', [SystemSettingsController::class, 'updateSeo'])->name('settings.seo.update');
    Route::post('settings/cache/clear', [SystemSettingsController::class, 'clearCache'])->name('settings.cache.clear');

    // Currency Settings routes
    Route::post('settings/currency', [CurrencySettingController::class, 'update'])->name('settings.currency.update');

    // Webhook Settings routes
    Route::get('settings/webhooks', [WebhookController::class, 'index'])->name('settings.webhooks.index');
    Route::post('settings/webhooks', [WebhookController::class, 'store'])->name('settings.webhooks.store');
    Route::put('settings/webhooks/{webhook}', [WebhookController::class, 'update'])->name('settings.webhooks.update');
    Route::delete('settings/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('settings.webhooks.destroy');

    // Google Calendar Settings routes
    Route::post('settings/google-calendar', [SystemSettingsController::class, 'updateGoogleCalendar'])->name('settings.google-calendar.update');
    Route::post('settings/google-calendar/sync', [SystemSettingsController::class, 'syncGoogleCalendar'])->name('settings.google-calendar.sync');

    // Google Wallet Settings routes
    Route::post('settings/google-wallet', [SystemSettingsController::class, 'updateGoogleWallet'])->name('settings.google-wallet.update');

    // Email Notification Settings routes
    Route::get('settings/email-notifications/get', [EmailNotificationController::class, 'getNotificationSettings'])->name('settings.email-notifications.get');
    Route::post('settings/email-notifications/update', [EmailNotificationController::class, 'updateNotificationSettings'])->name('settings.email-notifications.update');

    // Slack Settings routes
    // Route::get('settings/slack/get', [SlackSettingController::class, 'getSlackSettings'])->name('slack.settings.get');
    // Route::post('settings/slack/update', [SlackSettingController::class, 'updateSlackSettings'])->name('slack.settings.update');
    // Route::post('settings/slack/test-webhook', [SlackSettingController::class, 'testSlackWebhook'])->name('slack.test-webhook');
    Route::get('settings/slack-notifications/available', [SystemSettingsController::class, 'getAvailableSlackNotifications'])->name('settings.slack-notifications.available');
    Route::get('settings/slack-notifications', [SystemSettingsController::class, 'getSlackNotifications'])->name('settings.slack-notifications.get');
    Route::get('settings/slack-config', [SystemSettingsController::class, 'getSlackConfig'])->name('settings.slack-config.get');
    Route::post('settings/slack-notifications', [SystemSettingsController::class, 'updateSlackNotifications'])->name('settings.slack-notifications.update');


        Route::get('settings/twilio-notifications', [SystemSettingsController::class, 'getSlackNotifications'])->name('settings.slack-notifications.get');

    // Twilio Settings routes
    Route::get('settings/twilio-notifications/available', [SystemSettingsController::class, 'getAvailableTwilioNotifications'])->name('settings.twilio-notifications.available');
    Route::get('settings/twilio-notifications', [SystemSettingsController::class, 'getTwilioNotifications'])->name('settings.twilio-notifications.get');
    Route::get('settings/twilio-config', [SystemSettingsController::class, 'getTwilioConfig'])->name('settings.twilio-config.get');
    Route::post('settings/twilio-notifications', [SystemSettingsController::class, 'updateTwilioNotifications'])->name('settings.twilio-notifications.update');
    Route::post('settings/sms/test', [SystemSettingsController::class, 'testTwilioSMS'])->name('settings.sms.test');
    Route::post('settings/slack/test-webhook', [SystemSettingsController::class, 'testSlackWebhook'])->name('slack.test-webhook');

    // Notification Template routes
    Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
    Route::get('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'show'])->name('notification-templates.show');
    Route::post('notification-templates/{notificationTemplate}/settings', [NotificationTemplateController::class, 'updateSettings'])->name('notification-templates.update-settings');
    Route::post('notification-templates/{notificationTemplate}/content', [NotificationTemplateController::class, 'updateContent'])->name('notification-templates.update-content');
});
