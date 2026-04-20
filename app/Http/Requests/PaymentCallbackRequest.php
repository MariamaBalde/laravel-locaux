<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:succeeded,failed,cancelled,pending'],
            'signature' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'string'],
        ];
    }
}
