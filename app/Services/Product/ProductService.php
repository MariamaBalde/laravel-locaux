<?php

namespace App\Services\Product;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    private function extractPublicStoragePath(?string $imageUrl): ?string
    {
        if (! $imageUrl || ! is_string($imageUrl)) {
            return null;
        }

        $parsedPath = parse_url($imageUrl, PHP_URL_PATH);
        $path = is_string($parsedPath) ? $parsedPath : $imageUrl;
        $path = str_replace('\\', '/', $path);

        $prefix = '/storage/';
        $position = strpos($path, $prefix);
        if ($position === false) {
            return null;
        }

        return ltrim(substr($path, $position + strlen($prefix)), '/');
    }

    private function uploadProductImages(array $images): array
    {
        $imageUrls = [];
        foreach ($images as $image) {
            $path = $image->store('products', 'public');
            $imageUrls[] = '/storage/'.ltrim($path, '/');
        }

        return $imageUrls;
    }

    private function resolveFinalImageUrls(array $validated): array
    {
        $imageUrls = [];

        if (isset($validated['image_urls']) && is_array($validated['image_urls'])) {
            $imageUrls = array_merge($imageUrls, $validated['image_urls']);
        }

        if (isset($validated['images']) && is_array($validated['images'])) {
            $imageUrls = array_merge($imageUrls, $this->uploadProductImages($validated['images']));
        }

        $imageUrls = array_values(array_unique(array_filter($imageUrls)));

        if (count($imageUrls) > 5) {
            throw ValidationException::withMessages([
                'images' => ['Vous pouvez envoyer jusqu\'à 5 images maximum.'],
            ]);
        }

        return $imageUrls;
    }

    private function ensureVerifiedVendor(User $user): void
    {
        if (! $user->isVendeur()) {
            throw ValidationException::withMessages([
                'role' => ['Seuls les vendeurs peuvent accéder à cette ressource.'],
            ]);
        }

        if (! $user->vendeur || ! $user->vendeur->verified) {
            throw ValidationException::withMessages([
                'verified' => ['Votre compte vendeur doit être vérifié par un administrateur.'],
            ]);
        }
    }

    public function getAllProducts(array $filters = [])
    {
        $query = Product::with(['vendeur.user', 'category'])
            ->where('is_active', true);

        // Filtre par catégorie
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtre par vendeur
        if (isset($filters['vendeur_id'])) {
            $query->where('vendeur_id', $filters['vendeur_id']);
        }

        // Filtre par prix minimum
        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        // Filtre par prix maximum
        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        // Filtre par stock disponible uniquement
        if (isset($filters['in_stock']) && $filters['in_stock'] == true) {
            $query->where('stock', '>', 0);
        }

        // Recherche avancée (nom + description)
        if (isset($filters['search'])) {
            $searchTerm = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm);
            });
        }

        // Filtre par poids (livraison)
        if (isset($filters['weight_max'])) {
            $query->where('weight', '<=', $filters['weight_max']);
        }

        // Filtre par date de création
        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (isset($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        // Valider les colonnes de tri
        $validSortColumns = ['created_at', 'price', 'name', 'stock', 'updated_at'];
        if (! in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }

        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($filters['per_page'] ?? 12, 100);

        return $query->paginate($perPage);
    }

    public function getProductById(int $id)
    {
        $product = Product::with(['vendeur.user', 'category', 'creator'])
            ->find($id);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        return $product;
    }

    public function createProduct(array $data, User $user)
    {
        $this->ensureVerifiedVendor($user);

        $imageUrls = $this->resolveFinalImageUrls($data);

        // Créer le produit
        $product = Product::create([
            'vendeur_id' => $user->vendeur->id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'weight' => $data['weight'] ?? null,
            'images' => $imageUrls,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $user->id,
        ]);

        return $product->load(['vendeur.user', 'category']);
    }

    public function updateProduct(int $id, array $data, User $user)
    {
        $product = Product::find($id);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        // Vérifier les permissions
        if (! $user->isAdmin() && $product->vendeur_id !== $user->vendeur?->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à modifier ce produit.'],
            ]);
        }

        if (! $user->isAdmin()) {
            $this->ensureVerifiedVendor($user);
        }

        $validated = $data;
        $wantsToReplaceImages = isset($validated['images']) || isset($validated['image_urls']);
        if ($wantsToReplaceImages) {
            // Supprimer les anciennes images
            if ($product->images && is_array($product->images)) {
                foreach ($product->images as $oldImage) {
                    if ($oldImage) {
                        $oldPath = $this->extractPublicStoragePath($oldImage);
                        if ($oldPath) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                }
            }

            $validated['images'] = $this->resolveFinalImageUrls($validated);
        }

        // Ajouter updated_by
        $validated['updated_by'] = $user->id;

        // Mettre à jour le produit
        $product->update($validated);

        return $product->load(['vendeur.user', 'category']);
    }

    public function addProductImages(int $id, array $data, User $user)
    {
        $product = Product::find($id);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        if (! $user->isAdmin() && $product->vendeur_id !== $user->vendeur?->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à modifier ce produit.'],
            ]);
        }

        if (! $user->isAdmin()) {
            $this->ensureVerifiedVendor($user);
        }

        $newUrls = $this->resolveFinalImageUrls($data);
        $existing = is_array($product->images) ? $product->images : [];
        $final = array_values(array_unique(array_merge($existing, $newUrls)));

        if (count($final) > 5) {
            throw ValidationException::withMessages([
                'images' => ['Maximum 5 images par produit.'],
            ]);
        }

        $product->update([
            'images' => $final,
            'updated_by' => $user->id,
        ]);

        return $product->load(['vendeur.user', 'category']);
    }

    public function createProductForVendor(array $data, User $admin)
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent créer un produit pour un vendeur.'],
            ]);
        }

        $vendeur = Vendeur::with('user')->find($data['vendeur_id']);
        if (! $vendeur || ! $vendeur->user || ! $vendeur->user->isVendeur()) {
            throw ValidationException::withMessages([
                'vendeur_id' => ['Le vendeur sélectionné est invalide.'],
            ]);
        }

        if (! $vendeur->verified && ($data['is_active'] ?? true) === true) {
            throw ValidationException::withMessages([
                'is_active' => ['Un vendeur non vérifié ne peut pas publier un produit actif.'],
            ]);
        }

        $imageUrls = $this->resolveFinalImageUrls($data);

        $isActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : (bool) $vendeur->verified;

        $product = Product::create([
            'vendeur_id' => $vendeur->id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'weight' => $data['weight'] ?? null,
            'images' => $imageUrls,
            'is_active' => $isActive,
            'created_by' => $admin->id,
        ]);

        return $product->load(['vendeur.user', 'category']);
    }

    public function deleteProduct(int $id, User $user)
    {
        $product = Product::find($id);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        // Vérifier les permissions
        if (! $user->isAdmin() && $product->vendeur_id !== $user->vendeur?->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à supprimer ce produit.'],
            ]);
        }

        if (! $user->isAdmin()) {
            $this->ensureVerifiedVendor($user);
        }

        // Empêche la suppression d'un produit impliqué dans des commandes en attente de traitement.
        $activeOrderNumbers = OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['pending']);
            })
            ->with('order:id,order_number')
            ->get()
            ->pluck('order.order_number')
            ->filter()
            ->unique()
            ->values();

        if ($activeOrderNumbers->isNotEmpty()) {
            $count = $activeOrderNumbers->count();
            $preview = $activeOrderNumbers->take(3)->implode(', ');
            if ($count > 3) {
                $preview .= ', ...';
            }

            throw ValidationException::withMessages([
                'product' => ["Impossible de supprimer ce produit: {$count} commande(s) active(s) liée(s) ({$preview})."],
            ]);
        }

        // Supprimer les images du storage
        if ($product->images) {
            foreach ($product->images as $image) {
                $path = $this->extractPublicStoragePath($image);
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // Supprimer le produit
        $product->delete();

        return [
            'message' => 'Produit supprimé avec succès',
        ];
    }

    public function toggleProductStatus(int $id, User $user)
    {
        $product = Product::find($id);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        // Vérifier les permissions
        if (! $user->isAdmin() && $product->vendeur_id !== $user->vendeur?->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à modifier ce produit.'],
            ]);
        }

        if (! $user->isAdmin()) {
            $this->ensureVerifiedVendor($user);
        }

        // Inverser le statut
        $product->update([
            'is_active' => ! $product->is_active,
            'updated_by' => $user->id,
        ]);

        return $product->load(['vendeur.user', 'category']);
    }

    public function getVendorProducts(User $user, array $filters = [])
    {
        $this->ensureVerifiedVendor($user);

        $query = Product::with(['category'])
            ->withCount([
                'orderItems as active_orders_count' => function ($query) {
                    $query->whereHas('order', function ($orderQuery) {
                        $orderQuery->whereIn('status', ['pending']);
                    });
                },
            ])
            ->where('vendeur_id', $user->vendeur->id);

        // Filtre par statut (actif/inactif)
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Recherche
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $validSortColumns = ['created_at', 'updated_at', 'name', 'price', 'stock', 'is_active'];
        if (! in_array($sortBy, $validSortColumns, true)) {
            $sortBy = 'created_at';
        }
        if (! in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return $query->paginate($perPage);
    }

    public function updateStock(int $id, int $quantity, User $user)
    {
        $product = Product::find($id);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        // Vérifier les permissions
        if (! $user->isAdmin() && $product->vendeur_id !== $user->vendeur?->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à modifier ce produit.'],
            ]);
        }

        if (! $user->isAdmin()) {
            $this->ensureVerifiedVendor($user);
        }

        // Valider la quantité
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['La quantité ne peut pas être négative.'],
            ]);
        }

        // Mettre à jour le stock
        $product->update([
            'stock' => $quantity,
            'updated_by' => $user->id,
        ]);

        return $product->load(['vendeur.user', 'category']);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * RECHERCHE AVANCÉE DE PRODUITS
     * ═══════════════════════════════════════════════════════
     */
    public function searchProducts(array $filters = [])
    {
        $query = Product::with(['vendeur.user', 'category'])
            ->where('is_active', true);

        // ══════════════════════════════════════════
        // RECHERCHE PAR MOT-CLÉ (nom, description)
        // ══════════════════════════════════════════
        if (isset($filters['q']) && ! empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        }

        // ══════════════════════════════════════════
        // FILTRES
        // ══════════════════════════════════════════

        // Catégorie(s) - supporte plusieurs catégories
        if (isset($filters['categories'])) {
            $categories = is_array($filters['categories'])
                ? $filters['categories']
                : explode(',', $filters['categories']);
            $query->whereIn('category_id', $categories);
        }

        // Vendeur
        if (isset($filters['vendeur_id'])) {
            $query->where('vendeur_id', $filters['vendeur_id']);
        }

        // Prix minimum
        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        // Prix maximum
        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        // Stock disponible uniquement
        if (isset($filters['in_stock']) && $filters['in_stock'] == 'true') {
            $query->where('stock', '>', 0);
        }

        // Poids minimum
        if (isset($filters['weight_min'])) {
            $query->where('weight', '>=', $filters['weight_min']);
        }

        // Poids maximum
        if (isset($filters['weight_max'])) {
            $query->where('weight', '<=', $filters['weight_max']);
        }

        // ══════════════════════════════════════════
        // TRI
        // ══════════════════════════════════════════

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        // Tris personnalisés
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        // ══════════════════════════════════════════
        // PAGINATION
        // ══════════════════════════════════════════

        $perPage = $filters['per_page'] ?? 12;

        return $query->paginate($perPage);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * NOUVEAUTÉS - Produits récemment ajoutés
     * ═══════════════════════════════════════════════════════
     */
    public function getNewProducts(int $limit = 8)
    {
        return Product::with(['vendeur.user', 'category'])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
