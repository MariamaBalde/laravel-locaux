<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\VendorVerificationRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendVendorVerificationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event - Notify admins of new vendor registration
     */
    public function handle($event): void
    {
        // Récupérer tous les administrateurs
        $admins = User::where('role', 'admin')->get();

        // Envoyer la notification à chaque admin
        foreach ($admins as $admin) {
            $admin->notify(new VendorVerificationRequest($event->vendeur));
        }
    }
}
