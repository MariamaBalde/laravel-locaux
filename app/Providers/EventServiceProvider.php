<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\VendorCreated;
use App\Listeners\NotifyVendor;
use App\Listeners\SendOrderConfirmation;
use App\Listeners\SendVendorVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderPlaced::class => [
            SendOrderConfirmation::class,
            NotifyVendor::class,
        ],
        VendorCreated::class => [
            SendVendorVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
