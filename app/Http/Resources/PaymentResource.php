<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'paid_at' => $this->paid_at,
        ];
    }
}
