<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCategoryRequest;
use App\Models\FoodCategory;
use App\Models\User;
use App\Services\Admin\CategoryManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryManagementService $categories) {}

    /**
     * Display all nutrition categories with their usage count.
     */
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => $this->categories->allWithPostCounts(),
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): View
    {
        return view('admin.categories.create', [
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(AdminCategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Kategori "'.$category->nama_kategori.'" berhasil dibuat.');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(FoodCategory $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Update a category.
     */
    public function update(AdminCategoryRequest $request, FoodCategory $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove an unused category.
     */
    public function destroy(FoodCategory $category): RedirectResponse
    {
        try {
            $this->categories->delete($category);
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Kategori berhasil dihapus.');
    }
}
