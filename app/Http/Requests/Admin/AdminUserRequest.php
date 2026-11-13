<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AdminUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Administrator) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $user = $this->route('user');
        $user = $user instanceof User ? $user : null;
        $role = $isCreate ? $this->input('role') : $user?->role?->value;

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user?->id),
            ],
            'no_hp' => ['required', 'string', 'max:20'],
            'password' => $isCreate
                ? ['required', 'confirmed', Rules\Password::defaults()]
                : ['nullable', 'confirmed', Rules\Password::defaults()],
        ];

        if ($isCreate) {
            $rules['role'] = ['required', Rule::in(array_column(Role::cases(), 'value'))];
        }

        if ($role === Role::Donor->value) {
            $rules['nama_usaha'] = [
                'required',
                'string',
                'max:255',
                Rule::unique(DonorProfile::class, 'nama_usaha')->ignore($user?->donorProfile?->id),
            ];
            $rules['jenis_usaha'] = ['required', 'string', 'max:255'];
            $rules['alamat'] = ['required', 'string', 'max:255'];
            $rules['lat'] = ['required', 'numeric', 'between:-90,90'];
            $rules['lng'] = ['required', 'numeric', 'between:-180,180'];
        }

        if ($role === Role::Penerima->value) {
            $rules['nama_lembaga'] = [
                'required',
                'string',
                'max:255',
                Rule::unique(RecipientProfile::class, 'nama_lembaga')->ignore($user?->recipientProfile?->id),
            ];
            $rules['jenis_lembaga'] = ['required', 'string', 'max:255'];
            $rules['alamat'] = ['required', 'string', 'max:255'];
            $rules['lat'] = ['required', 'numeric', 'between:-90,90'];
            $rules['lng'] = ['required', 'numeric', 'between:-180,180'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.lowercase' => 'Email harus menggunakan huruf kecil.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.max' => 'Nomor HP maksimal 20 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Peran akun tidak valid.',
            'nama_usaha.required' => 'Nama usaha wajib diisi.',
            'nama_usaha.unique' => 'Nama usaha sudah digunakan oleh donor lain.',
            'jenis_usaha.required' => 'Jenis usaha wajib diisi.',
            'nama_lembaga.required' => 'Nama lembaga wajib diisi.',
            'nama_lembaga.unique' => 'Nama lembaga sudah digunakan oleh penerima lain.',
            'jenis_lembaga.required' => 'Jenis lembaga wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'lat.required' => 'Latitude wajib diisi.',
            'lat.numeric' => 'Latitude harus berupa angka.',
            'lat.between' => 'Latitude harus berada di antara -90 dan 90.',
            'lng.required' => 'Longitude wajib diisi.',
            'lng.numeric' => 'Longitude harus berupa angka.',
            'lng.between' => 'Longitude harus berada di antara -180 dan 180.',
        ];
    }
}
