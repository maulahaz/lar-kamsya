<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $goat = $this->route('goat');
        $id = $goat?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:40'],
            'gender' => ['required', Rule::in(['jantan', 'betina'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'weight' => ['required', 'numeric', 'min:0', 'max:500'],
            'color' => ['nullable', 'string', 'max:40'],
            'origin' => ['sometimes', Rule::in(['pembelian', 'peranakan'])],
            'mother_id' => ['nullable', 'integer', Rule::exists('goats', 'id')->whereNull('deleted_at'), Rule::notIn(array_filter([$id]))],
            'entry_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kambing wajib diisi.',
            'type.required' => 'Jenis kambing wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'weight.required' => 'Bobot kambing wajib diisi.',
            'weight.numeric' => 'Bobot harus berupa angka.',
            'entry_date.required' => 'Tanggal masuk wajib diisi.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'mother_id.not_in' => 'Kambing tidak dapat menjadi induk dirinya sendiri.',
        ];
    }
}
