<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Http\Requests\UploadProductImagesRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Product\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Liste tous les produits (public)
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $paginated = $this->productService->getAllProducts($filters);

            return response()->json([
                'success' => true,
                'data' => new ProductCollection($paginated),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
            ], 500);
        }
    }

    /**
     * Affiche un produit spécifique (public)
     * GET /api/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
                'errors' => $e->errors(),
            ], 404);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du produit',
            ], 500);
        }
    }

    /**
     * Crée un nouveau produit (vendeur uniquement)
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Product::class);
            $product = $this->productService->createProduct(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => new ProductResource($product),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du produit',
            ], 500);
        }
    }

    /**
     * Met à jour un produit (vendeur/admin)
     * PUT/PATCH /api/products/{id}
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $productModel = Product::find($id);
            if ($productModel) {
                $this->authorize('update', $productModel);
            }
            $product = $this->productService->updateProduct(
                $id,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => new ProductResource($product),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du produit',
            ], 500);
        }
    }

    /**
     * Supprime un produit (vendeur/admin)
     * DELETE /api/products/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $productModel = Product::find($id);
            if ($productModel) {
                $this->authorize('delete', $productModel);
            }
            $result = $this->productService->deleteProduct(
                $id,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du produit',
            ], 500);
        }
    }

    /**
     * Active/Désactive un produit (vendeur/admin)
     * PATCH /api/products/{id}/toggle
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        try {
            $productModel = Product::find($id);
            if ($productModel) {
                $this->authorize('toggle', $productModel);
            }
            $product = $this->productService->toggleProductStatus(
                $id,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Statut du produit modifié',
                'data' => new ProductResource($product),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du statut',
            ], 500);
        }
    }

    /**
     * Liste les produits du vendeur connecté
     * GET /api/vendeur/products
     */
    public function myProducts(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $paginated = $this->productService->getVendorProducts(
                $request->user(),
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => new ProductCollection($paginated),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => $e->errors(),
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
            ], 500);
        }
    }

    /**
     * Met à jour le stock d'un produit
     * PATCH /api/products/{id}/stock
     */
    public function updateStock(UpdateStockRequest $request, int $id): JsonResponse
    {
        try {
            $productModel = Product::find($id);
            if ($productModel) {
                $this->authorize('updateStock', $productModel);
            }
            $validated = $request->validated();

            $product = $this->productService->updateStock(
                $id,
                (int) $validated['quantity'],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour',
                'data' => new ProductResource($product),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du stock',
            ], 500);
        }
    }

    /**
     * Recherche avancée de produits
     * GET /api/products/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $products = $this->productService->searchProducts($filters);

            return response()->json([
                'success' => true,
                'data' => new ProductCollection($products),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
            ], 500);
        }
    }

    /**
     * Nouveautés - Produits récemment ajoutés
     * GET /api/products/new
     */
    public function newest(Request $request): JsonResponse
    {
        try {
            $limit = $request->limit ?? 8;
            $products = $this->productService->getNewProducts($limit);

            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($products),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des nouveautés',
            ], 500);
        }
    }

    /**
     * Upload/ajout d'images produit (vendeur)
     * POST /api/seller/products/{id}/images
     */
    public function uploadImages(UploadProductImagesRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->addProductImages(
                $id,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Images mises à jour avec succès',
                'data' => new ProductResource($product),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des images',
            ], 500);
        }
    }
}
