<?php

namespace App\Http\Controllers\Donor;

use App\Enums\FoodPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Donor\FoodPostRequest;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class FoodPostController extends Controller
{
    /**
     * Display the donor's food posts.
     */
    public function index(Request $request): View
    {
        $donor = $this->donorProfile($request);

        $foodPosts = FoodPost::query()
            ->with('category')
            ->where('donor_id', $donor->id)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('donor.food-posts.index', compact('foodPosts'));
    }

    /**
     * Show the form for creating a new food post.
     */
    public function create(Request $request): View
    {
        $donor = $this->donorProfile($request);

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        return view('donor.food-posts.create', compact('categories', 'donor'));
    }

    /**
     * Store a newly created food post.
     */
    public function store(FoodPostRequest $request): RedirectResponse
    {
        $donor = $this->donorProfile($request);
        $validated = $request->validated();

        $foto = $request->file('foto')->store("food-posts/{$donor->id}", 'public');

        if ($foto === false) {
            throw new RuntimeException('Foto gagal diunggah.');
        }

        try {
            FoodPost::create([
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'foto' => $foto,
                'jumlah_porsi' => $validated['jumlah_porsi'],
                'sisa_porsi' => $validated['jumlah_porsi'],
                'alamat' => $validated['alamat'],
                'catatan_kurir' => $validated['catatan_kurir'] ?? null,
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'batas_konsumsi' => $validated['batas_konsumsi'],
                'status' => FoodPostStatus::Tersedia,
                'donor_id' => $donor->id,
                'category_id' => $validated['category_id'],
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($foto);

            throw $exception;
        }

        return redirect()
            ->route('donor.food-posts.index')
            ->with('status', 'Postingan makanan berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified food post.
     */
    public function edit(Request $request, FoodPost $foodPost): View
    {
        $this->ensureOwnedByDonor($request, $foodPost);

        $donor = $this->donorProfile($request);

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        return view('donor.food-posts.edit', compact('foodPost', 'categories', 'donor'));
    }

    /**
     * Update the specified food post.
     */
    public function update(FoodPostRequest $request, FoodPost $foodPost): RedirectResponse
    {
        $this->ensureOwnedByDonor($request, $foodPost);

        $donor = $this->donorProfile($request);
        $validated = $request->validated();
        $oldFoto = $foodPost->foto;

        $newFoto = null;

        if ($request->hasFile('foto')) {
            $newFoto = $request->file('foto')->store("food-posts/{$donor->id}", 'public');

            if ($newFoto === false) {
                throw new RuntimeException('Foto gagal diunggah.');
            }
        }

        try {
            $foodPost->update([
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'foto' => $newFoto ?? $oldFoto,
                'jumlah_porsi' => $validated['jumlah_porsi'],
                'sisa_porsi' => $validated['jumlah_porsi'],
                'alamat' => $validated['alamat'],
                'catatan_kurir' => $validated['catatan_kurir'] ?? null,
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'batas_konsumsi' => $validated['batas_konsumsi'],
                'category_id' => $validated['category_id'],
            ]);
        } catch (Throwable $exception) {
            if ($newFoto !== null) {
                Storage::disk('public')->delete($newFoto);
            }

            throw $exception;
        }

        if ($newFoto !== null) {
            Storage::disk('public')->delete($oldFoto);
        }

        return redirect()
            ->route('donor.food-posts.index')
            ->with('status', 'Postingan makanan berhasil diperbarui.');
    }

    /**
     * Remove the specified food post.
     */
    public function destroy(Request $request, FoodPost $foodPost): RedirectResponse
    {
        $this->ensureOwnedByDonor($request, $foodPost);

        if ($foodPost->claims()->exists()) {
            return redirect()
                ->route('donor.food-posts.index')
                ->with('error', 'Postingan makanan tidak dapat dihapus karena sudah memiliki klaim.');
        }

        $foto = $foodPost->foto;

        $foodPost->delete();

        Storage::disk('public')->delete($foto);

        return redirect()
            ->route('donor.food-posts.index')
            ->with('status', 'Postingan makanan berhasil dihapus.');
    }

    /**
     * Get the authenticated donor's profile.
     */
    private function donorProfile(Request $request): DonorProfile
    {
        return $request->user()->donorProfile()->firstOrFail();
    }

    /**
     * Ensure the food post belongs to the authenticated donor.
     */
    private function ensureOwnedByDonor(Request $request, FoodPost $foodPost): void
    {
        $donor = $request->user()->donorProfile;

        abort_unless($donor !== null && $foodPost->donor_id === $donor->id, 404);
    }
}
