<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\FoodCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminCategoryRequest extends FormRequest
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
        $category = $this->route('category');
        $category = $category instanceof FoodCategory ? $category : null;

        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                Rule::unique(FoodCategory::class, 'nama_kategori')->ignore($category?->id),
            ],
        ];
    }

    /**
     * Get custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ];
    }
}
