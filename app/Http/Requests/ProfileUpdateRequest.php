<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $profile = $user->role === Role::Donor
            ? $user->donorProfile
            : $user->recipientProfile;

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'no_hp' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed'],
            'current_password' => ['required_with:password', 'current_password'],
            'alamat' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ];

        if ($user->role === Role::Donor) {
            $rules['nama_usaha'] = ['required', 'string', 'max:255', Rule::unique('donor_profiles', 'nama_usaha')->ignore($profile?->id)];
            $rules['jenis_usaha'] = ['required', 'string', 'max:255'];
        } else {
            $rules['nama_lembaga'] = ['required', 'string', 'max:255', Rule::unique('recipient_profiles', 'nama_lembaga')->ignore($profile?->id)];
            $rules['jenis_lembaga'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * Get the Indonesian attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama',
            'email' => 'email',
            'no_hp' => 'nomor HP',
            'password' => 'kata sandi baru',
            'password_confirmation' => 'konfirmasi kata sandi',
            'current_password' => 'kata sandi saat ini',
            'alamat' => 'alamat',
            'lat' => 'latitude',
            'lng' => 'longitude',
            'nama_usaha' => 'nama usaha',
            'jenis_usaha' => 'jenis usaha',
            'nama_lembaga' => 'nama lembaga',
            'jenis_lembaga' => 'jenis lembaga',
        ];
    }
}