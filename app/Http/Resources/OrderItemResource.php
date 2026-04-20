<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'vendeur_id' => $this->vendeur_id,
            'quantity' => (int) $this->quantity,
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product?->id,
                    'name' => $this->product?->name,
                    'images' => $this->product?->images ?? [],
                ];
            }),
        ];
    }
}
