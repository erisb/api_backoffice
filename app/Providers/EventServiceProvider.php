<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // \App\Events\ExampleEvent::class => [
        //     \App\Listeners\ExampleListener::class,
        // ],
        'App\Events\CacheFlushEvent' => [
            'App\Listeners\CacheFlushListener',
        ],
        'App\Events\PergiUmrohEvent' => [
            'App\Listeners\PergiUmrohEventListener',
        ],
        'App\Events\UmrohTokenEvent' => [
            'App\Listeners\UmrohTokenEventListener',
        ],
        'App\Events\DueDateUmrohEvent' => [
            'App\Listeners\DueDateUmrohEventListener',
        ],
        'App\Events\CartsEvent' => [
            'App\Listeners\CartsEventListener',
        ],
        'App\Events\LogUserMobileEvent' => [
            'App\Listeners\LogUserMobileListener',
        ],
        'App\Events\BackOfficeUserLogEvent' => [
            'App\Listeners\BackOfficeUserLogListener',
        ],
        'App\Events\NotificationEvent' => [
            'App\Listeners\NotificationEventListener',
        ],
        'App\Events\TopupEvent' => [
            'App\Listeners\TopupEventListener',
        ],
        'App\Events\TransferEvent' => [
            'App\Listeners\TransferEventListener',
        ],
        'App\Events\CashTransactionEvent' => [
            'App\Listeners\CashTransactionEventListener',
        ],
        'App\Events\DonasiEvent' => [
            'App\Listeners\DonasiEventListener',
        ],
        'App\Events\FCMJumatEvent' => [
            'App\Listeners\FCMJumatEventListener',
        ],
    ];
}
