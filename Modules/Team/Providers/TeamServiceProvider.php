<?php

namespace Modules\Team\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TeamServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'Team';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'team';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Data/Migrations'));
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            \Modules\Team\Domain\Interfaces\TeamRepositoryInterface::class,
            \Modules\Team\Infrastructure\Repositories\TeamRepository::class
        );

        // Register TeamService as singleton
        $this->app->singleton(
            \Modules\Team\Application\Services\TeamService::class,
            function ($app) {
                return new \Modules\Team\Application\Services\TeamService(
                    $app->make(\Modules\Team\Domain\Interfaces\TeamRepositoryInterface::class)
                );
            }
        );

        // Register TeamController for dependency injection if needed
        $this->app->bind(
            \Modules\Team\Presentation\Controllers\TeamController::class,
            function ($app) {
                return new \Modules\Team\Presentation\Controllers\TeamController(
                    $app->make(\Modules\Team\Application\Services\TeamService::class)
                );
            }
        );
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Presentation/Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'Presentation/Resources/lang'),
                $this->moduleNameLower
            );
            $this->loadJsonTranslationsFrom(
                module_path($this->moduleName, 'Presentation/Resources/lang')
            );
        }
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        // Chargez les routes seulement si nous ne sommes pas en mode console
        // et si le cache de routes n'est pas activé
        if (!$this->app->routesAreCached() && !$this->app->runningInConsole()) {
            Route::middleware(['api'])
                ->prefix('api')
                ->group(function () {
                    $routeFile = module_path($this->moduleName, 'Presentation/Routes/api.php');

                    if (file_exists($routeFile)) {
                        require $routeFile;
                    }
                });
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            \Modules\Team\Domain\Interfaces\TeamRepositoryInterface::class,
            \Modules\Team\Application\Services\TeamService::class,
        ];
    }

    /**
     * Get publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        $viewPaths = config('view.paths', []);

        foreach ($viewPaths as $path) {
            $modulePath = $path . '/modules/' . $this->moduleNameLower;
            if (is_dir($modulePath)) {
                $paths[] = $modulePath;
            }
        }

        return $paths;
    }
}