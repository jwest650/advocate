<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Events\TeamMemberCreated;
use App\Events\CaseCreated;
use App\Events\SendEmailEvent;
use App\Events\NewHearingCreated;
use App\Events\NewLicenseCreated;
use App\Events\NewJudgeCreated;
use App\Events\NewCourtCreated;
use App\Events\NewTaskCreated;
use App\Events\NewCleRecordCreated;
use App\Events\NewRegulatoryBodyCreated;
use App\Events\NewCaseCreated;
use App\Events\NewClientCreated;
use App\Events\NewInvoiceCreated;
use App\Events\InvoiceSent;
use App\Listeners\SendUserCreatedEmail;
use App\Listeners\TeamMemberCreateListener;
use App\Listeners\CaseCreateListener;
use App\Listeners\SendEmailListener;
use App\Listeners\NewHearingListener;
use App\Listeners\NewLicenseListener;
use App\Listeners\NewJudgeListener;
use App\Listeners\NewCourtListener;
use App\Listeners\NewTaskListener;
use App\Listeners\NewCleRecordListener;
use App\Listeners\NewRegulatoryBodyListener;
use App\Listeners\NewCaseListener;
use App\Listeners\NewClientListener;
use App\Listeners\NewInvoiceListener;
use App\Listeners\InvoiceSentListener;
// Slack notification listeners
use App\Listeners\SendNewCaseSlackNotification;
use App\Listeners\SendNewClientSlackNotification;
use App\Listeners\SendNewTaskSlackNotification;
use App\Listeners\SendNewHearingSlackNotification;
use App\Listeners\SendNewInvoiceSlackNotification;
use App\Listeners\SendInvoiceSentSlackNotification;
use App\Listeners\SendNewCourtSlackNotification;
use App\Listeners\SendNewJudgeSlackNotification;
use App\Listeners\SendNewLicenseSlackNotification;
use App\Listeners\SendNewRegulatoryBodySlackNotification;
use App\Listeners\SendNewCleRecordSlackNotification;
use App\Listeners\SendTeamMemberCreatedSlackNotification;
// Twilio notification listeners
use App\Listeners\SendNewCaseTwilioNotification;
use App\Listeners\SendNewClientTwilioNotification;
use App\Listeners\SendNewTaskTwilioNotification;
use App\Listeners\SendNewHearingTwilioNotification;
use App\Listeners\SendNewInvoiceTwilioNotification;
use App\Listeners\SendInvoiceSentTwilioNotification;
use App\Listeners\SendNewCourtTwilioNotification;
use App\Listeners\SendNewJudgeTwilioNotification;
use App\Listeners\SendNewLicenseTwilioNotification;
use App\Listeners\SendNewRegulatoryBodyTwilioNotification;
use App\Listeners\SendNewCleRecordTwilioNotification;
use App\Listeners\SendTeamMemberCreatedTwilioNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserCreated::class => [
            SendUserCreatedEmail::class,
        ],
        TeamMemberCreated::class => [
            TeamMemberCreateListener::class,
            SendTeamMemberCreatedSlackNotification::class,
        ],
        NewCaseCreated::class => [
            NewCaseListener::class,
            SendNewCaseSlackNotification::class,
            SendNewCaseTwilioNotification::class,
        ],

        NewHearingCreated::class => [
            NewHearingListener::class,
            SendNewHearingSlackNotification::class,
            SendNewHearingTwilioNotification::class,
        ],
        NewLicenseCreated::class => [
            NewLicenseListener::class,
            SendNewLicenseSlackNotification::class,
        ],
        NewJudgeCreated::class => [
            NewJudgeListener::class,
            SendNewJudgeSlackNotification::class,
            SendNewJudgeTwilioNotification::class,
        ],
        NewCourtCreated::class => [
            NewCourtListener::class,
            SendNewCourtSlackNotification::class,
            SendNewCourtTwilioNotification::class,
        ],
        NewTaskCreated::class => [
            NewTaskListener::class,
            SendNewTaskSlackNotification::class,
        ],
        NewCleRecordCreated::class => [
            NewCleRecordListener::class,
            SendNewCleRecordSlackNotification::class,
        ],
        NewRegulatoryBodyCreated::class => [
            NewRegulatoryBodyListener::class,
            SendNewRegulatoryBodySlackNotification::class,
            SendNewRegulatoryBodyTwilioNotification::class,
        ],
        NewClientCreated::class => [
            NewClientListener::class,
            SendNewClientSlackNotification::class,
            SendNewClientTwilioNotification::class,
        ],
        NewInvoiceCreated::class => [
            NewInvoiceListener::class,
            SendNewInvoiceSlackNotification::class,
            SendNewInvoiceTwilioNotification::class,
        ],
        InvoiceSent::class => [
            InvoiceSentListener::class,
            SendInvoiceSentSlackNotification::class,
            SendInvoiceSentTwilioNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
