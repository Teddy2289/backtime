<?php

namespace Modules\TaskTimeLog\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Task\Application\Services\TaskService;
use Modules\TaskFiles\Domain\Interfaces\TaskFileRepositoryInterface;
use Modules\TaskTimeLog\Application\Services\TaskTimeLogService;
use Modules\TaskTimeLog\Domain\Interfaces\TaskTimeLogRepositoryInterface;
use Modules\TaskTimeLog\Infrastructure\Repositories\TaskTimeLogRepository;
use Modules\TaskTimeLog\Presentation\Controllers\TasktimelogController;

class TaskTimeLogServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Tasktimelog';
    protected $moduleNameLower = 'tasktimelog';

    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            TaskTimeLogRepositoryInterface::class,
            TaskTimeLogRepository::class
        );

        // Register TeamService as singleton
        $this->app->singleton(
            TaskTimeLogService::class,
            function ($app) {
                return new TaskTimeLogService(
                    $app->make(TaskTimeLogRepositoryInterface::class)
                );
            }
        );


        if (!$this->app->bound(TaskTimeLogRepositoryInterface::class)) {
            $this->app->bind(
                TaskFileRepositoryInterface::class,
                TaskTimeLogRepository::class
            );
        }


        // Register TeamController for dependency injection if needed
        $this->app->bind(
            TaskTimeLogController::class, // Notez le "T" majuscule
            function ($app) {
                return new TaskTimeLogController(
                    $app->make(TaskTimeLogService::class), // Premier paramètre
                    $app->make(TaskService::class)         // Deuxième paramètre
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
            TaskTimeLogRepositoryInterface::class,
            TaskTimeLogService::class,
            TaskTimeLogController::class,
            TaskService::class
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
