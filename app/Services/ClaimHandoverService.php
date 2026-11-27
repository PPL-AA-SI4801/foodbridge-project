<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Claim;
use App\Models\InAppNotification;
use App\Models\User;
use App\Support\ClaimCode;
use App\Support\ClaimStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Serah terima mandiri PBI-12: penerbitan kode dan penyelesaian klaim.
 *
 * PBI-11 hanya menyimpan metode_pengambilan; service ini menyediakan
 * fallback ensureCode() yang idempoten untuk klaim approved
 * ambil-sendiri yang belum punya kode, tanpa menyentuh PBI-11.
 */
class ClaimHandoverService
{
    /**
     * Pastikan klaim approved ambil-sendiri punya kode verifikasi.
     * Klaim yang sudah punya kode atau bukan ambil-sendiri dikembalikan apa adanya.
     */
    public function ensureCode(Claim $claim): ?string
    {
        if (! $claim->isApproved()
            || ! in_array($claim->metode_pengambilan, [Claim::METHOD_AMBIL_SENDIRI], true)
            || ! empty($claim->kode_verifikasi)) {
            return $claim->kode_verifikasi ?: null;
        }

        return DB::transaction(function () use ($claim): string {
            $locked = Claim::lockForUpdate()->findOrFail($claim->id);

            if (! empty($locked->kode_verifikasi)) {
                return $locked->kode_verifikasi;
            }

            $locked->kode_verifikasi = ClaimCode::generateUnique(
                fn (string $code): bool => Claim::where('kode_verifikasi', $code)->exists()
            );
            $locked->save();

            return $locked->kode_verifikasi;
        });
    }

    /**
     * Selesaikan serah terima: cocokkan kode lalu tandai klaim completed.
     *
     * @throws DomainException saat status, metode, atau kode tidak valid.
     */
    public function complete(Claim $claim, ?string $kode, User $actor): Claim
    {
        $post = $claim->post;
        abort_if($post === null, 404);

        $donorId = $actor->donorProfile?->id;

        abort_unless($donorId !== null && (int) $post->donor_id === (int) $donorId, 403);

        return DB::transaction(function () use ($claim, $kode): Claim {
            $locked = Claim::lockForUpdate()->with(['post', 'recipient'])->findOrFail($claim->id);

            if (! in_array($locked->status, ClaimStatus::GROUPS['disetujui'], true)) {
                throw new DomainException('Hanya klaim yang disetujui yang dapat diselesaikan.');
            }

            if (! in_array($locked->metode_pengambilan, [Claim::METHOD_AMBIL_SENDIRI], true)) {
                throw new DomainException('Penyelesaian dengan kode hanya untuk metode Ambil Sendiri.');
            }

            $expected = (string) ($locked->kode_verifikasi ?? '');

            if ($expected === '' || ! hash_equals($expected, (string) $kode)) {
                throw new DomainException('Kode verifikasi tidak sesuai.');
            }

            $recipientUserId = $locked->recipient?->user_id;
            abort_if($recipientUserId === null, 404);

            $locked->status = 'completed';
            $locked->completed_at = now();
            $locked->save();

            InAppNotification::create([
                'user_id' => $recipientUserId,
                'judul' => 'Makanan Telah Diserahkan',
                'pesan' => 'Makanan "'.($locked->post?->judul ?? 'makanan').'" telah diserahkan. Terima kasih telah menggunakan FoodBridge.',
                'tipe' => NotificationType::Claim->value,
            ]);

            return $locked;
        });
    }
}
