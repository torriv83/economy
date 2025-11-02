<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDebtRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'balance' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'interest_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'minimum_payment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Navn er påkrevd.',
            'name.string' => 'Navn må være tekst.',
            'name.max' => 'Navn kan ikke være lengre enn 255 tegn.',
            'balance.required' => 'Saldo er påkrevd.',
            'balance.numeric' => 'Saldo må være et tall.',
            'balance.min' => 'Saldo må være minst 0,01 kr.',
            'interest_rate.required' => 'Rente er påkrevd.',
            'interest_rate.numeric' => 'Rente må være et tall.',
            'interest_rate.min' => 'Rente kan ikke være negativ.',
            'interest_rate.max' => 'Rente kan ikke være mer enn 100%.',
            'minimum_payment.numeric' => 'Minimum betaling må være et tall.',
            'minimum_payment.min' => 'Minimum betaling kan ikke være negativ.',
        ];
    }
}
