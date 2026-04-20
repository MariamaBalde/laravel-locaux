<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VendorDashboardController;
use App\Http\Controllers\Api\VendorOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Authentification avec Passport
|--------------------------------------------------------------------------
*/

// Routes publiques (sans authentification)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1'); // Rafraîchir token
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
});

// Alias legacy pour compatibilité (contrat /api/login)
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/password/forgot', [PasswordController::class, 'forgot'])->middleware('throttle:6,1');
Route::post('/password/reset', [PasswordController::class, 'reset'])->middleware('throttle:6,1');

// Routes protégées (avec authentification Passport)
Route::middleware('auth:api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/email/verify', [AuthController::class, 'verifyAuthenticatedEmail']);

    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::patch('/profile', [UserController::class, 'updateProfile']);
        Route::post('/change-password', [UserController::class, 'changePassword']);

        Route::get('/addresses', [UserController::class, 'addresses']);
        Route::post('/addresses', [UserController::class, 'addAddress']);
        Route::patch('/addresses/{id}', [UserController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [UserController::class, 'deleteAddress']);

        Route::get('/payment-methods', [UserController::class, 'paymentMethods']);
        Route::post('/payment-methods', [UserController::class, 'addPaymentMethod']);
        Route::delete('/payment-methods/{id}', [UserController::class, 'deletePaymentMethod']);
    });
});

/*
|--------------------------------------------------------------------------
| API Routes - Produits
|--------------------------------------------------------------------------
*/

// Routes publiques - Consultation des produits
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/new', [ProductController::class, 'newest']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Vendeur uniquement
Route::middleware(['auth:api', 'vendeur'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/vendeur/products', [ProductController::class, 'myProducts']);
    Route::get('/seller/products', [ProductController::class, 'myProducts']);
    Route::post('/seller/products', [ProductController::class, 'store']);
    Route::post('/seller/products/{id}/images', [ProductController::class, 'uploadImages']);

    Route::prefix('vendor/dashboard')->group(function () {
        Route::get('/overview', [VendorDashboardController::class, 'overview']);
        Route::get('/stats', [VendorDashboardController::class, 'stats']);
        Route::get('/revenue-weekly', [VendorDashboardController::class, 'revenueWeekly']);
        Route::get('/destinations', [VendorDashboardController::class, 'destinations']);
        Route::get('/recent-orders', [VendorDashboardController::class, 'recentOrders']);
        Route::get('/top-products', [VendorDashboardController::class, 'topProducts']);
    });

    Route::get('/seller/dashboard', [VendorDashboardController::class, 'overview']);
    Route::get('/seller/orders', [VendorOrderController::class, 'index']);
    Route::get('/seller/orders/{id}', [VendorOrderController::class, 'show']);
    Route::patch('/seller/orders/{id}/status', [VendorOrderController::class, 'updateStatus']);
    Route::put('/seller/orders/{id}/status', [VendorOrderController::class, 'updateStatus']);

    Route::get('/vendor/orders/{id}', [VendorOrderController::class, 'show']);
    Route::patch('/vendor/orders/{id}/status', [VendorOrderController::class, 'updateStatus']);
    Route::put('/vendor/orders/{id}/status', [VendorOrderController::class, 'updateStatus']);
    Route::patch('/vendor/orders/{id}/tracking', [VendorOrderController::class, 'updateTracking']);

    Route::get('/seller/profile', [UserController::class, 'profile']);
    Route::put('/seller/profile', [UserController::class, 'updateProfile']);
});

// Vendeur OU Admin
Route::middleware(['auth:api', 'role:vendeur,admin'])->group(function () {
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::put('/seller/products/{id}', [ProductController::class, 'update']);
    Route::delete('/seller/products/{id}', [ProductController::class, 'destroy']);
    Route::patch('/products/{id}/toggle', [ProductController::class, 'toggle']);
    Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Catégories
|--------------------------------------------------------------------------
*/

// Routes publiques - Consultation des catégories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
Route::get('/categories/{id}/products', [CategoryController::class, 'products']);

// Routes protégées - Gestion des catégories (Admin uniquement)
Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::patch('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Panier (Cart)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);           // Récupérer le panier
    Route::post('/', [CartController::class, 'store']);          // Ajouter au panier
    Route::patch('/{id}', [CartController::class, 'update']);    // Mettre à jour quantité
    Route::put('/{id}', [CartController::class, 'update']);
    Route::delete('/{id}', [CartController::class, 'destroy']);  // Supprimer du panier
    Route::delete('/', [CartController::class, 'clear']);        // Vider le panier
    Route::get('/check', [CartController::class, 'check']);      // Vérifier disponibilité
});

/*
|--------------------------------------------------------------------------
| API Routes - Commandes (Orders)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);                    // Lister les commandes
    Route::post('/', [OrderController::class, 'store']);                   // Créer une commande
    Route::get('/{id}', [OrderController::class, 'show']);                 // Détail d'une commande
    Route::patch('/{id}/cancel', [OrderController::class, 'cancel']);      // Annuler une commande
    Route::put('/{id}/cancel', [OrderController::class, 'cancel']);        // Alias contrat API
    Route::post('/{id}/confirm-payment', [OrderController::class, 'confirmPayment']); // Confirmer paiement
    Route::put('/{id}/shipping', [ShippingController::class, 'updateOrderShipping']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Paiements
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('payments')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate'])->middleware('throttle:20,1');
    Route::get('/status/{orderId}', [PaymentController::class, 'status']);
});

// Callback provider (sans auth utilisateur)
Route::post('/payments/callback', [PaymentController::class, 'callback'])->middleware('throttle:60,1');
Route::get('/payments/callback', [PaymentController::class, 'callback'])->middleware('throttle:60,1');

Route::get('/shipping/methods', [ShippingController::class, 'methods']);
Route::post('/shipping/estimate', [ShippingController::class, 'estimate']);

/*
|--------------------------------------------------------------------------
| API Routes - Dashboard Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    // Statistiques du dashboard
    Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
    Route::get('/stats', [AdminController::class, 'dashboardStats']);
    Route::get('/dashboard/advanced-stats', [AdminController::class, 'advancedStats']);

    // Graphiques
    Route::get('/charts/sales-per-month', [AdminController::class, 'salesPerMonth']);
    Route::get('/charts/products-by-category', [AdminController::class, 'productsByCategory']);

    // Top vendeurs et commandes récentes
    Route::get('/top-vendors', [AdminController::class, 'topVendors']);
    Route::get('/recent-orders', [AdminController::class, 'recentOrders']);
    Route::get('/orders', [AdminController::class, 'recentOrders']);
    Route::get('/recent-users', [AdminController::class, 'recentUsers']);

    // Gestion des vendeurs
    Route::get('/pending-vendors', [AdminController::class, 'pendingVendors']);
    Route::post('/vendors/{id}/approve', [AdminController::class, 'approveVendor']);
    Route::post('/vendors/{id}/reject', [AdminController::class, 'rejectVendor']);

    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'users']);
    Route::put('/users/{id}/status', [AdminController::class, 'toggleUserStatus']);
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

    // Création de produit assistée par admin
    Route::get('/products', [AdminController::class, 'getAllProducts']);
    Route::post('/products', [AdminController::class, 'createProductForVendor']);
    Route::patch('/products/{id}/status', [AdminController::class, 'updateProductStatus']);
    Route::patch('/products/{id}/featured', [AdminController::class, 'toggleFeaturedProduct']);
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);
});
