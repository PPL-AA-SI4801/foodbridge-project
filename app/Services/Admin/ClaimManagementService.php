<?php

namespace App\Services\Admin;

use App\Enums\FoodPostStatus;
use App\Models\Claim;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Services\ClaimWorkflowService;
use App\Support\ClaimStatus;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for administrator claim management.
 */
class ClaimManagementService
{
    /**
     * Number of claims shown on each page.
     */
    public const PER_PAGE = 10;

    public function __construct(private readonly ClaimWorkflowService $claimWorkflow) {}

    /**
     * Paginate claims with search and status filters.
     *
     * @param  array{q?: string|null, status?: string|null}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        return Claim::query()
            ->with(['post.donor', 'recipient.user'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function (Builder $subQuery) use ($term): void {
                    $subQuery->whereHas('post', function (Builder $postQuery) use ($term): void {
                        $postQuery->where('judul', 'like', $term)
                            ->orWhereHas('donor', fn (Builder $donorQuery) => $donorQuery->where('nama_usaha', 'like', $term));
                    })->orWhereHas('recipient', function (Builder $recipientQuery) use ($term): void {
                        $recipientQuery->where('nama_lembaga', 'like', $term)
                            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('nama', 'like', $term));
                    });
                });
            })
            ->when(
                is_string($status) && array_key_exists($status, ClaimStatus::GROUPS),
                fn (Builder $query) => $query->whereIn('status', ClaimStatus::GROUPS[$status]),
            )
            ->orderByDesc('tanggal_klaim')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Count claims grouped by badge key.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = Claim::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $normalized = [];

        foreach ($counts as $status => $total) {
            $key = ClaimStatus::normalize($status);
            $normalized[$key] = ($normalized[$key] ?? 0) + (int) $total;
        }

        return $normalized;
    }

    /**
     * Create a pending claim on behalf of a recipient and notify the donor.
     *
     * @param  array{recipient_id: int, post_id: int, jumlah_porsi: int}  $data
     *
     * @throws DomainException when the post is no longer claimable.
     */
    public function create(array $data): Claim
    {
        return DB::transaction(function () use ($data): Claim {
            $post = FoodPost::query()->lockForUpdate()->findOrFail($data['post_id']);

            $this->ensurePostIsClaimable($post, (int) $data['jumlah_porsi']);

            $claim = Claim::create([
                'post_id' => $post->id,
                'recipient_id' => $data['recipient_id'],
                'jumlah_porsi' => (int) $data['jumlah_porsi'],
                'status' => 'pending',
                'tanggal_klaim' => today(),
            ]);

            InAppNotification::create([
                'user_id' => $post->donor->user_id,
                'judul' => 'Pengajuan Klaim Baru',
                'pesan' => 'Administrator mengajukan klaim sebanyak '.$claim->jumlah_porsi.' porsi pada postingan '.$post->judul.'.',
                'tipe' => 'claim',
            ]);

            return $claim;
        });
    }

    /**
     * Update a pending claim's portions and optionally approve or reject it.
     *
     * @param  array{jumlah_porsi: int, status: string}  $data
     *
     * @throws DomainException when the claim is no longer pending or the status is invalid.
     */
    public function update(Claim $claim, array $data): Claim
    {
        return DB::transaction(function () use ($claim, $data): Claim {
            $lockedClaim = Claim::query()->lockForUpdate()->findOrFail($claim->id);

            if (! in_array($lockedClaim->status, ClaimStatus::GROUPS['menunggu'], true)) {
                throw new DomainException('Klaim yang sudah diproses tidak dapat diubah.');
            }

            $status = (string) $data['status'];
            $jumlahPorsi = (int) $data['jumlah_porsi'];

            if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
                throw new DomainException('Status klaim hanya dapat diubah menjadi disetujui atau ditolak.');
            }

            if ($status === 'pending') {
                $this->ensurePostIsClaimable($lockedClaim->post, $jumlahPorsi);

                $lockedClaim->update(['jumlah_porsi' => $jumlahPorsi]);

                return $lockedClaim->refresh();
            }

            $this->ensurePostIsClaimable($lockedClaim->post, $jumlahPorsi);

            $lockedClaim->update(['jumlah_porsi' => $jumlahPorsi]);

            return $status === 'approved'
                ? $this->claimWorkflow->approve($lockedClaim, 'administrator')
                : $this->claimWorkflow->reject($lockedClaim, 'administrator');
        });
    }

    /**
     * Delete a claim and restore stock when it had already been approved.
     *
     * @throws DomainException when the claim is completed.
     */
    public function delete(Claim $claim): void
    {
        DB::transaction(function () use ($claim): void {
            $lockedClaim = Claim::query()->lockForUpdate()->findOrFail($claim->id);

            if (in_array($lockedClaim->status, ClaimStatus::GROUPS['selesai'], true)) {
                throw new DomainException('Klaim yang sudah selesai tidak dapat dihapus.');
            }

            $foodPost = $lockedClaim->post()->lockForUpdate()->first();
            $recipientUserId = $lockedClaim->recipient?->user_id;
            $wasApproved = in_array($lockedClaim->status, ClaimStatus::GROUPS['disetujui'], true);

            if ($wasApproved && $foodPost !== null) {
                $foodPost->sisa_porsi = min(
                    $foodPost->jumlah_porsi,
                    $foodPost->sisa_porsi + $lockedClaim->jumlah_porsi,
                );

                if ($foodPost->sisa_porsi > 0 && $foodPost->batas_konsumsi->isFuture()) {
                    $foodPost->status = FoodPostStatus::Tersedia;
                }

                $foodPost->save();
            }

            $judul = $foodPost?->judul ?? 'makanan';
            $lockedClaim->delete();

            if ($recipientUserId !== null) {
                InAppNotification::create([
                    'user_id' => $recipientUserId,
                    'judul' => 'Klaim Dihapus oleh Administrator',
                    'pesan' => 'Klaim Anda untuk makanan "'.$judul.'" telah dihapus oleh administrator.',
                    'tipe' => 'claim',
                ]);
            }
        });
    }

    /**
     * Ensure the post is still available and has enough portions.
     *
     * @throws DomainException when the post cannot satisfy the request.
     */
    private function ensurePostIsClaimable(FoodPost $post, int $jumlahPorsi): void
    {
        if ($post->batas_konsumsi !== null && $post->batas_konsumsi->isPast()) {
            throw new DomainException('Makanan sudah kedaluwarsa dan tidak dapat diklaim.');
        }

        if ($post->sisa_porsi <= 0) {
            throw new DomainException('Makanan sudah habis dan tidak dapat diklaim.');
        }

        if ($jumlahPorsi > $post->sisa_porsi) {
            throw new DomainException('Jumlah porsi melebihi sisa porsi yang tersedia ('.$post->sisa_porsi.' porsi).');
        }
    }
}
