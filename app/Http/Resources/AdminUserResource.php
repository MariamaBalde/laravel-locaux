<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'statut' => $this->statut,
            'country' => $this->country,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'vendeur' => $this->whenLoaded('vendeur', function () {
                return [
                    'id' => $this->vendeur?->id,
                    'shop_name' => $this->vendeur?->shop_name,
                    'verified' => (bool) ($this->vendeur?->verified ?? false),
                    'rating' => $this->vendeur?->rating,
                    'total_sales' => $this->vendeur?->total_sales,
                ];
            }),
        ];
    }
}
