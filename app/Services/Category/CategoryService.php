<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * Liste toutes les catégories
     */
    public function getAllCategories(array $filters = [])
    {
        $query = Category::withCount('products')
            ->orderBy('name', 'asc');

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($subQuery) use ($term) {
                $subQuery->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return $query->paginate($perPage);
    }

    /**
     * Récupère une catégorie par son ID
     */
    public function getCategoryById(int $id)
    {
        $category = Category::withCount('products')->find($id);

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => ['Catégorie non trouvée.'],
            ]);
        }

        return $category;
    }

    /**
     * Récupère une catégorie par son slug
     */
    public function getCategoryBySlug(string $slug)
    {
        $category = Category::where('slug', $slug)
            ->withCount('products')
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => ['Catégorie non trouvée.'],
            ]);
        }

        return $category;
    }

    /**
     * Crée une nouvelle catégorie (Admin uniquement)
     */
    public function createCategory(array $data, User $user)
    {
        // Vérifier que l'utilisateur est admin
        if (! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent créer des catégories.'],
            ]);
        }

        // Créer la catégorie
        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        return $category;
    }

    /**
     * Met à jour une catégorie (Admin uniquement)
     */
    public function updateCategory(int $id, array $data, User $user)
    {
        // Vérifier que l'utilisateur est admin
        if (! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent modifier des catégories.'],
            ]);
        }

        $category = Category::find($id);

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => ['Catégorie non trouvée.'],
            ]);
        }

        // Si le nom change, mettre à jour le slug
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Mettre à jour
        $category->update($data);

        return $category->load('products');
    }

    /**
     * Supprime une catégorie (Admin uniquement)
     */
    public function deleteCategory(int $id, User $user)
    {
        // Vérifier que l'utilisateur est admin
        if (! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent supprimer des catégories.'],
            ]);
        }

        $category = Category::find($id);

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => ['Catégorie non trouvée.'],
            ]);
        }

        // Vérifier si la catégorie a des produits
        if ($category->products()->count() > 0) {
            throw ValidationException::withMessages([
                'category' => ['Impossible de supprimer une catégorie contenant des produits.'],
            ]);
        }

        $category->delete();

        return [
            'message' => 'Catégorie supprimée avec succès',
        ];
    }

    /**
     * Récupère les produits d'une catégorie
     */
    public function getCategoryProducts(int $id, array $filters = [])
    {
        $category = Category::find($id);

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => ['Catégorie non trouvée.'],
            ]);
        }

        $query = $category->products()
            ->with(['vendeur.user', 'category'])
            ->where('is_active', true);

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'updated_at', 'name', 'price', 'stock'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min(max((int) ($filters['per_page'] ?? 12), 1), 100);

        return $query->paginate($perPage);
    }
}
