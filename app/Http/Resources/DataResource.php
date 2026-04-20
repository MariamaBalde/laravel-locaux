<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        if ($this->resource instanceof \JsonSerializable) {
            return (array) $this->resource->jsonSerialize();
        }

        return (array) $this->resource;
    }
}
