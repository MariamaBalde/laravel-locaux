<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataCollection;
use App\Http\Resources\DataResource;
use App\Services\VendorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorDashboardController extends Controller
{
    public function __construct(private VendorDashboardService $vendorDashboardService) {}

    /**
     * GET /api/vendor/dashboard/overview
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $data = $this->vendorDashboardService->getOverview($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => new DataResource($data),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard vendeur',
            ], 500);
        }
    }

    /**
     * GET /api/vendor/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $overview = $this->vendorDashboardService->getOverview($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => new DataResource([
                    'stats' => $overview['stats'],
                    'notifications' => $overview['notifications'],
                ]),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
            ], 500);
        }
    }

    /**
     * GET /api/vendor/dashboard/revenue-weekly
     */
    public function revenueWeekly(Request $request): JsonResponse
    {
        try {
            $overview = $this->vendorDashboardService->getOverview($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($overview['weeklyRevenue'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des revenus hebdomadaires',
            ], 500);
        }
    }

    /**
     * GET /api/vendor/dashboard/destinations
     */
    public function destinations(Request $request): JsonResponse
    {
        try {
            $overview = $this->vendorDashboardService->getOverview($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => new DataResource($overview['destinations']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des destinations',
            ], 500);
        }
    }

    /**
     * GET /api/vendor/dashboard/recent-orders
     */
    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $overview = $this->vendorDashboardService->getOverview($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($overview['recentOrders'])),
                'pagination' => new DataResource($overview['pagination']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes récentes',
            ], 500);
        }
    }

    /**
     * GET /api/vendor/dashboard/top-products
     */
    public function topProducts(Request $request): JsonResponse
    {
        try {
            $overview = $this->vendorDashboardService->getOverview($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($overview['topProducts'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des top produits',
            ], 500);
        }
    }
}
