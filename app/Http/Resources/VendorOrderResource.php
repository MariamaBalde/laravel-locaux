<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vendorSubtotal = (float) $this->items->sum('subtotal');
        $totalItems = (int) $this->items->sum('quantity');

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'shipping_method' => $this->shipping_method,
            'shipping_address' => $this->shipping_address,
            'tracking_number' => $this->tracking_number,
            'notes' => $this->notes,
            'customer' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
            ],
            'payment' => [
                'method' => $this->payment?->method,
                'status' => $this->payment?->status,
                'transaction_id' => $this->payment?->transaction_id,
                'paid_at' => $this->payment?->paid_at,
            ],
            'summary' => [
                'vendor_subtotal' => round($vendorSubtotal, 2),
                'total_items' => $totalItems,
            ],
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'product_image' => is_array($item->product?->images)
                        ? ($item->product->images[0] ?? null)
                        : null,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->price,
                    'subtotal' => (float) $item->subtotal,
                ];
            })->values(),
        ];
    }
}
