<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    /**
     * Menampilkan daftar klaim Penerima ("Klaim Saya") beserta sisa kuota harian.
     * Kebutuhan: FR-05
     */
    public function index()
    {
        $recipientId = auth()->id();
        $today = Carbon::today();

        // Mengambil riwayat klaim oleh penerima yang sedang login
        $claims = Claim::where('recipient_id', $recipientId)
            ->with('foodPost')
            ->latest()
            ->paginate(10);

        // Menghitung kuota klaim harian (maksimal 3 per hari, status rejected tidak dihitung)
        $dailyClaimsCount = Claim::where('recipient_id', $recipientId)
            ->whereDate('tanggal_klaim', $today)
            ->where('status', '!=', 'rejected')
            ->count();

        $dailyQuotaLimit = 3;
        $remainingQuota = max(0, $dailyQuotaLimit - $dailyClaimsCount);

        return view('recipient.claims.index', compact('claims', 'remainingQuota', 'dailyClaimsCount'));
    }

    /**
     * Memproses pengajuan klaim berkuota harian (PBI-07).
     * Kebutuhan: FR-05, NFR-01[cite: 1, 14, 17]
     */
    public function store(Request $request, FoodPost $foodPost)
    {
        $recipientId = auth()->id();
        $today = Carbon::today();

        // 1. Validasi input jumlah porsi (wajib integer, min 1, tidak melebihi sisa_porsi)[cite: 1]
        $request->validate([
            'jumlah_porsi' => ['required', 'integer', 'min:1', "max:{$foodPost->sisa_porsi}"],
        ], [
            'jumlah_porsi.required' => 'Jumlah porsi wajib diisi.',
            'jumlah_porsi.integer' => 'Jumlah porsi harus berupa angka.',
            'jumlah_porsi.min' => 'Jumlah porsi minimal adalah 1.',
            'jumlah_porsi.max' => 'Jumlah porsi yang diklaim tidak boleh melebihi sisa porsi makanan.',
        ]);

        // 2. Validasi kuota klaim harian (maksimal 3 kali, status rejected tidak dihitung)[cite: 1]
        $dailyClaimsCount = Claim::where('recipient_id', $recipientId)
            ->whereDate('tanggal_klaim', $today)
            ->where('status', '!=', 'rejected')
            ->count();

        if ($dailyClaimsCount >= 3) {
            return back()->withErrors(['jumlah_porsi' => 'Kuota klaim harian Anda sudah habis (3/3).']);
        }

        // 3. Validasi status makanan (tidak boleh kedaluwarsa atau habis)[cite: 1]
        if ($foodPost->batas_konsumsi && Carbon::parse($foodPost->batas_konsumsi)->isPast()) {
            return back()->withErrors(['jumlah_porsi' => 'Makanan sudah kedaluwarsa dan tidak dapat diklaim.']);
        }

        if ($foodPost->sisa_porsi <= 0) {
            return back()->withErrors(['jumlah_porsi' => 'Makanan sudah habis.']);
        }

        // 4. Validasi pencegahan klaim ganda pada post yang sama
        $existingClaim = Claim::where('recipient_id', $recipientId)
            ->where('post_id', $foodPost->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingClaim) {
            return back()->withErrors(['jumlah_porsi' => 'Anda sudah mengajukan klaim untuk makanan ini.']);
        }

        // 5. Simpan klaim berstatus pending ke database & kirim notifikasi ke Donor[cite: 1, 14, 15]
        DB::transaction(function () use ($request, $foodPost, $recipientId, $today) {
            Claim::create([
                'post_id' => $foodPost->id,
                'recipient_id' => $recipientId,
                'jumlah_porsi' => $request->jumlah_porsi,
                'status' => 'pending',
                'tanggal_klaim' => $today,
            ]);

            // Mengirim notifikasi in-app ke donor pemilik postingan (konteks PBI-08 / FR-10)[cite: 1, 15]
            InAppNotification::create([
                'user_id' => $foodPost->donor_id,
                'judul' => 'Pengajuan Klaim Baru',
                'pesan' => 'Penerima mengajukan klaim sebanyak ' . $request->jumlah_porsi . ' porsi pada postingan ' . $foodPost->judul,
                'tipe' => 'claim',
            ]);
        });

        return redirect()->route('recipient.claims.index')->with('success', 'Klaim berhasil diajukan dan tersimpan dengan status pending.');
    }
}