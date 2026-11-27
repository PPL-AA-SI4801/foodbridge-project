<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\InAppNotification;
use App\Models\User;
use App\Support\ClaimCode;
use App\Support\ClaimStatus;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Serah terima klaim: PBI-12 (ambil-sendiri via kode) dan PBI-23 (kurir via foto).
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
        $this->authorizeDonorOwnership($claim, $actor);

        return DB::transaction(function () use ($claim, $kode): Claim {
            $locked = Claim::lockForUpdate()->with(['post', 'recipient'])->findOrFail($claim->id);

            $this->ensureApproved($locked);

            if (! in_array($locked->metode_pengambilan, [Claim::METHOD_AMBIL_SENDIRI], true)) {
                throw new DomainException('Penyelesaian dengan kode hanya untuk metode Ambil Sendiri.');
            }

            $expected = (string) ($locked->kode_verifikasi ?? '');

            if ($expected === '' || ! hash_equals($expected, (string) $kode)) {
                throw new DomainException('Kode verifikasi tidak sesuai.');
            }

            return $this->markCompleted($locked);
        });
    }

    /**
     * Selesaikan serah terima kurir PBI-23: simpan foto lalu tandai klaim completed.
     *
     * @throws DomainException saat status, metode, atau data pengemudi tidak valid.
     */
    public function completeCourier(Claim $claim, UploadedFile $foto, User $actor): Claim
    {
        $this->authorizeDonorOwnership($claim, $actor);

        $uploadedPath = null;

        try {
            return DB::transaction(function () use ($claim, $foto, &$uploadedPath): Claim {
                $locked = Claim::lockForUpdate()->with(['post', 'recipient', 'courierDetail'])->findOrFail($claim->id);

                $this->ensureApproved($locked);

                if (! in_array($locked->metode_pengambilan, [Claim::METHOD_KURIR_ONLINE, 'kurir'], true)) {
                    throw new DomainException('Penyelesaian dengan foto hanya untuk metode Kurir Online.');
                }

                $courier = $locked->courierDetail;

                if ($courier === null) {
                    throw new DomainException('Data pengemudi belum tersedia.');
                }

                $uploadedPath = $foto->store('courier_handovers', 'public');

                $oldPath = $courier->foto_serah_terima;

                if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $uploadedPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $courier->foto_serah_terima = $uploadedPath;
                $courier->save();

                return $this->markCompleted($locked);
            });
        } catch (\Throwable $e) {
            $this->deleteOrphanedUpload($claim->id, $uploadedPath);

            throw $e;
        }
    }

    /**
     * Pastikan klaim milik postingan donor yang sedang bertindak.
     */
    private function authorizeDonorOwnership(Claim $claim, User $actor): void
    {
        $post = $claim->post;
        abort_if($post === null, 404);

        $donorId = $actor->donorProfile?->id;

        abort_unless($donorId !== null && (int) $post->donor_id === (int) $donorId, 403);
    }

    /**
     * Pastikan klaim yang dikunci masih berstatus disetujui.
     *
     * @throws DomainException
     */
    private function ensureApproved(Claim $locked): void
    {
        if (! in_array($locked->status, ClaimStatus::GROUPS['disetujui'], true)) {
            throw new DomainException('Hanya klaim yang disetujui yang dapat diselesaikan.');
        }
    }

    /**
     * Tandai klaim completed dan beri tahu penerima.
     */
    private function markCompleted(Claim $locked): Claim
    {
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
    }

    /**
     * Hapus file yatim bila transaksi DB gagal setelah store.
     * Cek ulang agar file hasil penyelesaian sukses sebelumnya tidak terhapus.
     */
    private function deleteOrphanedUpload(int $claimId, ?string $uploadedPath): void
    {
        if (! is_string($uploadedPath) || $uploadedPath === '' || ! Storage::disk('public')->exists($uploadedPath)) {
            return;
        }

        $current = CourierDetail::where('claim_id', $claimId)->value('foto_serah_terima');

        if ($current !== $uploadedPath) {
            Storage::disk('public')->delete($uploadedPath);
        }
    }
}
