<?php

namespace App\Http\Controllers\Donor;

use App\Enums\FoodPostStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\UrgencyLevel;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\FoodRequest;
use App\Models\InAppNotification;
use App\Support\Geo;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RequestBoardController extends Controller
{
    /**
     * Browse active food requests on the shared Request Board for verified Donors.
     */
    public function index(Request $request): View
    {
        FoodRequest::expireOverdue();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'urgency' => ['nullable', 'string', 'in:normal,mendesak,darurat'],
            'view' => ['nullable', 'string', 'in:open,mine'],
        ]);

        $donor = $this->donorProfile($request);
        $donorLocation = Geo::coordinate($donor->lat, $donor->lng);

        $search = trim($validated['q'] ?? '');
        $selectedCategoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $selectedUrgency = $validated['urgency'] ?? null;
        $viewMode = $validated['view'] ?? 'open';

        $baseQuery = FoodRequest::query()
            ->with(['recipient', 'category', 'donor', 'claim.post']);

        if ($viewMode === 'mine') {
            $baseQuery->where('donor_id', $donor->id);
        } else {
            $baseQuery->openBoard();
        }

        $foodRequests = $baseQuery
            ->when($selectedCategoryId !== null, fn ($query) => $query->where('category_id', $selectedCategoryId))
            ->when($selectedUrgency !== null, fn ($query) => $query->where('urgency_level', $selectedUrgency))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('judul', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term)
                        ->orWhereHas('recipient', fn ($r) => $r->where('nama_lembaga', 'like', $term)->orWhere('alamat', 'like', $term));
                });
            })
            ->orderByRaw("CASE WHEN urgency_level = 'darurat' THEN 0 WHEN urgency_level = 'mendesak' THEN 1 ELSE 2 END")
            ->orderBy('batas_waktu')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        $foodRequests->getCollection()->each(function (FoodRequest $item) use ($donorLocation): void {
            $recipientLocation = Geo::coordinate($item->recipient?->lat, $item->recipient?->lng);

            $item->setAttribute(
                'distance_km',
                $donorLocation !== null && $recipientLocation !== null
                    ? Geo::distanceKm($donorLocation[0], $donorLocation[1], $recipientLocation[0], $recipientLocation[1])
                    : null,
            );
        });

        $availablePosts = FoodPost::query()
            ->available()
            ->with('category')
            ->where('donor_id', $donor->id)
            ->orderByDesc('sisa_porsi')
            ->orderBy('batas_konsumsi')
            ->get();

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        $openBoardCount = FoodRequest::query()->openBoard()->count();
        $urgentOpenCount = FoodRequest::query()
            ->openBoard()
            ->whereIn('urgency_level', [UrgencyLevel::Darurat->value, UrgencyLevel::Mendesak->value])
            ->count();
        $myCommitmentsCount = FoodRequest::query()
            ->where('donor_id', $donor->id)
            ->count();

        return view('donor.requests.index', compact(
            'foodRequests',
            'availablePosts',
            'categories',
            'donor',
            'search',
            'selectedCategoryId',
            'selectedUrgency',
            'viewMode',
            'openBoardCount',
            'urgentOpenCount',
            'myCommitmentsCount',
        ));
    }

    /**
     * Fulfil an open food request using an existing FoodPost (creating an approved Claim)
     * or by committing directly as the fulfilling Donor.
     */
    public function fulfil(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        FoodRequest::expireOverdue();

        $donor = $this->donorProfile($request);

        $validated = $request->validate([
            'post_id' => ['nullable', 'integer', 'exists:food_posts,id'],
            'jumlah_porsi' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($donor, $foodRequest, $validated): void {
                $lockedRequest = FoodRequest::query()
                    ->with('recipient')
                    ->lockForUpdate()
                    ->findOrFail($foodRequest->id);

                if ($lockedRequest->isPastDeadline()) {
                    $lockedRequest->status = FoodRequestStatus::Expired;
                    $lockedRequest->save();

                    throw new DomainException('Permintaan ini sudah melewati batas waktu dan telah kedaluwarsa.');
                }

                if ($lockedRequest->status !== FoodRequestStatus::Open) {
                    throw new DomainException('Permintaan ini sudah diproses atau tidak lagi terbuka.');
                }

                $claim = null;

                if (! empty($validated['post_id'])) {
                    $foodPost = FoodPost::query()
                        ->lockForUpdate()
                        ->where('donor_id', $donor->id)
                        ->findOrFail($validated['post_id']);

                    if ($foodPost->batas_konsumsi && $foodPost->batas_konsumsi->isPast()) {
                        throw new DomainException('Postingan makanan yang dipilih sudah melewati batas konsumsi.');
                    }

                    if ($foodPost->sisa_porsi <= 0) {
                        throw new DomainException('Stok porsi pada postingan makanan yang dipilih sudah habis.');
                    }

                    $portionsToAllocate = isset($validated['jumlah_porsi'])
                        ? (int) $validated['jumlah_porsi']
                        : min($lockedRequest->jumlah_porsi, $foodPost->sisa_porsi);

                    if ($portionsToAllocate > $foodPost->sisa_porsi) {
                        throw new DomainException('Jumlah porsi melebihi sisa porsi postingan makanan yang dipilih.');
                    }

                    $foodPost->sisa_porsi -= $portionsToAllocate;
                    if ($foodPost->sisa_porsi === 0) {
                        $foodPost->status = FoodPostStatus::Habis;
                    }
                    $foodPost->save();

                    $metode = $lockedRequest->metode_penerimaan === 'diantar_kurir'
                        ? 'kurir_online'
                        : 'ambil_sendiri';

                    $claim = Claim::create([
                        'post_id' => $foodPost->id,
                        'recipient_id' => $lockedRequest->recipient_id,
                        'jumlah_porsi' => $portionsToAllocate,
                        'status' => 'approved',
                        'metode_pengambilan' => $metode,
                        'kode_verifikasi' => Str::upper(Str::random(6)),
                        'tanggal_klaim' => today(),
                        'approved_at' => now(),
                    ]);
                }

                $lockedRequest->donor_id = $donor->id;
                $lockedRequest->claim_id = $claim?->id;
                $lockedRequest->status = FoodRequestStatus::InProgress;
                $lockedRequest->save();

                if ($lockedRequest->recipient?->user_id) {
                    $message = $claim !== null
                        ? sprintf(
                            'Donatur %s memenuhi permintaan "%s" melalui postingan "%s" (%d porsi). Klaim otomatis disetujui (Kode: %s).',
                            $donor->nama_usaha,
                            $lockedRequest->judul,
                            $claim->post->judul,
                            $claim->jumlah_porsi,
                            $claim->kode_verifikasi,
                        )
                        : sprintf(
                            'Donatur %s berkomitmen untuk memenuhi permintaan kebutuhan "%s" (%d porsi) Anda.',
                            $donor->nama_usaha,
                            $lockedRequest->judul,
                            $lockedRequest->jumlah_porsi,
                        );

                    InAppNotification::create([
                        'user_id' => $lockedRequest->recipient->user_id,
                        'judul' => 'Permintaan Makanan Anda Direspons Donatur',
                        'pesan' => $message,
                        'tipe' => 'request',
                    ]);
                }
            });
        } catch (DomainException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('donor.requests.index', ['view' => 'mine'])
            ->with('status', 'Terima kasih! Anda berhasil merespons kebutuhan makanan ini. Status permintaan kini Sedang Diproses.');
    }

    /**
     * Allow the fulfilling Donor to mark the request (and its associated claim) as Fulfilled.
     */
    public function complete(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        $donor = $this->donorProfile($request);

        abort_unless($foodRequest->donor_id === $donor->id, 403);

        if ($foodRequest->status !== FoodRequestStatus::InProgress) {
            return redirect()
                ->back()
                ->with('error', 'Hanya permintaan yang sedang diproses yang dapat ditandai terpenuhi.');
        }

        DB::transaction(function () use ($foodRequest, $donor): void {
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

            if ($locked->recipient?->user_id) {
                InAppNotification::create([
                    'user_id' => $locked->recipient->user_id,
                    'judul' => 'Kebutuhan Makanan Telah Diserahkan',
                    'pesan' => sprintf(
                        'Donatur %s menandai pemenuhan kebutuhan "%s" sebagai selesai/terpenuhi.',
                        $donor->nama_usaha,
                        $locked->judul,
                    ),
                    'tipe' => 'request',
                ]);
            }
        });

        return redirect()
            ->route('donor.requests.index', ['view' => 'mine'])
            ->with('status', 'Permintaan kebutuhan makanan berhasil ditandai sebagai Terpenuhi.');
    }

    /**
     * Get the authenticated donor's profile.
     */
    private function donorProfile(Request $request): DonorProfile
    {
        return $request->user()->donorProfile()->firstOrFail();
    }
}
