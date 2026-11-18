<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\Claim;
use App\Models\FoodPost;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminClaimRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            return [
                'recipient_id' => ['required', 'integer', 'exists:recipient_profiles,id'],
                'post_id' => ['required', 'integer', 'exists:food_posts,id'],
                'jumlah_porsi' => ['required', 'integer', 'min:1'],
            ];
        }

        return [
            'jumlah_porsi' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ];
    }

    /**
     * Validate the claim against the current stock and deadline.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $claim = $this->route('claim');
                $claim = $claim instanceof Claim ? $claim : null;
                $postId = $this->isMethod('post') ? $this->input('post_id') : $claim?->post_id;

                $post = $postId ? FoodPost::query()->find($postId) : null;

                if ($post === null) {
                    return;
                }

                if ($post->batas_konsumsi !== null && $post->batas_konsumsi->isPast()) {
                    $validator->errors()->add('post_id', 'Makanan sudah kedaluwarsa dan tidak dapat diklaim.');
                }

                if ($post->sisa_porsi <= 0) {
                    $validator->errors()->add('post_id', 'Makanan sudah habis dan tidak dapat diklaim.');
                }

                $jumlahPorsi = (int) $this->input('jumlah_porsi');

                if ($jumlahPorsi > $post->sisa_porsi) {
                    $validator->errors()->add(
                        'jumlah_porsi',
                        'Jumlah porsi tidak boleh melebihi sisa porsi yang tersedia ('.$post->sisa_porsi.' porsi).',
                    );
                }
            },
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
            'recipient_id.required' => 'Penerima wajib dipilih.',
            'recipient_id.exists' => 'Penerima yang dipilih tidak ditemukan.',
            'post_id.required' => 'Postingan makanan wajib dipilih.',
            'post_id.exists' => 'Postingan makanan yang dipilih tidak ditemukan.',
            'jumlah_porsi.required' => 'Jumlah porsi wajib diisi.',
            'jumlah_porsi.integer' => 'Jumlah porsi harus berupa angka.',
            'jumlah_porsi.min' => 'Jumlah porsi minimal adalah 1.',
            'status.required' => 'Status klaim wajib dipilih.',
            'status.in' => 'Status klaim hanya dapat berupa pending, disetujui, atau ditolak.',
        ];
    }
}
