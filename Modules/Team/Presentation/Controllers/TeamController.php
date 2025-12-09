<?php

namespace Modules\Team\Presentation\Controllers;

use Modules\Team\Presentation\Requests\CreateTeamRequest;
use Modules\Team\Presentation\Requests\UpdateTeamRequest;
use Modules\Team\Presentation\Requests\AddMemberRequest;
use Modules\Team\Presentation\Requests\TransferOwnershipRequest;
use Modules\Team\Presentation\Resources\TeamResource;
use Modules\Team\Application\Services\TeamService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected TeamService $teamService
    ) {
    }

    /**
     * Display a listing of teams.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['search', 'owner_id']);

        $teams = $this->teamService->paginate($perPage, $filters);

        return $this->paginatedResponse($teams, 'Teams retrieved successfully');
    }

    /**
     * Store a newly created team.
     */
    public function store(CreateTeamRequest $request): JsonResponse
    {
        $team = $this->teamService->create($request->validated());

        return $this->successResponse(
            new TeamResource($team),
            'Team created successfully',
            201
        );
    }

    /**
     * Display the specified team.
     */
    public function show(string $id): JsonResponse
    {
        $team = $this->teamService->find($id);

        if (!$team) {
            return $this->notFoundResponse('Team not found.');
        }

        return $this->successResponse(
            new TeamResource($team),
            'Team retrieved successfully'
        );
    }

    /**
     * Update the specified team.
     */
    public function update(UpdateTeamRequest $request, string $id): JsonResponse
    {
        $team = $this->teamService->update($id, $request->validated());

        if (!$team) {
            return $this->notFoundResponse('Team not found.');
        }

        return $this->successResponse(
            new TeamResource($team),
            'Team updated successfully'
        );
    }

    /**
     * Remove the specified team.
     */
    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->teamService->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse('Team not found.');
        }

        return $this->successResponse(
            null,
            'Team deleted successfully'
        );
    }

    /**
     * Get teams by owner.
     */
    /**
     * Get teams by owner.
     */
    public function getByOwner(string $ownerId): JsonResponse
    {
        // Chargez les relations nécessaires mais pas l'owner pour éviter la boucle
        $teams = $this->teamService->findByOwner($ownerId);

        // Créez une collection manuelle sans utiliser TeamResource si nécessaire
        $teamsData = $teams->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'owner_id' => $team->owner_id,
                'is_public' => $team->is_public,
                'created_at' => $team->created_at,
                'updated_at' => $team->updated_at,
                'members_count' => $team->members()->count(),
            ];
        });

        return $this->successResponse(
            $teamsData, // Utilisez le tableau simple au lieu de TeamResource
            'Teams retrieved successfully'
        );
    }

    /**
     * Add member to team.
     */
    public function addMember(Request $request, int $teamId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $userId = $request->input('user_id');

        // Pas besoin de conversion en string puisque nous utilisons des entiers
        $added = $this->teamService->addMember($teamId, $userId);

        if (!$added) {
            return $this->errorResponse('Failed to add member to team.');
        }

        return $this->successResponse(
            null,
            'Member added successfully'
        );
    }

    /**
     * Remove member from team.
     */
    public function removeMember(Request $request, string $teamId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $userId = $request->input('user_id');

        // S'assurer que c'est une chaîne
        if (!is_string($userId)) {
            $userId = (string) $userId;
        }

        $removed = $this->teamService->removeMember($teamId, $userId);

        if (!$removed) {
            return $this->errorResponse('Failed to remove member from team.');
        }

        return $this->successResponse(
            null,
            'Member removed successfully'
        );
    }

    /**
     * Get team members.
     */
    public function getMembers(string $teamId): JsonResponse
    {
        $members = $this->teamService->getMembers($teamId);

        return $this->successResponse(
            $members,
            'Team members retrieved successfully'
        );
    }

    /**
     * Get teams for current user.
     */
    public function myTeams(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $teams = $this->teamService->getUserTeams($userId);

        return $this->successResponse(
            TeamResource::collection($teams),
            'User teams retrieved successfully'
        );
    }

    /**
     * Transfer team ownership.
     */
    public function transferOwnership(Request $request, string $teamId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_owner_id' => 'required|string|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $newOwnerId = $request->input('new_owner_id');

        // S'assurer que c'est une chaîne
        if (!is_string($newOwnerId)) {
            $newOwnerId = (string) $newOwnerId;
        }

        $team = $this->teamService->transferOwnership($teamId, $newOwnerId);

        if (!$team) {
            return $this->errorResponse('Team not found or failed to transfer ownership.');
        }

        return $this->successResponse(
            new TeamResource($team),
            'Ownership transferred successfully'
        );
    }

    /**
     * Get team statistics.
     */
    public function statistics(string $teamId): JsonResponse
    {
        $statistics = $this->teamService->getStatistics($teamId);

        if (empty($statistics)) {
            return $this->notFoundResponse('Team not found.');
        }

        return $this->successResponse(
            $statistics,
            'Team statistics retrieved successfully'
        );
    }

    /**
     * Search teams with advanced filters.
     */
    public function search(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $criteria = $request->only(['search', 'owner_id', 'is_public']);

        $teams = $this->teamService->search($criteria, $perPage);

        return $this->paginatedResponse($teams, 'Teams retrieved successfully');
    }

    /**
     * Check if user is team owner.
     */
    public function checkOwnership(Request $request, string $teamId): JsonResponse
    {
        $userId = $request->user()->id;
        $isOwner = $this->teamService->isOwner($teamId, $userId);

        return $this->successResponse(
            ['is_owner' => $isOwner],
            'Ownership check completed'
        );
    }

    /**
     * Check if user is team member.
     */
    public function checkMembership(Request $request, string $teamId): JsonResponse
    {
        $userId = $request->user()->id;
        $isMember = $this->teamService->isMember($teamId, $userId);

        return $this->successResponse(
            ['is_member' => $isMember],
            'Membership check completed'
        );
    }
}