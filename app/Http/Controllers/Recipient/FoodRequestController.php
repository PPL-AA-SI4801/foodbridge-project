<?php

namespace App\Http\Controllers\Recipient;

use App\Enums\AccountStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\Role;
use App\Enums\UrgencyLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipient\StoreFoodRequestRequest;
use App\Models\FoodCategory;
use App\Models\FoodRequest;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FoodRequestController extends Controller
{
    /**
     * Display the recipient's own food requests on the Request Board.
     */
    public function index(Request $request): View
    {
        FoodRequest::expireOverdue();

        $recipient = $this->recipientProfile($request);

        $statusFilter = $request->query('status');
        $allowedFilters = array_map(fn (FoodRequestStatus $case) => $case->value, FoodRequestStatus::cases());
        $activeFilter = is_string($statusFilter) && in_array($statusFilter, $allowedFilters, true)
            ? $statusFilter
            : null;

        $requests = FoodRequest::query()
            ->with(['category', 'donor', 'claim.post'])
            ->where('recipient_id', $recipient->id)
            ->when($activeFilter, fn ($query) => $query->where('status', $activeFilter))
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statusCounts = FoodRequest::query()
            ->where('recipient_id', $recipient->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openCount = (int) ($statusCounts[FoodRequestStatus::Open->value] ?? 0);
        $maxOpen = FoodRequest::MAX_OPEN_REQUESTS;
        $remainingOpenQuota = max(0, $maxOpen - $openCount);

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        return view('recipient.requests.index', compact(
            'requests',
            'statusCounts',
            'activeFilter',
            'openCount',
            'maxOpen',
            'remainingOpenQuota',
            'categories',
            'recipient',
        ));
    }

    /**
     * Store a newly broadcast food request from the recipient.
     */
    public function store(StoreFoodRequestRequest $request): RedirectResponse
    {
        FoodRequest::expireOverdue();

        $recipient = $this->recipientProfile($request);
        $validated = $request->validated();

        $openCount = FoodRequest::query()
            ->where('recipient_id', $recipient->id)
            ->where('status', FoodRequestStatus::Open->value)
            ->where('batas_waktu', '>', now())
            ->count();

        if ($openCount >= FoodRequest::MAX_OPEN_REQUESTS) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Batas permintaan terbuka tercapai. Anda hanya dapat memiliki maksimal '.FoodRequest::MAX_OPEN_REQUESTS.' permintaan aktif secara bersamaan.');
        }

        $urgency = UrgencyLevel::from($validated['urgency_level']);

        DB::transaction(function () use ($recipient, $validated, $urgency): void {
            $foodRequest = FoodRequest::create([
                'recipient_id' => $recipient->id,
                'category_id' => $validated['category_id'],
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'jumlah_porsi' => $validated['jumlah_porsi'],
                'urgency_level' => $urgency,
                'metode_penerimaan' => $validated['metode_penerimaan'],
                'batas_waktu' => $validated['batas_waktu'],
                'status' => FoodRequestStatus::Open,
            ]);

            $donorUserIds = User::query()
                ->where('role', Role::Donor->value)
                ->where('status_akun', AccountStatus::Aktif->value)
                ->pluck('id');

            $urgencyPrefix = $urgency === UrgencyLevel::Darurat
                ? '[DARURAT] '
                : ($urgency === UrgencyLevel::Mendesak ? '[Mendesak] ' : '');

            foreach ($donorUserIds as $donorUserId) {
                InAppNotification::create([
                    'user_id' => $donorUserId,
                    'judul' => $urgencyPrefix.'Kebutuhan Makanan Baru di Request Board',
                    'pesan' => sprintf(
                        '%s membutuhkan "%s" (%d porsi) sebelum %s.',
                        $recipient->nama_lembaga,
                        $foodRequest->judul,
                        $foodRequest->jumlah_porsi,
                        $foodRequest->batas_waktu->format('d/m/Y H:i'),
                    ),
                    'tipe' => 'request',
                ]);
            }
        });

        return redirect()
            ->route('recipient.requests.index')
            ->with('status', 'Permintaan kebutuhan makanan berhasil disiarkan ke Request Board dan notifikasi telah dikirim ke Donatur.');
    }

    /**
     * Mark an in-progress request as fulfilled (completed) by the recipient.
     */
    public function complete(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        $this->ensureOwnedByRecipient($request, $foodRequest);

        if (! in_array($foodRequest->status, [FoodRequestStatus::InProgress, FoodRequestStatus::Open], true)) {
            return redirect()
                ->route('recipient.requests.index')
                ->with('error', 'Permintaan ini sudah selesai atau tidak dapat ditandai terpenuhi.');
        }

        DB::transaction(function () use ($foodRequest): void {
            $locked = FoodRequest::lockForUpdate()->findOrFail($foodRequest->id);

            $locked->status = FoodRequestStatus::Fulfilled;
            $locked->save();

            if ($locked->claim_id !== null) {
                $claim = $locked->claim()->lockForUpdate()->first();
                if ($claim !== null && $claim->status !== 'completed') {
                    $claim->status = 'completed';
                    $claim->completed_at = now();
                    $claim->save();
                }
            }

            if ($locked->donor?->user_id) {
                InAppNotification::create([
                    'user_id' => $locked->donor->user_id,
                    'judul' => 'Kebutuhan Makanan Terpenuhi',
                    'pesan' => sprintf(
                        'Penerima (%s) telah mengonfirmasi bahwa kebutuhan "%s" telah terpenuhi. Terima kasih atas kontribusi Anda!',
                        $locked->recipient->nama_lembaga,
                        $locked->judul,
                    ),
                    'tipe' => 'request',
                ]);
            }
        });

        return redirect()
            ->route('recipient.requests.index')
            ->with('status', 'Permintaan makanan berhasil ditandai sebagai terpenuhi.');
    }

    /**
     * Cancel an open food request owned by the recipient.
     */
    public function cancel(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        $this->ensureOwnedByRecipient($request, $foodRequest);

        if ($foodRequest->status !== FoodRequestStatus::Open) {
            return redirect()
                ->route('recipient.requests.index')
                ->with('error', 'Hanya permintaan dengan status Terbuka yang dapat dibatalkan.');
        }

        $foodRequest->update([
            'status' => FoodRequestStatus::Cancelled,
        ]);

        return redirect()
            ->route('recipient.requests.index')
            ->with('status', 'Permintaan kebutuhan makanan telah dibatalkan.');
    }

    /**
     * Delete a request owned by the recipient (if not currently in progress or fulfilled).
     */
    public function destroy(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        $this->ensureOwnedByRecipient($request, $foodRequest);

        if (in_array($foodRequest->status, [FoodRequestStatus::InProgress, FoodRequestStatus::Fulfilled], true)) {
            return redirect()
                ->route('recipient.requests.index')
                ->with('error', 'Permintaan yang sedang diproses atau sudah terpenuhi tidak dapat dihapus.');
        }

        $foodRequest->delete();

        return redirect()
            ->route('recipient.requests.index')
            ->with('status', 'Permintaan kebutuhan makanan berhasil dihapus.');
    }

    /**
     * Get the authenticated recipient's profile.
     */
    private function recipientProfile(Request $request): RecipientProfile
    {
        return $request->user()->recipientProfile()->firstOrFail();
    }

    /**
     * Ensure the food request belongs to the authenticated recipient.
     */
    private function ensureOwnedByRecipient(Request $request, FoodRequest $foodRequest): void
    {
        $recipient = $request->user()->recipientProfile;

        abort_unless($recipient !== null && $foodRequest->recipient_id === $recipient->id, 404);
    }
}
