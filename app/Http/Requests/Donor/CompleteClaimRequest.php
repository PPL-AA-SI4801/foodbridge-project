<?php

namespace App\Http\Requests\Donor;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteClaimRequest extends FormRequest
{
    /**
     * Hanya donor yang boleh menyelesaikan serah terima.
     * Kepemilikan postingan diperiksa di ClaimHandoverService (403).
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Donor) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kode_verifikasi' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_verifikasi.required' => 'Kode verifikasi wajib diisi.',
            'kode_verifikasi.size' => 'Kode verifikasi tidak sesuai.',
            'kode_verifikasi.regex' => 'Kode verifikasi tidak sesuai.',
        ];
    }
}
