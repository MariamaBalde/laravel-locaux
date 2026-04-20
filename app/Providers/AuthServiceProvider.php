<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Policies\AdminUserPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorOrderPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Order::class => OrderPolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin.users.viewAny', [AdminUserPolicy::class, 'viewAny']);
        Gate::define('admin.users.updateStatus', [AdminUserPolicy::class, 'updateStatus']);
        Gate::define('vendor.orders.viewAny', [VendorOrderPolicy::class, 'viewAny']);
        Gate::define('vendor.orders.view', [VendorOrderPolicy::class, 'view']);
        Gate::define('vendor.orders.updateStatus', [VendorOrderPolicy::class, 'updateStatus']);
        Gate::define('vendor.orders.updateTracking', [VendorOrderPolicy::class, 'updateTracking']);

        // ✅ Enregistrer les routes Passport
        // Passport::routes(); // Déprécié en Laravel 11

        // ✅ Configurer la durée de vie des tokens (optionnel)
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // // ✅ Définir les scopes (permissions) si nécessaire
        // Passport::tokensCan([
        //     'manage-products' => 'Gérer les produits',
        //     'place-orders' => 'Passer des commandes',
        //     'manage-orders' => 'Gérer les commandes',
        // ]);

        // // Scope par défaut
        // Passport::setDefaultScope([
        //     'place-orders',
        // ]);
    }
}
