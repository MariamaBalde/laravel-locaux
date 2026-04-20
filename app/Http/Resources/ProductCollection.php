<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ProductResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
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
            // Rétrocompatibilité temporaire
            'current_page' => $meta['current_page'],
            'per_page' => $meta['per_page'],
            'last_page' => $meta['last_page'],
            'from' => $meta['from'],
            'to' => $meta['to'],
            'total' => $meta['total'],
        ];
    }
}
