<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Si vous utilisez Passport (optionnel)
        // Passport::routes();

        // Définir les gates globaux
        $this->defineGates();
    }

    /**
     * Define global authorization gates.
     */
    protected function defineGates(): void
    {
        // Gate pour vérifier si l'utilisateur est admin
        Gate::define('is-admin', function ($user) {
            return $user->hasRole('admin');
        });

        // Gate pour vérifier si l'utilisateur est manager
        Gate::define('is-manager', function ($user) {
            return $user->hasRole('manager');
        });

        // Gate générique pour vérifier n'importe quel rôle
        Gate::define('has-role', function ($user, ...$roles) {
            return $user->hasAnyRole($roles);
        });
    }
}
