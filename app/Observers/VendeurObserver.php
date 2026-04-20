<?php

namespace App\Observers;

use App\Events\VendorCreated;
use App\Models\Vendeur;

class VendeurObserver
{
    /**
     * Handle the Vendeur "created" event.
     */
    public function created(Vendeur $vendeur): void
    {
        // Dispatcher l'événement VendorCreated pour envoyer la notification aux admins
        VendorCreated::dispatch($vendeur);
    }

    /**
     * Handle the Vendeur "updated" event.
     */
    public function updated(Vendeur $vendeur): void
    {
        //
    }

    /**
     * Handle the Vendeur "deleted" event.
     */
    public function deleted(Vendeur $vendeur): void
    {
        //
    }

    /**
     * Handle the Vendeur "restored" event.
     */
    public function restored(Vendeur $vendeur): void
    {
        //
    }

    /**
     * Handle the Vendeur "force deleted" event.
     */
    public function forceDeleted(Vendeur $vendeur): void
    {
        //
    }
}
