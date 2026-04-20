<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ApiEndpoints
{
    #[OA\Get(
        path: '/api/products',
        operationId: 'productsList',
        tags: ['Products'],
        summary: 'Lister les produits',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche texte', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 12)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function productsList(): void {}

    #[OA\Get(
        path: '/api/products/{id}',
        operationId: 'productShow',
        tags: ['Products'],
        summary: 'Detail produit',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche associee', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function productShow(): void {}

    #[OA\Post(
        path: '/api/products',
        operationId: 'productStore',
        tags: ['Products'],
        summary: 'Creer un produit (vendeur)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function productStore(): void {}

    #[OA\Patch(
        path: '/api/products/{id}',
        operationId: 'productUpdate',
        tags: ['Products'],
        summary: 'Mettre a jour un produit',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function productUpdate(): void {}

    #[OA\Delete(
        path: '/api/products/{id}',
        operationId: 'productDelete',
        tags: ['Products'],
        summary: 'Supprimer un produit',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function productDelete(): void {}

    #[OA\Get(
        path: '/api/categories',
        operationId: 'categoriesList',
        tags: ['Categories'],
        summary: 'Lister les categories',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche categorie', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function categoriesList(): void {}

    #[OA\Post(
        path: '/api/categories',
        operationId: 'categoriesStore',
        tags: ['Categories'],
        summary: 'Creer une categorie (admin)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function categoriesStore(): void {}

    #[OA\Put(
        path: '/api/categories/{id}',
        operationId: 'categoriesUpdate',
        tags: ['Categories'],
        summary: 'Mettre a jour une categorie (admin)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function categoriesUpdate(): void {}

    #[OA\Delete(
        path: '/api/categories/{id}',
        operationId: 'categoriesDelete',
        tags: ['Categories'],
        summary: 'Supprimer une categorie (admin)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function categoriesDelete(): void {}

    #[OA\Get(
        path: '/api/cart',
        operationId: 'cartShow',
        tags: ['Cart'],
        summary: 'Afficher le panier',
        parameters: [new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche dans le panier', schema: new OA\Schema(type: 'string'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function cartShow(): void {}

    #[OA\Post(
        path: '/api/cart',
        operationId: 'cartAddItem',
        tags: ['Cart'],
        summary: 'Ajouter un article au panier',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function cartAddItem(): void {}

    #[OA\Delete(
        path: '/api/cart/{id}',
        operationId: 'cartDeleteItem',
        tags: ['Cart'],
        summary: 'Retirer un article du panier',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function cartDeleteItem(): void {}

    #[OA\Post(
        path: '/api/orders',
        operationId: 'ordersCreate',
        tags: ['Orders'],
        summary: 'Creer une commande',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function ordersCreate(): void {}

    #[OA\Get(
        path: '/api/orders',
        operationId: 'ordersList',
        tags: ['Orders'],
        summary: 'Lister les commandes utilisateur',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche commande', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function ordersList(): void {}

    #[OA\Get(
        path: '/api/orders/{id}',
        operationId: 'ordersShow',
        tags: ['Orders'],
        summary: 'Detail d\'une commande',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche associee', schema: new OA\Schema(type: 'string')),
        ],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function ordersShow(): void {}

    #[OA\Post(
        path: '/api/payments/initiate',
        operationId: 'paymentsInitiate',
        tags: ['Payments'],
        summary: 'Initier un paiement',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function paymentsInitiate(): void {}

    #[OA\Get(
        path: '/api/payments/status/{orderId}',
        operationId: 'paymentsStatus',
        tags: ['Payments'],
        summary: 'Verifier le statut de paiement',
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche transaction', schema: new OA\Schema(type: 'string')),
        ],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function paymentsStatus(): void {}

    #[OA\Post(
        path: '/api/payments/callback',
        operationId: 'paymentsCallback',
        tags: ['Payments'],
        summary: 'Callback fournisseur paiement',
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function paymentsCallback(): void {}

    #[OA\Get(
        path: '/api/user/profile',
        operationId: 'userProfile',
        tags: ['User'],
        summary: 'Profil utilisateur',
        parameters: [new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche profil', schema: new OA\Schema(type: 'string'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function userProfile(): void {}

    #[OA\Patch(
        path: '/api/user/profile',
        operationId: 'userProfileUpdate',
        tags: ['User'],
        summary: 'Mettre a jour le profil utilisateur',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function userProfileUpdate(): void {}

    #[OA\Get(
        path: '/api/seller/dashboard',
        operationId: 'sellerDashboard',
        tags: ['Vendor'],
        summary: 'Dashboard vendeur',
        parameters: [new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche widget/stat', schema: new OA\Schema(type: 'string'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function sellerDashboard(): void {}

    #[OA\Get(
        path: '/api/seller/orders',
        operationId: 'sellerOrdersList',
        tags: ['Vendor'],
        summary: 'Lister les commandes vendeur',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche commande vendeur', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function sellerOrdersList(): void {}

    #[OA\Patch(
        path: '/api/seller/orders/{id}/status',
        operationId: 'sellerOrdersStatusUpdate',
        tags: ['Vendor'],
        summary: 'Mettre a jour le statut d\'une commande vendeur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function sellerOrdersStatusUpdate(): void {}

    #[OA\Get(
        path: '/api/admin/dashboard/stats',
        operationId: 'adminDashboardStats',
        tags: ['Admin'],
        summary: 'Statistiques dashboard admin',
        parameters: [new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche statistique', schema: new OA\Schema(type: 'string'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function adminDashboardStats(): void {}

    #[OA\Get(
        path: '/api/admin/pending-vendors',
        operationId: 'adminPendingVendors',
        tags: ['Admin'],
        summary: 'Lister les vendeurs en attente',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Recherche vendeur', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function adminPendingVendors(): void {}

    #[OA\Post(
        path: '/api/admin/vendors/{id}/approve',
        operationId: 'adminApproveVendor',
        tags: ['Admin'],
        summary: 'Approuver un vendeur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifie')]
    )]
    public function adminApproveVendor(): void {}
}
