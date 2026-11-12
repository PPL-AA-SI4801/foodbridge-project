<?php

namespace App\Services;

use App\Models\Claim;
use App\Support\ClaimStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class RecipientClaimService
{
    /**
     * Maximum number of non-rejected claims a recipient may submit per day.
     */
    public const DAILY_CLAIM_LIMIT = 3;

    /**
     * Number of claims shown on each page of "Klaim Saya".
     */
    public const PER_PAGE = 10;

    /**
     * Paginate the authenticated recipient's claims with optional search and status filters.
     *
     * @param  array{q?: string|null, status?: string|null}  $filters
     */
    public function paginateForRecipient(int $recipientId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        return Claim::query()
            ->where('recipient_id', $recipientId)
            ->with(['post.donor', 'post.category', 'courierDetail'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->whereHas('post', function (Builder $postQuery) use ($term): void {
                    $postQuery->where('judul', 'like', $term)
                        ->orWhereHas('donor', fn (Builder $donorQuery) => $donorQuery->where('nama_usaha', 'like', $term));
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
     * Count today's claims that consume the daily quota. Rejected claims are ignored.
     */
    public function dailyClaimsCount(int $recipientId): int
    {
        return Claim::query()
            ->where('recipient_id', $recipientId)
            ->whereDate('tanggal_klaim', today())
            ->whereNotIn('status', ClaimStatus::GROUPS['ditolak'])
            ->count();
    }

    /**
     * Build the quota values shown by the page and its header.
     *
     * @return array{limit: int, used: int, remaining: int, exhausted: bool}
     */
    public function quotaSummary(int $recipientId): array
    {
        $used = $this->dailyClaimsCount($recipientId);

        return [
            'limit' => self::DAILY_CLAIM_LIMIT,
            'used' => $used,
            'remaining' => max(0, self::DAILY_CLAIM_LIMIT - $used),
            'exhausted' => $used >= self::DAILY_CLAIM_LIMIT,
        ];
    }

    /**
     * Count all claims for the recipient, grouped by badge key.
     *
     * @return array<string, int>
     */
    public function statusCounts(int $recipientId): array
    {
        $counts = Claim::query()
            ->where('recipient_id', $recipientId)
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
}
