<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCollection;
use App\Models\Category;
use App\Services\Category\CategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Liste toutes les catégories (public)
     * GET /api/categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategories($request->all());

            return response()->json([
                'success' => true,
                'data' => CategoryResource::collection($categories->getCollection()),
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'last_page' => $categories->lastPage(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
                    'total' => $categories->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des catégories',
            ], 500);
        }
    }

    /**
     * Affiche une catégorie spécifique (public)
     * GET /api/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
                'errors' => $e->errors(),
            ], 404);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la catégorie',
            ], 500);
        }
    }

    /**
     * Affiche une catégorie par son slug (public)
     * GET /api/categories/slug/{slug}
     */
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryBySlug($slug);

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
                'errors' => $e->errors(),
            ], 404);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la catégorie',
            ], 500);
        }
    }

    /**
     * Crée une nouvelle catégorie (admin uniquement)
     * POST /api/categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Category::class);
            $category = $this->categoryService->createCategory(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => new CategoryResource($category),
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
                'message' => 'Erreur lors de la création de la catégorie',
            ], 500);
        }
    }

    /**
     * Met à jour une catégorie (admin uniquement)
     * PUT/PATCH /api/categories/{id}
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $categoryModel = Category::find($id);
            if ($categoryModel) {
                $this->authorize('update', $categoryModel);
            }
            $category = $this->categoryService->updateCategory(
                $id,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => new CategoryResource($category),
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
                'message' => 'Erreur lors de la mise à jour de la catégorie',
            ], 500);
        }
    }

    /**
     * Supprime une catégorie (admin uniquement)
     * DELETE /api/categories/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $categoryModel = Category::find($id);
            if ($categoryModel) {
                $this->authorize('delete', $categoryModel);
            }
            $result = $this->categoryService->deleteCategory(
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
                'message' => 'Erreur lors de la suppression de la catégorie',
            ], 500);
        }
    }

    /**
     * Récupère les produits d'une catégorie (public)
     * GET /api/categories/{id}/products
     */
    public function products(Request $request, int $id): JsonResponse
    {
        try {
            $filters = $request->all();
            $products = $this->categoryService->getCategoryProducts($id, $filters);

            return response()->json([
                'success' => true,
                'data' => new ProductCollection($products),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
                'errors' => $e->errors(),
            ], 404);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
            ], 500);
        }
    }
}
