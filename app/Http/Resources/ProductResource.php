<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'images' => $this->images ?? [],
            'weight' => $this->weight,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at,
            'active_orders_count' => (int) ($this->active_orders_count ?? 0),
            'can_delete' => ((int) ($this->active_orders_count ?? 0)) === 0,
            'vendeur' => new VendeurProductResource($this->whenLoaded('vendeur')),
            'category' => new CategoryProductResource($this->whenLoaded('category')),
        ];
    }
}
