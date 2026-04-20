<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Vendeur;
use App\Notifications\VendorNewOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyVendorsOfNewOrder implements ShouldQueue
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
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        // Récupérer tous les vendeurs concernés par cette commande
        $vendorIds = $event->order->items
            ->pluck('product.vendeur_id')
            ->unique();

        // Envoyer une notification à chaque vendeur
        foreach ($vendorIds as $vendeurId) {
            $vendeur = Vendeur::with('user')->find($vendeurId);

            if ($vendeur && $vendeur->isVerified()) {
                $vendeur->user->notify(new VendorNewOrder($event->order));
            }
        }
    }
}
