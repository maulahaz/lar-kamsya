<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'price' => ['required', 'numeric', 'min:0'],
            'additional_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'goat.name' => ['required', 'string', 'max:100'],
            'goat.type' => ['required', 'string', 'max:40'],
            'goat.gender' => ['required', Rule::in(['jantan', 'betina'])],
            'goat.birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'goat.weight' => ['required', 'numeric', 'min:0', 'max:500'],
            'goat.color' => ['nullable', 'string', 'max:40'],
            'goat.notes' => ['nullable', 'string', 'max:1000'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal pembelian wajib diisi.',
            'date.before_or_equal' => 'Tanggal pembelian tidak boleh melebihi hari ini.',
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'price.required' => 'Harga beli wajib diisi.',
            'goat.name.required' => 'Nama kambing wajib diisi.',
            'goat.type.required' => 'Jenis kambing wajib diisi.',
            'goat.gender.required' => 'Jenis kelamin wajib dipilih.',
            'goat.weight.required' => 'Bobot kambing wajib diisi.',
        ];
    }
}
