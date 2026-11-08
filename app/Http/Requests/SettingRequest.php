<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_name' => ['required', 'string', 'max:120'],
            'owner_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'barcode_prefix' => ['required', 'string', 'regex:/^[0-9]{2,6}$/'],
            'low_stock_limit' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'farm_name.required' => 'Nama peternakan wajib diisi.',
            'owner_name.required' => 'Nama pemilik wajib diisi.',
            'barcode_prefix.required' => 'Prefix barcode wajib diisi.',
            'barcode_prefix.regex' => 'Prefix barcode harus berupa 2 sampai 6 angka.',
            'low_stock_limit.required' => 'Batas stok rendah wajib diisi.',
        ];
    }
}
