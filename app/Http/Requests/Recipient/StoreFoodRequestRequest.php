<?php

namespace App\Http\Requests\Recipient;

use App\Enums\Role;
use App\Enums\UrgencyLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Penerima) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:food_categories,id'],
            'jumlah_porsi' => ['required', 'integer', 'min:1', 'max:5000'],
            'urgency_level' => ['required', Rule::enum(UrgencyLevel::class)],
            'metode_penerimaan' => ['required', 'string', Rule::in(['ambil_sendiri', 'diantar_kurir', 'fleksibel'])],
            'batas_waktu' => ['required', 'date', 'after:now'],
        ];
    }

    /**
     * Get the Indonesian attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'judul' => 'jenis/nama makanan yang dibutuhkan',
            'deskripsi' => 'keterangan tambahan',
            'category_id' => 'kategori makanan',
            'jumlah_porsi' => 'estimasi jumlah porsi',
            'urgency_level' => 'tingkat urgensi',
            'metode_penerimaan' => 'preferensi pengambilan/pengantaran',
            'batas_waktu' => 'batas waktu kebutuhan',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'date' => ':attribute harus berupa tanggal dan waktu yang valid.',
            'after' => ':attribute harus setelah waktu saat ini.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'in' => ':attribute yang dipilih tidak valid.',
        ];
    }
}
