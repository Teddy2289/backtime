<?php

namespace Modules\ProjectsTeams\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ProjectsTeams\Application\Services\ProjectsTeamsService;
use Modules\ProjectsTeams\Domain\Interfaces\ProjectsTeamsRepositoryInterface;
use Modules\ProjectsTeams\Infrastructure\Repositories\ProjectsTeamsRepository;
use Modules\ProjectsTeams\Presentation\Controllers\ProjectsteamsController;

class ProjectsTeamsServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'ProjectsTeams';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'projectsteams';

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

        // Si vous utilisez le namespace "Modules\Project" pour les classes métier
        if (class_exists('\Modules\Project\Domain\Interfaces\ProjectsTeamsRepositoryInterface::class')) {
            // Version avec namespace "Modules\Project"
            $this->app->bind(
                ProjectsTeamsRepositoryInterface::class,
                ProjectsTeamsRepository::class
            );

            $this->app->singleton(
                ProjectsTeamsService::class,
                function ($app) {
                    return new ProjectsTeamsService(
                        $app->make(ProjectsTeamsRepositoryInterface::class)
                    );
                }
            );

            $this->app->bind(
                ProjectsTeamsController::class,
                function ($app) {
                    return new ProjectsteamsController(
                        $app->make(ProjectsTeamsService::class)
                    );
                }
            );
        } else {
            $this->app->bind(
                ProjectsTeamsRepositoryInterface::class,
                ProjectsTeamsRepository::class
            );

            $this->app->singleton(
                ProjectsTeamsService::class,
                function ($app) {
                    return new ProjectsTeamsService(
                        $app->make(ProjectsTeamsRepositoryInterface::class)
                    );
                }
            );

            $this->app->bind(
                ProjectsTeamsController::class,
                function ($app) {
                    return new ProjectsTeamsController(
                        $app->make(\Modules\ProjectsTeams\Application\Services\ProjectsTeamsService::class)
                    );
                }
            );
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path($this->moduleNameLower . '.php'),
            ], 'config');

            $this->mergeConfigFrom(
                $configPath,
                $this->moduleNameLower
            );
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Presentation/Resources/views');

        if (is_dir($sourcePath)) {
            $this->publishes([
                $sourcePath => $viewPath
            ], ['views', $this->moduleNameLower . '-module-views']);

            $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
        }
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
            $sourceLangPath = module_path($this->moduleName, 'Presentation/Resources/lang');

            if (is_dir($sourceLangPath)) {
                $this->loadTranslationsFrom($sourceLangPath, $this->moduleNameLower);
                $this->loadJsonTranslationsFrom($sourceLangPath);
            }
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
            // API Routes
            Route::middleware(['api'])
                ->prefix('api')
                ->group(function () {
                    $apiRouteFile = module_path($this->moduleName, 'Presentation/Routes/api.php');

                    if (file_exists($apiRouteFile)) {
                        require $apiRouteFile;
                    }
                });

            // Web Routes
            $webRouteFile = module_path($this->moduleName, 'Presentation/Routes/web.php');

            if (file_exists($webRouteFile)) {
                Route::middleware(['web'])
                    ->group(function () use ($webRouteFile) {
                        require $webRouteFile;
                    });
            }
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ProjectsTeamsRepositoryInterface::class,
            ProjectsTeamsService::class,
            ProjectsTeamsController::class,
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
