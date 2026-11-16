<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FoodPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminFoodPostRequest;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class FoodPostController extends Controller
{
    /**
     * Display a listing of all food posts for the Administrator.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:tersedia,habis,kedaluwarsa'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'donor_id' => ['nullable', 'integer', 'exists:donor_profiles,id'],
        ]);

        $search = trim($validated['q'] ?? '');
        $activeStatus = $validated['status'] ?? null;
        $selectedCategoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $selectedDonorId = isset($validated['donor_id']) ? (int) $validated['donor_id'] : null;

        $foodPosts = FoodPost::query()
            ->with(['category', 'donor'])
            ->withCount('claims')
            ->when($activeStatus !== null, fn ($query) => $query->where('status', $activeStatus))
            ->when($selectedCategoryId !== null, fn ($query) => $query->where('category_id', $selectedCategoryId))
            ->when($selectedDonorId !== null, fn ($query) => $query->where('donor_id', $selectedDonorId))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('judul', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term)
                        ->orWhere('alamat', 'like', $term)
                        ->orWhereHas('donor', fn ($d) => $d->where('nama_usaha', 'like', $term));
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statusCounts = FoodPost::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        $donors = DonorProfile::query()
            ->orderBy('nama_usaha')
            ->get();

        $pendingVerifications = User::query()
            ->pendingVerification()
            ->count();

        return view('admin.food-posts.index', compact(
            'foodPosts',
            'statusCounts',
            'categories',
            'donors',
            'search',
            'activeStatus',
            'selectedCategoryId',
            'selectedDonorId',
            'pendingVerifications',
        ));
    }

    /**
     * Show the form for creating a new food post as Administrator.
     */
    public function create(): View
    {
        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        $donors = DonorProfile::query()
            ->orderBy('nama_usaha')
            ->get();

        $pendingVerifications = User::query()
            ->pendingVerification()
            ->count();

        return view('admin.food-posts.create', compact('categories', 'donors', 'pendingVerifications'));
    }

    /**
     * Store a newly created food post in storage.
     */
    public function store(AdminFoodPostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $donor = DonorProfile::query()->findOrFail($validated['donor_id']);

        $foto = $request->file('foto')->store("food-posts/{$donor->id}", 'public');

        if ($foto === false) {
            throw new RuntimeException('Foto gagal diunggah.');
        }

        $jumlahPorsi = (int) $validated['jumlah_porsi'];
        $sisaPorsi = isset($validated['sisa_porsi']) ? (int) $validated['sisa_porsi'] : $jumlahPorsi;
        $status = isset($validated['status'])
            ? FoodPostStatus::from($validated['status'])
            : ($sisaPorsi === 0 ? FoodPostStatus::Habis : FoodPostStatus::Tersedia);

        try {
            FoodPost::create([
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'foto' => $foto,
                'jumlah_porsi' => $jumlahPorsi,
                'sisa_porsi' => $sisaPorsi,
                'alamat' => $validated['alamat'],
                'catatan_kurir' => $validated['catatan_kurir'] ?? null,
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'batas_konsumsi' => $validated['batas_konsumsi'],
                'status' => $status,
                'donor_id' => $donor->id,
                'category_id' => $validated['category_id'],
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($foto);

            throw $exception;
        }

        return redirect()
            ->route('admin.food-posts.index')
            ->with('status', 'Postingan makanan berhasil dibuat oleh Administrator.');
    }

    /**
     * Show the form for editing the specified food post.
     */
    public function edit(FoodPost $foodPost): View
    {
        $categories = FoodCategory::query()
            ->orderBy('nama_kategori')
            ->get();

        $donors = DonorProfile::query()
            ->orderBy('nama_usaha')
            ->get();

        $pendingVerifications = User::query()
            ->pendingVerification()
            ->count();

        return view('admin.food-posts.edit', compact('foodPost', 'categories', 'donors', 'pendingVerifications'));
    }

    /**
     * Update the specified food post in storage.
     */
    public function update(AdminFoodPostRequest $request, FoodPost $foodPost): RedirectResponse
    {
        $validated = $request->validated();
        $donor = DonorProfile::query()->findOrFail($validated['donor_id']);
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
                'jumlah_porsi' => (int) $validated['jumlah_porsi'],
                'sisa_porsi' => (int) $validated['sisa_porsi'],
                'alamat' => $validated['alamat'],
                'catatan_kurir' => $validated['catatan_kurir'] ?? null,
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'batas_konsumsi' => $validated['batas_konsumsi'],
                'status' => FoodPostStatus::from($validated['status']),
                'donor_id' => $donor->id,
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
            ->route('admin.food-posts.index')
            ->with('status', 'Postingan makanan berhasil diperbarui oleh Administrator.');
    }

    /**
     * Remove the specified food post from storage.
     */
    public function destroy(FoodPost $foodPost): RedirectResponse
    {
        if ($foodPost->claims()->exists()) {
            return redirect()
                ->route('admin.food-posts.index')
                ->with('error', 'Postingan makanan tidak dapat dihapus karena sudah memiliki klaim.');
        }

        $donorUserId = $foodPost->donor?->user_id;
        $judul = $foodPost->judul;
        $foto = $foodPost->foto;

        $foodPost->delete();

        if ($foto) {
            Storage::disk('public')->delete($foto);
        }

        if ($donorUserId) {
            InAppNotification::create([
                'user_id' => $donorUserId,
                'judul' => 'Postingan Makanan Dihapus oleh Administrator',
                'pesan' => sprintf('Postingan makanan "%s" milik usaha Anda telah dihapus oleh Administrator.', $judul),
                'tipe' => 'post',
            ]);
        }

        return redirect()
            ->route('admin.food-posts.index')
            ->with('status', 'Postingan makanan berhasil dihapus.');
    }
}
