<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FoodRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use App\Models\FoodRequest;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestBoardController extends Controller
{
    /**
     * Monitor and moderate all food requests on the Request Board.
     */
    public function index(Request $request): View
    {
        FoodRequest::expireOverdue();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:open,in_progress,fulfilled,expired,cancelled'],
            'urgency' => ['nullable', 'string', 'in:normal,mendesak,darurat'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
        ]);

        $search = trim($validated['q'] ?? '');
        $activeStatus = $validated['status'] ?? null;
        $activeUrgency = $validated['urgency'] ?? null;
        $selectedCategoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;

        $requests = FoodRequest::query()
            ->with(['recipient', 'category', 'donor', 'claim.post'])
            ->when($activeStatus !== null, fn ($query) => $query->where('status', $activeStatus))
            ->when($activeUrgency !== null, fn ($query) => $query->where('urgency_level', $activeUrgency))
            ->when($selectedCategoryId !== null, fn ($query) => $query->where('category_id', $selectedCategoryId))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('judul', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term)
                        ->orWhereHas('recipient', fn ($r) => $r->where('nama_lembaga', 'like', $term))
                        ->orWhereHas('donor', fn ($d) => $d->where('nama_usaha', 'like', $term));
                });
            })
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN urgency_level = 'darurat' THEN 0 WHEN urgency_level = 'mendesak' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $statusCounts = FoodRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        $pendingVerifications = User::query()
            ->pendingVerification()
            ->count();

        return view('admin.requests.index', compact(
            'requests',
            'statusCounts',
            'categories',
            'search',
            'activeStatus',
            'activeUrgency',
            'selectedCategoryId',
            'pendingVerifications',
        ));
    }

    /**
     * Cancel / close an active request as an Administrator moderation action.
     */
    public function cancel(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        if (! in_array($foodRequest->status, [FoodRequestStatus::Open, FoodRequestStatus::InProgress], true)) {
            return redirect()
                ->back()
                ->with('error', 'Hanya permintaan dengan status Terbuka atau Sedang Diproses yang dapat ditutup.');
        }

        $foodRequest->update([
            'status' => FoodRequestStatus::Cancelled,
        ]);

        if ($foodRequest->recipient?->user_id) {
            InAppNotification::create([
                'user_id' => $foodRequest->recipient->user_id,
                'judul' => 'Permintaan Kebutuhan Ditutup oleh Administrator',
                'pesan' => sprintf(
                    'Permintaan kebutuhan "%s" pada Request Board telah ditutup oleh Administrator.',
                    $foodRequest->judul,
                ),
                'tipe' => 'request',
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Permintaan kebutuhan makanan berhasil ditutup oleh Administrator.');
    }

    /**
     * Delete an inappropriate food request as an Administrator moderation action.
     */
    public function destroy(FoodRequest $foodRequest): RedirectResponse
    {
        $recipientUserId = $foodRequest->recipient?->user_id;
        $judul = $foodRequest->judul;

        $foodRequest->delete();

        if ($recipientUserId) {
            InAppNotification::create([
                'user_id' => $recipientUserId,
                'judul' => 'Permintaan Kebutuhan Dihapus oleh Administrator',
                'pesan' => sprintf(
                    'Permintaan kebutuhan "%s" telah dihapus dari Request Board oleh Administrator.',
                    $judul,
                ),
                'tipe' => 'request',
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Permintaan kebutuhan makanan berhasil dihapus dari Request Board.');
    }
}
