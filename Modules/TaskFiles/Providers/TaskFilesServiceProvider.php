<?php

namespace Modules\TaskFiles\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Task\Application\Services\TaskService;
use Modules\Task\Domain\Entities\Task;
use Modules\TaskFiles\Application\Services\TaskFileService;
use Modules\TaskFiles\Domain\Interfaces\TaskFileRepositoryInterface;
use Modules\TaskFiles\Infrastructure\Repositories\TaskFileRepository;
use Modules\TaskFiles\Presentation\Controllers\TaskFilesController;

class TaskFilesServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Taskfiles';
    protected $moduleNameLower = 'taskfiles';



    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            TaskFileRepositoryInterface::class,
            TaskFileRepository::class
        );

        // Register TaskFileService as singleton
        $this->app->singleton(
            TaskFileService::class,
            function ($app) {
                return new TaskFileService(
                    $app->make(TaskFileRepositoryInterface::class)
                );
            }
        );

        if (!$this->app->bound(TaskFileRepositoryInterface::class)) {
            $this->app->bind(
                TaskFileRepositoryInterface::class,
                TaskFileRepository::class
            );
        }

        if (!$this->app->bound(TaskFileService::class)) {
            $this->app->singleton(
                TaskFileService::class,
                function ($app) {
                    return new TaskFileService(
                        $app->make(TaskFileRepositoryInterface::class)
                    );
                }
            );
        }

        // Register TeamController for dependency injection if needed
        $this->app->bind(
            TaskFilesController::class,
            function ($app): TaskFilesController {
                return new TaskFilesController(
                    $app->make(TaskFileService::class),
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
        $routeFile = module_path($this->moduleName, 'Presentation/Routes/api.php');

        if (file_exists($routeFile)) {
            Route::middleware(['api'])
                ->prefix('api')
                ->group(function () use ($routeFile) {
                    require $routeFile;
                });
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TaskFileRepositoryInterface::class,
            TaskFileService::class,
            TaskFilesController::class,
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

