<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'string'],
            'shipping_method' => ['required', 'string', 'in:standard,express,pickup'],
            'payment_method' => ['required', 'string', 'in:wave,orange_money,stripe,paypal,visa'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
