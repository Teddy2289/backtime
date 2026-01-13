<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\WorkTimeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\DashboardUserController;

Route::middleware(['api'])->group(
    function () {
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('login', [AuthController::class, 'login']);
            Route::post('register', [AuthController::class, 'register']);
            Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
            Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
            Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');

            // Profile routes
            Route::prefix('profile')->group(function () {
                Route::get('/', [AuthController::class, 'profile']);
                Route::put('/', [AuthController::class, 'updateProfile']);
                Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
                Route::delete('/avatar', [AuthController::class, 'removeAvatar']);
            });
        });


        Route::prefix('userDashboard')->group(function () {
            // Dashboard principal
            Route::get('/', [DashboardUserController::class, 'index']);
            // Résumé mensuel
            Route::get('/monthly-summary', [DashboardUserController::class, 'monthlySummary']);
            // Statistiques détaillées des tâches (avec ou sans période)
            Route::get('/task-stats', [DashboardUserController::class, 'taskStatusStats']);
            // Liste des tâches avec pagination et filtres
            Route::get('/tasks', [DashboardUserController::class, 'tasksList']);
            // Tâches récentes (limité à 10)
            Route::get('/recent-tasks', [DashboardUserController::class, 'recentTasks']);
            // Tâches en retard
            Route::get('/overdue-tasks', [DashboardUserController::class, 'overdueTasks']);
            // Tâches à venir
            Route::get('/upcoming-tasks', [DashboardUserController::class, 'upcomingTasks']);
        });


        Route::prefix('dashboard')->group(function () {
            Route::get('/', action: [DashboardController::class, 'index']);
            Route::get('/dashboard/widgets', [DashboardController::class, 'widgetStats']);
            Route::get('/dashboard/user/{user}', [DashboardController::class, 'userStats']);
            Route::get('/users/{user}/work-time', [DashboardController::class, 'userWorkTime']);
            Route::get('/users/{user}/work-time/{period}', [DashboardController::class, 'userWorkTimeByPeriod']);
            Route::get('/users/{user}/work-history', [DashboardController::class, 'userWorkHistory']);
            Route::get('/users/{user}/task-work-time', [DashboardController::class, 'userTaskWorkTime']);
        });


        // Route work times
        Route::prefix('work-time')->group(function () {
            Route::post('/start-day', [WorkTimeController::class, 'startDay']);
            Route::post('/pause', [WorkTimeController::class, 'pause']);
            Route::post('/resume', [WorkTimeController::class, 'resume']);
            Route::post('/end-day', [WorkTimeController::class, 'endDay']);
            Route::get('/status', [WorkTimeController::class, 'status']);
            Route::get('/weekly-stats', [WorkTimeController::class, 'weeklyStats']);
            Route::get('/monthly-stats', [WorkTimeController::class, 'monthlyStats']);
            Route::get('/work-time/can-start', [WorkTimeController::class, 'canStartDay']);
            Route::post('/sync-time', [WorkTimeController::class, 'syncTime']);
            Route::post('/resume', [WorkTimeController::class, 'resumeDay']);
        });
        // Include module routes
        if (file_exists(base_path('Modules/User/Presentation/Routes/api.php'))) {
            require base_path('Modules/User/Presentation/Routes/api.php');
        }

        if (file_exists(base_path('Modules/Team/Presentation/Routes/api.php'))) {
            require base_path('Modules/Team/Presentation/Routes/api.php');
        }

        if (file_exists(base_path('Modules/Project/Presentation/Routes/api.php'))) {
            require base_path('Modules/Project/Presentation/Routes/api.php');
        }

        if (file_exists(base_path('Modules/Projectsteams/Presentation/Routes/api.php'))) {
            require base_path('Modules/Projectsteams/Presentation/Routes/api.php');
        }



        // Health check
        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now(),
                'version' => app()->version(),
            ]);
        });
    }
);
