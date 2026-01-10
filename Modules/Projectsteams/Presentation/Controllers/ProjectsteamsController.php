<?php

namespace Modules\ProjectsTeams\Presentation\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ProjectsTeams\Application\Services\ProjectsTeamsService;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\User\Domain\Entities\User;

class ProjectsTeamsController extends Controller
{
    use ApiResponser;

    /**
     * The project service instance.
     */
    protected ProjectsTeamsService $projectService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ProjectsTeamsService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['team_id', 'status', 'search', 'order_by', 'order_direction']);

            $projects = $this->projectService->getPaginatedProjects($perPage, $filters);

            return $this->paginatedResponse($projects, 'Projects retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve projects',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'team_id' => 'required|integer|exists:teams,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string|in:active,completed,on_hold,cancelled',
            ]);

            $project = $this->projectService->createProject($validated);

            return $this->successResponse(
                $project,
                'Project created successfully',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Display the specified project.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->getProjectById($id);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:1000',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string|in:active,completed,on_hold,cancelled',
            ]);

            $project = $this->projectService->updateProject($id, $validated);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove the specified project.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->projectService->deleteProject($id);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete project', null, 500);
            }

            return $this->successResponse(
                null,
                'Project deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Restore a soft-deleted project.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $restored = $this->projectService->restoreProject($id);

            if (!$restored) {
                return $this->errorResponse('Failed to restore project', null, 500);
            }

            return $this->successResponse(
                null,
                'Project restored successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to restore project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get projects by team ID.
     */
    public function getByTeam(int $teamId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['status', 'search', 'order_by', 'order_direction']);

            $projects = $this->projectService->getPaginatedProjectsByTeam($teamId, $perPage, $filters);

            return $this->paginatedResponse($projects, 'Team projects retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve team projects',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Search projects.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2',
            ]);

            $filters = $request->only(['team_id', 'status']);
            $projects = $this->projectService->searchProjects($request->query('query'), $filters);

            return $this->successResponse(
                $projects,
                'Projects search completed successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to search projects',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get projects by status.
     */
    public function getByStatus(string $status): JsonResponse // Changé de int à string
    {
        try {
            $projects = $this->projectService->getProjectsByStatus($status);

            return $this->successResponse(
                $projects,
                'Projects by status retrieved successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve projects by status',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get upcoming projects ending soon.
     */
    public function getUpcoming(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $projects = $this->projectService->getUpcomingEndDateProjects($days);

            return $this->successResponse(
                $projects,
                'Upcoming projects retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve upcoming projects',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get project statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $teamId = $request->get('team_id');
            $stats = $this->projectService->getProjectStatistics($teamId);

            return $this->successResponse(
                $stats,
                'Project statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve project statistics',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update project status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:active,completed,on_hold,cancelled',
            ]);

            $project = $this->projectService->updateProjectStatus($id, $validated['status']);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project status updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update project status',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Complete a project.
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->completeProject($id);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project marked as completed'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to complete project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Put project on hold.
     */
    public function putOnHold(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->putProjectOnHold($id);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project put on hold'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to put project on hold',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Cancel a project.
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->cancelProject($id);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project cancelled'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to cancel project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Reactivate a project.
     */
    public function reactivate(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->reactivateProject($id);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            return $this->successResponse(
                $project,
                'Project reactivated'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to reactivate project',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Check if project belongs to team.
     */
    public function checkTeamMembership(int $projectId, int $teamId): JsonResponse
    {
        try {
            $belongs = $this->projectService->projectBelongsToTeam($projectId, $teamId);

            return $this->successResponse(
                ['belongs' => $belongs],
                $belongs ? 'Project belongs to the team' : 'Project does not belong to the team'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to check project membership',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'module' => 'Project',
            'version' => '1.0.0'
        ], 'Module is healthy');
    }

    /**
     * Get team members for a project.
     */
    public function getProjectTeamUsers(int $projectId): JsonResponse
    {
        try {
            // 1. Charger le projet avec sa team ET les membres de cette team en une seule fois
            $project = ProjectsTeams::with('team.members')->find($projectId);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            // 2. Vérifier si le projet a une équipe
            if (!$project->team) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Project has no team'
                ], 200);
            }

            // 3. Récupérer les membres directement via la relation
            // Eloquent filtrera automatiquement par le team_id du projet spécifique
            $members = $project->team->members;

            // 4. Formater les membres
            $formattedMembers = $members->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'avatar_url' => $user->avatar_url, // Utilise l'accessor de votre modèle User
                    'initials' => $user->initials,   // Utilise l'accessor de votre modèle User
                    'role' => 'member',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedMembers,
                'message' => 'Project team members retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getProjectTeamUsers', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assignable users for a project (team members)
     */
    public function getAssignableUsers(int $projectId): JsonResponse
    {
        try {
            $project = ProjectsTeams::with(['team.members'])->find($projectId);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            if (!$project->team) {
                return $this->successResponse([], 'Project has no team');
            }

            $users = $project->team->members->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'initials' => $user->initials ?? strtoupper(substr($user->name, 0, 2)),
                    'can_assign' => true, // Tous les membres peuvent être assignés
                ];
            });

            return $this->successResponse(
                $users,
                'Assignable users retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve assignable users',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get project with team members (detailed view)
     */
    public function showWithTeam(int $id): JsonResponse
    {
        try {
            $project = ProjectsTeams::with(['team.members'])->find($id);

            if (!$project) {
                return $this->notFoundResponse('Project not found');
            }

            $responseData = [
                'id' => $project->id,
                'team_id' => $project->team_id,
                'name' => $project->name,
                'description' => $project->description,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'status' => $project->status,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'team' => $project->team ? [
                    'id' => $project->team->id,
                    'name' => $project->team->name,
                    'description' => $project->team->description,
                    'owner_id' => $project->team->owner_id,
                    'is_public' => $project->team->is_public,
                ] : null,
                'team_members' => $project->team ? $project->team->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'avatar' => $member->avatar,
                        'avatar_url' => $member->avatar_url ?? null,
                        'role' => $member->pivot->role ?? 'member',
                    ];
                }) : [],
                'tasks_count' => $project->tasks()->count(),
            ];

            return $this->successResponse(
                $responseData,
                'Project with team details retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve project details',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
}