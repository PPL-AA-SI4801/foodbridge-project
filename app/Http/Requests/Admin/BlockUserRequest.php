<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
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
        return [
            // 200 chars keeps the full notification message within notifications.pesan (varchar 255).
            'alasan' => ['required', 'string', 'min:10', 'max:200'],
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
            'alasan' => 'alasan pemblokiran',
        ];
    }

    /**
     * Get the Indonesian validation messages for blocking.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pemblokiran wajib diisi.',
            'alasan.string' => 'Alasan pemblokiran harus berupa teks.',
            'alasan.min' => 'Alasan pemblokiran minimal 10 karakter.',
            'alasan.max' => 'Alasan pemblokiran tidak boleh lebih dari 200 karakter.',
        ];
    }
}
