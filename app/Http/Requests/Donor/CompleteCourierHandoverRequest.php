<?php

namespace App\Http\Requests\Donor;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteCourierHandoverRequest extends FormRequest
{
    /**
     * Hanya donor yang boleh menyelesaikan serah terima kurir.
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
            'foto_serah_terima' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto_serah_terima.required' => 'Foto kurir wajib diunggah.',
            'foto_serah_terima.image' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto_serah_terima.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto_serah_terima.max' => 'Ukuran foto maksimal 2 MB.',
            'foto_serah_terima.max.file' => 'Ukuran foto maksimal 2 MB.',
            'foto_serah_terima.uploaded' => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'foto_serah_terima' => 'foto kurir',
        ];
    }
}
