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
     * Menampilkan daftar klaim masuk pada pos makanan milik donor yang sedang login.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Mengambil klaim yang berelasi dengan food post milik donor
        $claims = Claim::with(['foodPost', 'recipient'])
            ->whereHas('foodPost', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return view('donor.claims.index', compact('claims'));
    }

    /**
     * Menyetujui klaim dan mengurangi sisa porsi secara atomik.
     */
    public function approve(Request $request, Claim $claim)
    {
        // Pastikan food post benar-benar milik donor yang sedang login
        if ($claim->foodPost->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::transaction(function () use ($claim) {
                // Lock row food_post untuk mencegah race condition
                $foodPost = $claim->foodPost()->lockForUpdate()->first();

                // Validasi apakah sisa porsi masih mencukupi
                if ($foodPost->sisa_porsi < $claim->jumlah_porsi) {
                    throw new \Exception('Sisa porsi makanan tidak mencukupi untuk klaim ini.');
                }

                // Kurangi sisa porsi
                $foodPost->sisa_porsi -= $claim->jumlah_porsi;
                $foodPost->save();

                // Ubah status klaim menjadi disetujui / selesai
                $claim->status = 'approved';
                $claim->save();

                // Kirim notifikasi in-app ke penerima
                InAppNotification::create([
                    'user_id' => $claim->user_id,
                    'title' => 'Klaim Disetujui',
                    'message' => 'Klaim Anda untuk pos makanan "' . $foodPost->judul . '" telah disetujui oleh donor.',
                    'is_read' => false,
                ]);
            });

            return redirect()->back()->with('success', 'Klaim berhasil disetujui dan sisa porsi telah diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Menolak klaim oleh donor.
     */
    public function reject(Request $request, Claim $claim)
    {
        // Pastikan food post benar-benar milik donor yang sedang login
        if ($claim->foodPost->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($claim) {
            $claim->status = 'rejected';
            $claim->save();

            // Kirim notifikasi in-app ke penerima (status rejected tidak memotong kuota harian penerima)
            InAppNotification::create([
                'user_id' => $claim->user_id,
                'title' => 'Klaim Ditolak',
                'message' => 'Mohon maaf, klaim Anda untuk pos makanan "' . $claim->foodPost->judul . '" ditolak oleh donor.',
                'is_read' => false,
            ]);
        });

        return redirect()->back()->with('success', 'Klaim berhasil ditolak.');
    }
}