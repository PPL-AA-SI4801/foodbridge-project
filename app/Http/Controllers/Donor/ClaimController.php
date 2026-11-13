<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Services\ClaimWorkflowService;
use App\Support\ClaimStatus;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(private readonly ClaimWorkflowService $claimWorkflow) {}

    /**
     * Menampilkan daftar klaim masuk pada postingan makanan milik donor yang sedang login.
     */
    public function index(Request $request)
    {
        // food_posts.donor_id mengacu ke donor_profiles.id, bukan users.id
        $donor = $request->user()->donorProfile()->firstOrFail();

        $filter = $request->query('status');
        $activeFilter = is_string($filter) && array_key_exists($filter, ClaimStatus::GROUPS) ? $filter : null;

        $ownedByDonor = fn ($query) => $query->where('donor_id', $donor->id);

        // Model Claim tidak punya created_at (WithoutTimestamps), jadi latest() akan error.
        // Relasi ke makanan bernama post(), bukan foodPost().
        $claims = Claim::query()
            ->with(['post.category', 'recipient'])
            ->whereHas('post', $ownedByDonor)
            ->when($activeFilter, fn ($query) => $query->whereIn('status', ClaimStatus::GROUPS[$activeFilter]))
            ->orderByRaw("CASE WHEN status IN ('pending', 'menunggu') THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_klaim')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statusCounts = Claim::query()
            ->whereHas('post', $ownedByDonor)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('donor.claims.index', compact('claims', 'statusCounts', 'activeFilter'));
    }

    /**
     * Menyetujui klaim dan mengurangi sisa porsi secara atomik.
     */
    public function approve(Request $request, Claim $claim)
    {
        $this->authorizeOwnership($request, $claim);

        try {
            $this->claimWorkflow->approve($claim);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Klaim berhasil disetujui dan sisa porsi telah diperbarui.');
    }

    /**
     * Menolak klaim oleh donor (status rejected tidak memotong kuota harian penerima).
     */
    public function reject(Request $request, Claim $claim)
    {
        $this->authorizeOwnership($request, $claim);

        try {
            $this->claimWorkflow->reject($claim);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Klaim berhasil ditolak.');
    }

    /**
     * Pastikan postingan pada klaim ini benar-benar milik donor yang sedang login.
     */
    private function authorizeOwnership(Request $request, Claim $claim): void
    {
        $donorId = $request->user()->donorProfile?->id;

        abort_unless($donorId !== null && $claim->post->donor_id === $donorId, 403);
    }
}
