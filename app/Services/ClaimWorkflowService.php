<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\InAppNotification;
use App\Support\ClaimStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Shared approval and rejection workflow for claims.
 *
 * Both the donor claim page and the administrator claim management page use
 * this service so the stock reduction, timestamps, and notifications stay
 * identical no matter who makes the decision.
 */
class ClaimWorkflowService
{
    /**
     * Approve a pending claim and reduce the post's remaining portions.
     *
     * @throws DomainException when the claim was already processed or the stock is insufficient.
     */
    public function approve(Claim $claim, string $actorLabel = 'donor'): Claim
    {
        return DB::transaction(function () use ($claim, $actorLabel): Claim {
            $lockedClaim = Claim::lockForUpdate()->findOrFail($claim->id);

            if (! in_array($lockedClaim->status, ClaimStatus::GROUPS['menunggu'], true)) {
                throw new DomainException('Klaim ini sudah diproses sebelumnya.');
            }

            $foodPost = $lockedClaim->post()->lockForUpdate()->firstOrFail();

            if ($foodPost->sisa_porsi < $lockedClaim->jumlah_porsi) {
                throw new DomainException('Sisa porsi makanan tidak mencukupi untuk klaim ini.');
            }

            $foodPost->sisa_porsi -= $lockedClaim->jumlah_porsi;
            $foodPost->save();

            $lockedClaim->status = 'approved';
            $lockedClaim->approved_at = now();
            $lockedClaim->save();

            InAppNotification::create([
                'user_id' => $lockedClaim->recipient->user_id,
                'judul' => 'Klaim Disetujui',
                'pesan' => 'Klaim Anda untuk makanan "'.$foodPost->judul.'" telah disetujui oleh '.$actorLabel.'.',
                'tipe' => 'claim',
            ]);

            return $lockedClaim;
        });
    }

    /**
     * Reject a pending claim without touching the post's remaining portions.
     *
     * @throws DomainException when the claim was already processed.
     */
    public function reject(Claim $claim, string $actorLabel = 'donor'): Claim
    {
        return DB::transaction(function () use ($claim, $actorLabel): Claim {
            $lockedClaim = Claim::lockForUpdate()->findOrFail($claim->id);

            if (! in_array($lockedClaim->status, ClaimStatus::GROUPS['menunggu'], true)) {
                throw new DomainException('Klaim ini sudah diproses sebelumnya.');
            }

            $lockedClaim->status = 'rejected';
            $lockedClaim->save();

            InAppNotification::create([
                'user_id' => $lockedClaim->recipient->user_id,
                'judul' => 'Klaim Ditolak',
                'pesan' => 'Mohon maaf, klaim Anda untuk makanan "'.$lockedClaim->post->judul.'" ditolak oleh '.$actorLabel.'.',
                'tipe' => 'claim',
            ]);

            return $lockedClaim;
        });
    }
}
