<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Support\Geo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class FoodCatalogController extends Controller
{
    /**
     * Display available food posts near the authenticated recipient.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'radius_km' => ['nullable', 'integer', 'in:5,10,25,50,100'],
        ]);

        $recipient = $this->recipientProfile($request);
        $recipientLocation = Geo::coordinate($recipient->lat, $recipient->lng);
        $search = trim($validated['q'] ?? '');
        $selectedCategoryId = isset($validated['category_id'])
            ? (int) $validated['category_id']
            : null;
        $requestedRadiusKm = isset($validated['radius_km'])
            ? (int) $validated['radius_km']
            : null;
        $selectedRadiusKm = $recipientLocation !== null ? $requestedRadiusKm : null;
        $effectiveRadiusKm = $selectedRadiusKm;

        $foodPosts = FoodPost::query()
            ->available()
            ->with(['category', 'donor'])
            ->when(
                $selectedCategoryId !== null,
                fn ($query, $categoryId) => $query->where('category_id', $categoryId),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('judul', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term)
                        ->orWhere('alamat', 'like', $term)
                        ->orWhereHas('donor', fn ($donorQuery) => $donorQuery->where('nama_usaha', 'like', $term))
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('nama_kategori', 'like', $term));
                });
            })
            ->orderBy('batas_konsumsi')
            ->orderBy('id')
            ->get()
            ->each(function (FoodPost $foodPost) use ($recipientLocation): void {
                $postLocation = Geo::coordinate($foodPost->lat, $foodPost->lng);
                $minutesLeft = max(1, (int) ceil(($foodPost->batas_konsumsi->getTimestamp() - now()->getTimestamp()) / 60));

                $foodPost->setAttribute(
                    'distance_km',
                    $recipientLocation !== null && $postLocation !== null
                        ? Geo::distanceKm($recipientLocation[0], $recipientLocation[1], $postLocation[0], $postLocation[1])
                        : null,
                );
                $foodPost->setAttribute('minutes_left', $minutesLeft);
                $foodPost->setAttribute('is_urgent', $minutesLeft <= 120);
                $foodPost->setAttribute('time_left_short', $this->timeLeftShort($minutesLeft));
            })
            ->when(
                $effectiveRadiusKm !== null,
                fn ($posts) => $posts->filter(
                    fn (FoodPost $foodPost) => $foodPost->distance_km !== null && $foodPost->distance_km <= $effectiveRadiusKm,
                ),
            )
            ->sort(function (FoodPost $left, FoodPost $right): int {
                $leftDistance = $left->getAttribute('distance_km') ?? PHP_FLOAT_MAX;
                $rightDistance = $right->getAttribute('distance_km') ?? PHP_FLOAT_MAX;

                return [
                    $leftDistance,
                    $left->batas_konsumsi->getTimestamp(),
                    $left->id,
                ] <=> [
                    $rightDistance,
                    $right->batas_konsumsi->getTimestamp(),
                    $right->id,
                ];
            })
            ->values();

        $availableCount = $foodPosts->count();

        $mapPosts = $foodPosts
            ->filter(fn (FoodPost $foodPost) => Geo::coordinate($foodPost->lat, $foodPost->lng) !== null)
            ->map(function (FoodPost $foodPost): array {
                $location = Geo::coordinate($foodPost->lat, $foodPost->lng);

                return [
                    'id' => $foodPost->id,
                    'judul' => $foodPost->judul,
                    'alamat' => $foodPost->alamat,
                    'donor' => $foodPost->donor?->nama_usaha,
                    'category' => $foodPost->category?->nama_kategori,
                    'porsi' => $foodPost->sisa_porsi,
                    'lat' => $location[0],
                    'lng' => $location[1],
                    'distance_km' => $foodPost->distance_km,
                    'time_left' => $foodPost->time_left_short,
                    'is_urgent' => $foodPost->is_urgent,
                ];
            })
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 9;

        $foodPosts = new LengthAwarePaginator(
            $foodPosts->forPage($page, $perPage)->values(),
            $availableCount,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        $dailyClaimedPortions = (int) Claim::query()
            ->where('recipient_id', $recipient->id)
            ->whereDate('tanggal_klaim', today())
            ->sum('jumlah_porsi');

        return view('recipient.catalog.index', compact(
            'availableCount',
            'categories',
            'dailyClaimedPortions',
            'effectiveRadiusKm',
            'foodPosts',
            'mapPosts',
            'recipient',
            'recipientLocation',
            'search',
            'selectedCategoryId',
            'selectedRadiusKm',
        ));
    }

    /**
     * Format a compact remaining-time label.
     */
    private function timeLeftShort(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? $hours.'j '.$remainingMinutes.'m'
            : $hours.'j';
    }

    /**
     * Get the authenticated recipient's profile.
     */
    private function recipientProfile(Request $request): RecipientProfile
    {
        return $request->user()->recipientProfile()->firstOrFail();
    }
}
