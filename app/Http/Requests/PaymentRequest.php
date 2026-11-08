<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.min' => 'Nominal pembayaran harus lebih dari nol.',
            'method.required' => 'Metode pembayaran wajib dipilih.',
        ];
    }
}
