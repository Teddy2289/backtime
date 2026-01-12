<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\UserDashboardService;
use App\Services\Dashboard\DashboardHelperService;

class DashboardUserController extends Controller
{
    protected $adminDashboardService;
    protected $userDashboardService;
    protected $helperService;

    public function __construct(
        AdminDashboardService $adminDashboardService,
        UserDashboardService $userDashboardService,
        DashboardHelperService $helperService
    ) {
        $this->adminDashboardService = $adminDashboardService;
        $this->userDashboardService = $userDashboardService;
        $this->helperService = $helperService;
    }

    /**
     * Get dashboard statistics for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $data = $user->isAdmin()
            ? $this->adminDashboardService->getData($user)
            : $this->userDashboardService->getData($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get detailed task status statistics
     */
    public function taskStatusStats(Request $request): JsonResponse
    {
        return $this->adminDashboardService->getTaskStatusStatsResponse($request);
    }

    /**
     * Get user's monthly work summary
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        return $this->userDashboardService->getMonthlySummaryResponse($request);
    }

    /**
     * Get tasks list with filters
     */
    public function tasksList(Request $request): JsonResponse
    {
        return $this->helperService->getTasksListResponse($request);
    }

    /**
     * Get recent tasks
     */
    public function recentTasks(Request $request): JsonResponse
    {
        return $this->helperService->getRecentTasksResponse($request);
    }

    /**
     * Get overdue tasks
     */
    public function overdueTasks(Request $request): JsonResponse
    {
        return $this->helperService->getOverdueTasksResponse($request);
    }

    /**
     * Get upcoming tasks
     */
    public function upcomingTasks(Request $request): JsonResponse
    {
        return $this->helperService->getUpcomingTasksResponse($request);
    }
}
