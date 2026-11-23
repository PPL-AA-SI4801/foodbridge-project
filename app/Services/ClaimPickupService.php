<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\InAppNotification;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClaimPickupService
{
    /**
     * Choose pickup method for an approved claim and record courier details if courier method is selected.
     *
     * @param  array{metode_pengambilan: string, platform?: string, nama_pengemudi?: string, no_kendaraan?: string, kode_verifikasi_kurir?: string}  $data
     *
     * @throws DomainException
     */
    public function choosePickupMethod(Claim $claim, array $data, ?UploadedFile $fotoBukti, User $actor): Claim
    {
        $this->authorizeRecipientOwnership($claim, $actor);

        $metode = $data['metode_pengambilan'] ?? null;
        if (! in_array($metode, Claim::PICKUP_METHODS, true)) {
            throw new DomainException('Metode pengambilan tidak valid.');
        }

        $uploadedPath = null;

        try {
            return DB::transaction(function () use ($claim, $data, $metode, $fotoBukti, &$uploadedPath): Claim {
                $lockedClaim = Claim::lockForUpdate()->with(['post.donor', 'recipient'])->findOrFail($claim->id);

                if (! $lockedClaim->isApproved()) {
                    throw new DomainException('Klaim belum disetujui.');
                }

                if ($lockedClaim->hasPickupMethod()) {
                    throw new DomainException('Metode pengambilan sudah dipilih.');
                }

                if ($lockedClaim->isFoodExpired()) {
                    throw new DomainException('Makanan sudah melewati batas konsumsi.');
                }

                if ($metode === Claim::METHOD_KURIR) {
                    if (! $fotoBukti) {
                        throw new DomainException('Tangkapan layar pesanan kurir wajib diunggah.');
                    }

                    $uploadedPath = $fotoBukti->store('courier_proofs', 'public');

                    CourierDetail::create([
                        'claim_id' => $lockedClaim->id,
                        'platform' => $data['platform'],
                        'nama_pengemudi' => $data['nama_pengemudi'],
                        'no_kendaraan' => $data['no_kendaraan'],
                        'kode_verifikasi_kurir' => $data['kode_verifikasi_kurir'],
                        'foto_bukti' => $uploadedPath,
                        'created_at' => now(),
                    ]);
                }

                $lockedClaim->metode_pengambilan = $metode;
                $lockedClaim->save();

                $foodTitle = $lockedClaim->post?->judul ?? 'makanan';
                $pesan = $metode === Claim::METHOD_KURIR
                    ? 'Penerima telah memilih metode Kurir Online ('.$data['platform'].') untuk makanan "'.$foodTitle.'".'
                    : 'Penerima telah memilih metode Ambil Sendiri untuk makanan "'.$foodTitle.'".';

                $donorUserId = $lockedClaim->post?->donor?->user_id;
                if ($donorUserId) {
                    InAppNotification::create([
                        'user_id' => $donorUserId,
                        'judul' => 'Metode Pengambilan Ditentukan',
                        'pesan' => $pesan,
                        'tipe' => NotificationType::Claim->value,
                    ]);
                }

                return $lockedClaim;
            });
        } catch (\Throwable $e) {
            if ($uploadedPath && Storage::disk('public')->exists($uploadedPath)) {
                Storage::disk('public')->delete($uploadedPath);
            }

            throw $e;
        }
    }

    /**
     * Ensure the claim belongs to the logged-in recipient.
     */
    private function authorizeRecipientOwnership(Claim $claim, User $actor): void
    {
        $recipientId = $actor->recipientProfile?->id;

        abort_unless($recipientId !== null && $claim->recipient_id === $recipientId, 403);
    }
}
