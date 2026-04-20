<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:client,vendeur'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'country' => ['required', 'string', 'size:2'],

            // Champs vendeur (requis si role = vendeur)
            'shop_name' => ['exclude_unless:role,vendeur', 'required_if:role,vendeur', 'string', 'max:255'],
            'shop_description' => ['exclude_unless:role,vendeur', 'nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $role = strtolower((string) $this->input('role', ''));

        $data = [
            'role' => $role,
        ];

        if ($role !== 'vendeur') {
            // Pour un client, on ignore totalement les champs boutique.
            $data['shop_name'] = null;
            $data['shop_description'] = null;
        }

        $this->merge($data);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'role.required' => 'Le rôle est obligatoire',
            'shop_name.required_if' => 'Le nom de la boutique est obligatoire pour les vendeurs',
        ];
    }
}
