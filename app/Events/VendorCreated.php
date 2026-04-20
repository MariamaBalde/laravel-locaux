<?php

namespace App\Events;

use App\Models\Vendeur;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorCreated
{
    use Dispatchable, SerializesModels;

    public Vendeur $vendeur;

    /**
     * Create a new event instance.
     */
    public function __construct(Vendeur $vendeur)
    {
        $this->vendeur = $vendeur;
    }
}
