<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'goat_id' => ['required', 'integer', Rule::exists('goats', 'id')->whereNull('deleted_at')->where('status', 'tersedia')],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'price' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal penjualan wajib diisi.',
            'date.before_or_equal' => 'Tanggal penjualan tidak boleh melebihi hari ini.',
            'goat_id.required' => 'Kambing wajib dipilih.',
            'goat_id.exists' => 'Kambing tidak tersedia untuk dijual.',
            'customer_id.required' => 'Customer wajib dipilih.',
            'price.required' => 'Harga jual wajib diisi.',
        ];
    }
}
