<?php

namespace App\Http\Requests\Recipient;

use App\Enums\Role;
use App\Models\Claim;
use App\Models\CourierDetail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChoosePickupMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Penerima) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isKurirRule = 'required_if:metode_pengambilan,'.Claim::METHOD_KURIR_ONLINE.',kurir';

        return [
            'metode_pengambilan' => ['required', 'string', Rule::in(Claim::PICKUP_METHODS)],
            'platform' => [$isKurirRule, 'nullable', 'string', Rule::in(CourierDetail::PLATFORMS)],
            'nama_pengemudi' => [$isKurirRule, 'nullable', 'string', 'max:255'],
            'no_kendaraan' => [$isKurirRule, 'nullable', 'string', 'max:20'],
            'kode_verifikasi_kurir' => [$isKurirRule, 'nullable', 'string', 'max:255'],
            'foto_bukti' => [
                $isKurirRule,
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'metode_pengambilan' => 'metode pengambilan',
            'platform' => 'platform kurir',
            'nama_pengemudi' => 'nama pengemudi',
            'no_kendaraan' => 'nomor kendaraan',
            'kode_verifikasi_kurir' => 'kode verifikasi kurir',
            'foto_bukti' => 'tangkapan layar pesanan',
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
            'max' => ':attribute tidak boleh lebih dari :max.',
            'in' => ':attribute yang dipilih tidak valid.',
            'foto_bukti.required' => 'Tangkapan layar pesanan wajib diunggah.',
            'foto_bukti.file' => 'Tangkapan layar pesanan harus berupa berkas yang valid.',
            'foto_bukti.image' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto_bukti.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto_bukti.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
