<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdminProductForVendorRequest;
use App\Http\Requests\UpdateAdminProductStatusRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\DataCollection;
use App\Http\Resources\DataResource;
use App\Http\Resources\ProductResource;
use App\Services\AdminService;
use App\Services\Product\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $adminService;

    protected $productService;

    public function __construct(AdminService $adminService, ProductService $productService)
    {
        $this->adminService = $adminService;
        $this->productService = $productService;
    }

    private function authorizeAdminAccess(): void
    {
        $this->authorize('admin.users.viewAny');
    }

    /**
     * Statistiques du dashboard
     * GET /api/admin/dashboard/stats
     */
    public function dashboardStats(): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $stats = $this->adminService->getDashboardStats();

            return response()->json([
                'success' => true,
                'data' => new DataResource($stats),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
            ], 500);
        }
    }

    /**
     * Statistiques avancées
     * GET /api/admin/dashboard/advanced-stats
     */
    public function advancedStats(): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $stats = $this->adminService->getAdvancedStats();

            return response()->json([
                'success' => true,
                'data' => new DataResource($stats),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Ventes par mois
     * GET /api/admin/charts/sales-per-month
     */
    public function salesPerMonth(Request $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $year = $request->year ?? now()->year;
            $data = $this->adminService->getSalesPerMonth($year);

            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($data)),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Produits par catégorie
     * GET /api/admin/charts/products-by-category
     */
    public function productsByCategory(): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $data = $this->adminService->getProductsByCategory();

            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($data)),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Top vendeurs
     * GET /api/admin/top-vendors
     */
    public function topVendors(Request $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $data = $this->adminService->getTopVendors($request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection($data->getCollection()),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'total' => $data->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Dernières commandes
     * GET /api/admin/recent-orders
     */
    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $data = $this->adminService->getRecentOrders($request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection($data->getCollection()),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'total' => $data->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Derniers utilisateurs
     * GET /api/admin/recent-users
     */
    public function recentUsers(Request $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $data = $this->adminService->getRecentUsers($request->all());

            return response()->json([
                'success' => true,
                'data' => AdminUserResource::collection($data->getCollection()),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'total' => $data->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Vendeurs en attente
     * GET /api/admin/pending-vendors
     */
    public function pendingVendors(Request $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $data = $this->adminService->getPendingVendors($request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection($data->getCollection()),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'total' => $data->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Approuver un vendeur
     * POST /api/admin/vendors/{id}/approve
     */
    public function approveVendor(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $result = $this->adminService->approveVendor($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new DataResource($result['vendeur']->toArray()),
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
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Rejeter un vendeur
     * POST /api/admin/vendors/{id}/reject
     */
    public function rejectVendor(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $reason = $request->input('reason');
            $result = $this->adminService->rejectVendor($id, $request->user(), $reason);

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
        } catch (\Exception $e) {
            report($e);
            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée',
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Suspendre/Activer un utilisateur
     * POST /api/admin/users/{id}/toggle-status
     */
    public function toggleUserStatus(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorize('admin.users.updateStatus');
            $result = $this->adminService->toggleUserStatus($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new AdminUserResource($result['user']),
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
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Liste des utilisateurs
     * GET /api/admin/users
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $this->authorize('admin.users.viewAny');
            $filters = $request->all();
            $users = $this->adminService->getAllUsers($filters);

            return response()->json([
                'success' => true,
                'data' => AdminUserResource::collection($users->getCollection()),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'total' => $users->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);
            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée',
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur',
            ], 500);
        }
    }

    /**
     * Créer un produit pour le compte d'un vendeur
     * POST /api/admin/products
     */
    public function createProductForVendor(CreateAdminProductForVendorRequest $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $product = $this->productService->createProductForVendor(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit créé pour le vendeur avec succès',
                'data' => new ProductResource($product),
            ], 201);
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
                'message' => 'Erreur lors de la création du produit vendeur',
            ], 500);
        }
    }

    /**
     * Liste produits enrichie pour admin
     * GET /api/admin/products
     */
    public function getAllProducts(Request $request): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $products = $this->adminService->getAllProducts($request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($products)),
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des produits',
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'un produit
     * PATCH /api/admin/products/{id}/status
     */
    public function updateProductStatus(UpdateAdminProductStatusRequest $request, int $id): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $validated = $request->validated();

            $result = $this->adminService->updateProductStatus($id, $validated['status'], $request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
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
                'message' => 'Erreur lors de la mise à jour du statut',
            ], 500);
        }
    }

    /**
     * Basculer le statut featured d'un produit
     * PATCH /api/admin/products/{id}/featured
     */
    public function toggleFeaturedProduct(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $result = $this->adminService->toggleFeaturedProduct($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
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
                'message' => 'Erreur lors de la mise en avant',
            ], 500);
        }
    }

    /**
     * Supprimer un produit
     * DELETE /api/admin/products/{id}
     */
    public function deleteProduct(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorizeAdminAccess();
            $result = $this->adminService->deleteProduct($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
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
                'message' => 'Erreur lors de la suppression',
            ], 500);
        }
    }
}
