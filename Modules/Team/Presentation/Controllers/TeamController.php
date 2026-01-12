<?php

namespace Modules\Team\Presentation\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Team\Application\Models\Team;
use Modules\Team\Application\Services\TeamService;
use Modules\Team\Presentation\Requests\CreateTeamRequest;
use Modules\Team\Presentation\Requests\UpdateTeamRequest;
use Modules\Team\Presentation\Requests\AddMemberRequest;
use Modules\Team\Presentation\Requests\TransferOwnershipRequest;
use Modules\Team\Presentation\Resources\TeamResource;
use Modules\Team\Presentation\Resources\TeamMemberResource;

class TeamController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected TeamService $teamService
    ) {
    }

    /**
     * Liste des équipes (publique)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['search', 'owner_id', 'is_public']);

        $teams = $this->teamService->paginate($perPage, $filters);

        return $this->paginatedResponse(
            TeamResource::collection($teams),
            'Teams retrieved successfully'
        );
    }

    /**
     * Équipes publiques
     */
    public function publicTeams(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $teams = $this->teamService->getPublicTeams($perPage);

        return $this->paginatedResponse(
            TeamResource::collection($teams),
            'Public teams retrieved successfully'
        );
    }

    /**
     * Créer une équipe
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
     * Afficher une équipe
     */
    public function show(string $id): JsonResponse
    {
        $team = $this->teamService->find($id);

        if (!$team) {
            return $this->notFoundResponse('Team not found');
        }

        return $this->successResponse(
            new TeamResource($team),
            'Team retrieved successfully'
        );
    }

    /**
     * Mettre à jour une équipe
     */
    public function update(UpdateTeamRequest $request, string $id): JsonResponse
    {
        $team = $this->teamService->update($id, $request->validated());

        if (!$team) {
            return $this->notFoundResponse('Team not found');
        }

        return $this->successResponse(
            new TeamResource($team),
            'Team updated successfully'
        );
    }

    /**
     * Supprimer une équipe
     */
    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->teamService->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse('Team not found');
        }

        return $this->successResponse(
            null,
            'Team deleted successfully'
        );
    }

    /**
     * Équipes par propriétaire
     */
    public function getByOwner(string $ownerId): JsonResponse
    {
        $teams = $this->teamService->findByOwner($ownerId);

        return $this->successResponse(
            TeamResource::collection($teams),
            'Teams retrieved successfully'
        );
    }

    /**
     * Ajouter un membre
     */
    public function addMember(Request $request, int $teamId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $added = $this->teamService->addMember(
            $teamId,
            $validated['user_id']
        );

        if (!$added) {
            return $this->errorResponse('Failed to add member to team');
        }

        return $this->successResponse(
            null,
            'Member added successfully'
        );
    }

    /**
     * Retirer un membre
     */
    public function removeMember(string $teamId, string $userId): JsonResponse
    {
        $removed = $this->teamService->removeMember($teamId, $userId);

        if (!$removed) {
            return $this->errorResponse('Failed to remove member from team');
        }

        return $this->successResponse(
            null,
            'Member removed successfully'
        );
    }

    /**
     * Liste des membres
     */
    public function getMembers(string $teamId): JsonResponse
    {
        $team = $this->teamService->find($teamId);

        if (!$team) {
            return $this->notFoundResponse('Team not found');
        }

        try {
            // Solution simple sans la colonne role
            $members = DB::table('team_members')
                ->join('users', 'team_members.user_id', '=', 'users.id')
                ->where('team_members.team_id', $teamId)
                ->whereNull('users.deleted_at')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'team_members.created_at as joined_at',
                    'team_members.user_id'
                ])
                ->get()
                ->map(function ($member) use ($team) {
                    return [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'joined_at' => $member->joined_at,
                        // Déterminez le rôle en fonction du propriétaire
                        'is_owner' => $member->user_id == $team->owner_id,
                    ];
                });

            return $this->successResponse(
                $members,
                'Team members retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve team members: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Mes équipes (utilisateur courant)
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
     * Transférer la propriété
     */
    public function transferOwnership(TransferOwnershipRequest $request, string $teamId): JsonResponse
    {
        $team = $this->teamService->transferOwnership(
            $teamId,
            $request->validated()['new_owner_id']
        );

        if (!$team) {
            return $this->errorResponse('Team not found or failed to transfer ownership');
        }

        return $this->successResponse(
            new TeamResource($team),
            'Ownership transferred successfully'
        );
    }

    /**
     * Statistiques de l'équipe
     */
    public function statistics(string $teamId): JsonResponse
    {
        $statistics = $this->teamService->getStatistics($teamId);

        if (!$statistics) {
            return $this->notFoundResponse('Team not found');
        }

        return $this->successResponse(
            $statistics,
            'Team statistics retrieved successfully'
        );
    }

    /**
     * Recherche simple (publique)
     */
    public function search(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $criteria = $request->only(['search', 'owner_id', 'is_public']);

        $teams = $this->teamService->search($criteria, $perPage);

        return $this->paginatedResponse(
            TeamResource::collection($teams),
            'Teams retrieved successfully'
        );
    }

    /**
     * Recherche avancée (protégée)
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $criteria = $request->only([
            'search',
            'owner_id',
            'is_public',
            'created_from',
            'created_to',
            'min_members',
            'max_members'
        ]);

        $teams = $this->teamService->advancedSearch($criteria, $perPage);

        return $this->paginatedResponse(
            TeamResource::collection($teams),
            'Teams retrieved successfully'
        );
    }

    /**
     * Vérifier la propriété
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
     * Vérifier l'appartenance
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