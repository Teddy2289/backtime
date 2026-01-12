<?php

namespace Modules\Team\Infrastructure\Repositories;

use Modules\Team\Domain\Entities\Team;
use Modules\Team\Domain\Interfaces\TeamRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TeamRepository implements TeamRepositoryInterface
{
    public function find(int $id): ?Team
    {
        return Team::with(['owner', 'members', 'projects'])->find($id);
    }

    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function update(int $id, array $data): ?Team
    {
        $team = $this->find($id);

        if (!$team) {
            return null;
        }

        $team->update($data);
        return $team->fresh(['owner', 'members']);
    }

    public function delete(int $id): bool
    {
        $team = $this->find($id);

        if (!$team) {
            return false;
        }

        return $team->delete();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Team::with(['owner']);

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByOwner(int $ownerId): Collection
    {
        return Team::with(['owner', 'members'])
            ->where('owner_id', $ownerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function addMember(int $teamId, int $userId): bool
    {
        // Check if already a member
        $exists = DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return true;
        }

        try {
            DB::table('team_members')->insert([
                'team_id' => $teamId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeMember(int $teamId, int $userId): bool
    {
        return DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function getMembers(int $teamId): Collection
    {
        return DB::table('team_members')
            ->join('users', 'team_members.user_id', '=', 'users.id')
            ->where('team_members.team_id', $teamId)
            ->select('users.*', 'team_members.created_at as joined_at')
            ->get()
            ->map(function ($item) {
                $item->user_id = $item->id;
                return $item;
            });
    }

    // IMPLÉMENTATION DES MÉTHODES AJOUTÉES DANS L'INTERFACE
    public function countByOwner(int $ownerId): int
    {
        return Team::where('owner_id', $ownerId)->count();
    }

    public function getPublicTeams(int $perPage = 15): LengthAwarePaginator
    {
        return Team::with(['owner'])
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserTeams(int $userId): Collection
    {
        // Teams where user is owner
        $ownedTeams = Team::where('owner_id', $userId)->get();

        // Teams where user is a member (but not owner)
        $memberTeams = DB::table('team_members')
            ->join('teams', 'team_members.team_id', '=', 'teams.id')
            ->where('team_members.user_id', $userId)
            ->where('teams.owner_id', '!=', $userId)
            ->select('teams.*')
            ->get();

        return $ownedTeams->merge($memberTeams);
    }
}