<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    public $collects = OrderResource::class;

    public function toArray(Request $request): array
    {
        $meta = [
            'current_page' => $this->currentPage(),
            'per_page' => $this->perPage(),
            'last_page' => $this->lastPage(),
            'from' => $this->firstItem(),
            'to' => $this->lastItem(),
            'total' => $this->total(),
        ];

        return [
            'data' => $this->collection,
            'meta' => $meta,
        ];
    }
}
