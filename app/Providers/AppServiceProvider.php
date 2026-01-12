<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Aucune registration nécessaire pour les modules
        // Ils seront chargés via config/app.php
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuration MySQL pour éviter les erreurs de longueur de clé
        Schema::defaultStringLength(191);

        // RETIRÉ: Schema::defaultMorphKeyType('uuid');
        // On utilise des IDs auto-incrémentés

        // Configuration Spatie Permission
        $this->configurePermissions();

        // Configuration JWT
        $this->configureJWT();
    }

    /**
     * Configure Spatie Permission settings.
     */
    protected function configurePermissions(): void
    {
        // Désactiver le cache des permissions en développement
        if ($this->app->environment('local')) {
            config(['permission.cache.enabled' => false]);
        }

        // Définir le temps d'expiration du cache
        config(['permission.cache.expiration_time' => 60 * 60]); // 1 heure
    }

    /**
     * Configure JWT settings.
     */
    protected function configureJWT(): void
    {
        // Configuration spécifique JWT si nécessaire
        config(['jwt.blacklist_enabled' => true]);
        config(['jwt.blacklist_grace_period' => 30]);
    }
}