<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    /**
     * The accepted legality document types.
     *
     * @var list<string>
     */
    public const DOCUMENT_TYPES = ['nib', 'akta_pendirian', 'izin_operasional', 'lainnya'];

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
        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'no_hp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in([Role::Donor->value, Role::Penerima->value])],
            'alamat' => ['required', 'string', 'max:255'],
            'jenis_dokumen' => ['required', Rule::in(self::DOCUMENT_TYPES)],
            'dokumen' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];

        return match ($this->input('role')) {
            Role::Donor->value => [
                ...$rules,
                'nama_usaha' => ['required', 'string', 'max:255', Rule::unique(DonorProfile::class, 'nama_usaha')],
                'jenis_usaha' => ['required', 'string', 'max:255'],
            ],
            Role::Penerima->value => [
                ...$rules,
                'nama_lembaga' => ['required', 'string', 'max:255', Rule::unique(RecipientProfile::class, 'nama_lembaga')],
                'jenis_lembaga' => ['required', 'string', 'max:255'],
            ],
            default => $rules,
        };
    }
}
