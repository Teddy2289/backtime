<?php

namespace Modules\TaskComment\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\TaskComment\Application\Services\TaskCommentService;
use Modules\TaskComment\Presentation\Controllers\TaskCommentController;
// AJOUTEZ CES USES
use Modules\Task\Application\Services\TaskService;
use Modules\Task\Domain\Interfaces\TaskRepositoryInterface;
use Modules\Task\Infrastructure\Repositories\TaskRepository;
use Modules\TaskComment\Domain\Interfaces\TaskCommentRepositoryInterface;
use Modules\TaskComment\Infrastructure\Repositories\TaskCommentRepository;

class TaskCommentServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Taskcomment';
    protected $moduleNameLower = 'taskcomment';

    public function register(): void
    {
        // 1. Repository pour TaskComment
        $this->app->bind(
            TaskCommentRepositoryInterface::class,
            TaskCommentRepository::class
        );

        // 2. Service pour TaskComment (dépend de son repository)
        $this->app->singleton(
            TaskCommentService::class,
            function ($app) {
                return new TaskCommentService(
                    $app->make(TaskCommentRepositoryInterface::class)
                );
            }
        );

        if (!$this->app->bound(TaskRepositoryInterface::class)) {
            $this->app->bind(
                TaskRepositoryInterface::class,
                TaskRepository::class
            );
        }

        if (!$this->app->bound(TaskService::class)) {
            $this->app->singleton(
                TaskService::class,
                function ($app) {
                    return new TaskService(
                        $app->make(TaskRepositoryInterface::class)
                    );
                }
            );
        }

        $this->app->bind(
            TaskCommentController::class,
            function ($app) {
                return new TaskCommentController(
                    $app->make(TaskCommentService::class),
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
            TaskCommentRepositoryInterface::class,
            TaskCommentService::class,
            TaskService::class,
            TaskCommentController::class,
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
