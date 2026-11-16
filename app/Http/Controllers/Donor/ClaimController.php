<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\InAppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    /**
     * Kunci filter di URL dan nilai status yang dicakup (Inggris dan Indonesia).
     */
    private const STATUS_GROUPS = [
        'menunggu' => ['pending', 'menunggu'],
        'disetujui' => ['approved', 'disetujui'],
        'selesai' => ['completed', 'selesai'],
        'ditolak' => ['rejected', 'ditolak'],
    ];

    /**
     * Menampilkan daftar klaim masuk pada postingan makanan milik donor yang sedang login.
     */
    public function index(Request $request)
    {
        // food_posts.donor_id mengacu ke donor_profiles.id, bukan users.id
        $donor = $request->user()->donorProfile()->firstOrFail();

        $filter = $request->query('status');
        $activeFilter = is_string($filter) && array_key_exists($filter, self::STATUS_GROUPS) ? $filter : null;

        $ownedByDonor = fn ($query) => $query->where('donor_id', $donor->id);

        // Model Claim tidak punya created_at (WithoutTimestamps), jadi latest() akan error.
        // Relasi ke makanan bernama post(), bukan foodPost().
        $claims = Claim::query()
            ->with(['post.category', 'recipient'])
            ->whereHas('post', $ownedByDonor)
            ->when($activeFilter, fn ($query) => $query->whereIn('status', self::STATUS_GROUPS[$activeFilter]))
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
            DB::transaction(function () use ($claim) {
                // Kunci baris klaim dan postingan agar tidak terjadi race condition / persetujuan ganda
                $lockedClaim = Claim::lockForUpdate()->findOrFail($claim->id);

                if (! in_array($lockedClaim->status, self::STATUS_GROUPS['menunggu'], true)) {
                    throw new \DomainException('Klaim ini sudah diproses sebelumnya.');
                }

                $foodPost = $lockedClaim->post()->lockForUpdate()->firstOrFail();

                if ($foodPost->sisa_porsi < $lockedClaim->jumlah_porsi) {
                    throw new \DomainException('Sisa porsi makanan tidak mencukupi untuk klaim ini.');
                }

                $foodPost->sisa_porsi -= $lockedClaim->jumlah_porsi;
                $foodPost->save();

                $lockedClaim->status = 'approved';
                $lockedClaim->approved_at = now();
                $lockedClaim->save();

                // Notifikasi ke akun penerima (claims tidak punya user_id, ambil dari profil penerima)
                InAppNotification::create([
                    'user_id' => $lockedClaim->recipient->user_id,
                    'judul' => 'Klaim Disetujui',
                    'pesan' => 'Klaim Anda untuk makanan "'.$foodPost->judul.'" telah disetujui oleh donor.',
                    'tipe' => 'claim',
                ]);
            });
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
            DB::transaction(function () use ($claim) {
                $lockedClaim = Claim::lockForUpdate()->findOrFail($claim->id);

                if (! in_array($lockedClaim->status, self::STATUS_GROUPS['menunggu'], true)) {
                    throw new \DomainException('Klaim ini sudah diproses sebelumnya.');
                }

                $lockedClaim->status = 'rejected';
                $lockedClaim->save();

                InAppNotification::create([
                    'user_id' => $lockedClaim->recipient->user_id,
                    'judul' => 'Klaim Ditolak',
                    'pesan' => 'Mohon maaf, klaim Anda untuk makanan "'.$lockedClaim->post->judul.'" ditolak oleh donor.',
                    'tipe' => 'claim',
                ]);
            });
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