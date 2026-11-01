<?php

namespace App\Http\Requests\Donor;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FoodPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Donor) ?? false;
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
            'deskripsi' => ['required', 'string', 'max:255'],
            'foto' => $this->isMethod('post')
                ? ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
                : ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'jumlah_porsi' => ['required', 'integer', 'min:1'],
            'alamat' => ['required', 'string', 'max:255'],
            'catatan_kurir' => ['nullable', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'batas_konsumsi' => ['required', 'date', 'after:now'],
            'category_id' => ['required', 'integer', 'exists:food_categories,id'],
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
            'judul' => 'judul',
            'deskripsi' => 'deskripsi',
            'foto' => 'foto',
            'jumlah_porsi' => 'jumlah porsi',
            'alamat' => 'alamat',
            'catatan_kurir' => 'catatan kurir',
            'lat' => 'latitude',
            'lng' => 'longitude',
            'batas_konsumsi' => 'batas konsumsi',
            'category_id' => 'kategori',
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
            'max' => ':attribute maksimal :max karakter.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'numeric' => ':attribute harus berupa angka.',
            'between' => ':attribute harus berada di antara :min dan :max.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'after' => ':attribute harus setelah :date.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat JPG, JPEG, atau PNG.',
            'foto.max' => 'Foto maksimal 2 MB.',
        ];
    }
}
