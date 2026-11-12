<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Services\RecipientClaimService;
use App\Support\ClaimStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function index(Request $request, RecipientClaimService $claimService): View
    {
        $recipient = $request->user()->recipientProfile()->firstOrFail();

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $status = $request->query('status');

        $filters = [
            'q' => $search,
            'status' => is_string($status) && array_key_exists($status, ClaimStatus::GROUPS) ? $status : null,
        ];

        $claims = $claimService->paginateForRecipient($recipient->id, $filters);
        $quota = $claimService->quotaSummary($recipient->id);
        $statusCounts = $claimService->statusCounts($recipient->id);
        $statusOptions = ClaimStatus::LABELS;

        return view('recipient.claims.index', compact(
            'claims',
            'quota',
            'statusCounts',
            'statusOptions',
            'filters',
        ));
    }

    public function store(Request $request, FoodPost $foodPost, RecipientClaimService $claimService)
    {
        $recipientId = $request->user()->recipientProfile()->firstOrFail()->id;
        $today = Carbon::today();

        $request->validate([
            'jumlah_porsi' => ['required', 'integer', 'min:1', "max:{$foodPost->sisa_porsi}"],
        ], [
            'jumlah_porsi.required' => 'Jumlah porsi wajib diisi.',
            'jumlah_porsi.integer' => 'Jumlah porsi harus berupa angka.',
            'jumlah_porsi.min' => 'Jumlah porsi minimal adalah 1.',
            'jumlah_porsi.max' => 'Jumlah porsi yang diklaim tidak boleh melebihi sisa porsi makanan.',
        ]);

        if ($claimService->quotaSummary($recipientId)['exhausted']) {
            $limit = RecipientClaimService::DAILY_CLAIM_LIMIT;

            return back()->withErrors(['jumlah_porsi' => "Kuota klaim harian Anda sudah habis ({$limit}/{$limit})."]);
        }

        if ($foodPost->batas_konsumsi && Carbon::parse($foodPost->batas_konsumsi)->isPast()) {
            return back()->withErrors(['jumlah_porsi' => 'Makanan sudah kedaluwarsa dan tidak dapat diklaim.']);
        }

        if ($foodPost->sisa_porsi <= 0) {
            return back()->withErrors(['jumlah_porsi' => 'Makanan sudah habis.']);
        }

        $existingClaim = Claim::where('recipient_id', $recipientId)
            ->where('post_id', $foodPost->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingClaim) {
            return back()->withErrors(['jumlah_porsi' => 'Anda sudah mengajukan klaim untuk makanan ini.']);
        }

        DB::transaction(function () use ($request, $foodPost, $recipientId, $today) {
            Claim::create([
                'post_id' => $foodPost->id,
                'recipient_id' => $recipientId,
                'jumlah_porsi' => $request->jumlah_porsi,
                'status' => 'pending',
                'tanggal_klaim' => $today,
            ]);

            InAppNotification::create([
                'user_id' => $foodPost->donor->user_id,
                'judul' => 'Pengajuan Klaim Baru',
                'pesan' => 'Penerima mengajukan klaim sebanyak '.$request->jumlah_porsi.' porsi pada postingan '.$foodPost->judul,
                'tipe' => 'claim',
            ]);
        });

        return redirect()->route('recipient.claims.index')->with('success', 'Klaim berhasil diajukan dan tersimpan dengan status pending.');
    }
}
