<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'zipcode' => $this->zipcode,
            'city' => $this->city,
            'phone' => $this->phone,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at,
        ];
    }
}
