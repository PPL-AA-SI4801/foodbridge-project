<?php

namespace App\Services\Admin;

use App\Models\FoodCategory;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Business rules for nutrition category management.
 */
class CategoryManagementService
{
    /**
     * List categories with the number of food posts that use each one.
     *
     * @return Collection<int, FoodCategory>
     */
    public function allWithPostCounts(): Collection
    {
        return FoodCategory::query()
            ->withCount('foodPosts')
            ->orderBy('nama_kategori')
            ->get();
    }

    /**
     * @param  array{nama_kategori: string}  $data
     */
    public function create(array $data): FoodCategory
    {
        return FoodCategory::create($data);
    }

    /**
     * @param  array{nama_kategori: string}  $data
     */
    public function update(FoodCategory $category, array $data): FoodCategory
    {
        $category->update($data);

        return $category->refresh();
    }

    /**
     * Delete a category only when no food post uses it.
     *
     * @throws DomainException when the category is still in use.
     */
    public function delete(FoodCategory $category): void
    {
        if ($category->foodPosts()->exists()) {
            throw new DomainException('Kategori tidak dapat dihapus karena masih digunakan oleh postingan makanan.');
        }

        $category->delete();
    }
}
