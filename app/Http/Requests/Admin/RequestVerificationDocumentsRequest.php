<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestVerificationDocumentsRequest extends FormRequest
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
            'completeness_catatan' => ['required', 'string', 'max:255'],
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
            'completeness_catatan' => 'catatan kelengkapan dokumen',
        ];
    }

    /**
     * Get the Indonesian validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'completeness_catatan.required' => 'Catatan kelengkapan dokumen wajib diisi.',
            'completeness_catatan.string' => 'Catatan kelengkapan dokumen harus berupa teks.',
            'completeness_catatan.max' => 'Catatan kelengkapan dokumen tidak boleh lebih dari 255 karakter.',
        ];
    }
}
