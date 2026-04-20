<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DataCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($item) {
            if (is_array($item)) {
                return $item;
            }

            if ($item instanceof \JsonSerializable) {
                return (array) $item->jsonSerialize();
            }

            return method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        })->values()->all();
    }
}
