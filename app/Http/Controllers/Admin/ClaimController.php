<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminClaimRequest;
use App\Models\Claim;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\Admin\ClaimManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function __construct(private readonly ClaimManagementService $claims) {}

    /**
     * Display all claims on the platform.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:menunggu,disetujui,selesai,ditolak'],
        ]);

        $search = trim($validated['q'] ?? '');
        $activeStatus = $validated['status'] ?? null;

        $claims = $this->claims->paginate([
            'q' => $search,
            'status' => $activeStatus,
        ]);

        return view('admin.claims.index', [
            'claims' => $claims,
            'statusCounts' => $this->claims->statusCounts(),
            'search' => $search,
            'activeStatus' => $activeStatus,
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Show the form for creating a claim on behalf of a recipient.
     */
    public function create(): View
    {
        return view('admin.claims.create', [
            'recipients' => RecipientProfile::query()
                ->with('user')
                ->orderBy('nama_lembaga')
                ->get(),
            'foodPosts' => FoodPost::query()
                ->available()
                ->with('donor')
                ->orderBy('batas_konsumsi')
                ->get(),
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Store a claim submitted by the administrator.
     */
    public function store(AdminClaimRequest $request): RedirectResponse
    {
        try {
            $this->claims->create($request->validated());
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.claims.index')
            ->with('status', 'Klaim berhasil dibuat dan menunggu keputusan donor.');
    }

    /**
     * Show the form for editing a claim.
     */
    public function edit(Claim $claim): View
    {
        $claim->load(['post.donor', 'recipient.user']);

        return view('admin.claims.edit', [
            'claim' => $claim,
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Update a pending claim and optionally approve or reject it.
     */
    public function update(AdminClaimRequest $request, Claim $claim): RedirectResponse
    {
        try {
            $this->claims->update($claim, $request->validated());
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.claims.index')
            ->with('status', 'Klaim berhasil diperbarui.');
    }

    /**
     * Remove a claim that is not completed.
     */
    public function destroy(Claim $claim): RedirectResponse
    {
        try {
            $this->claims->delete($claim);
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.claims.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.claims.index')
            ->with('status', 'Klaim berhasil dihapus.');
    }
}
