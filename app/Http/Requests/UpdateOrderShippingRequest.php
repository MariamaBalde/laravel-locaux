<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_method' => ['required', 'string', 'in:pickup,standard,express,international,diaspora'],
            'shipping_address' => ['required', 'string'],
            'destination_country' => ['required', 'string', 'size:2'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
