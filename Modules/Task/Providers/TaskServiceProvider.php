<?php

namespace Modules\Task\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Modules\Task\Application\Services\TaskService;
use Modules\Task\Domain\Interfaces\TaskRepositoryInterface;
use Modules\Task\Infrastructure\Repositories\TaskRepository;
use Modules\Task\Presentation\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Modules\Task\Domain\Entities\Task;

class TaskServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Task';
    protected $moduleNameLower = 'task';

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            TaskRepositoryInterface::class,
            TaskRepository::class
        );

        // Register TeamService as singleton
        $this->app->singleton(
            TaskService::class,
            function ($app) {
                return new TaskService(
                    $app->make(TaskRepositoryInterface::class)
                );
            }
        );

        // Register TeamController for dependency injection if needed
        $this->app->bind(
            TaskController::class,
            function ($app) {
                return new TaskController(
                    $app->make(TaskService::class)
                );
            }
        );
    }


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

        // Enlevez temporairement la condition
        // if (!$this->app->routesAreCached() && !$this->app->runningInConsole()) {
        $routeFile = module_path($this->moduleName, 'Presentation/Routes/api.php');

        if (file_exists($routeFile)) {
            Route::middleware(['api'])
                ->prefix('api')
                ->group(function () use ($routeFile) {
                    require $routeFile;
                });
        } else {
            Log::warning('TaskServiceProvider: Fichier de routes introuvable - ' . $routeFile);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TaskRepositoryInterface::class,
            TaskService::class,
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
