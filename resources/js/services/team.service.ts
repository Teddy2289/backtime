import { api } from "../services/api";

// Types TypeScript pour une meilleure sécurité
export interface Team {
    id: string;
    name: string;
    description: string | null;
    owner_id: string;
    is_public: boolean;
    created_at: string;
    updated_at: string;
    members_count?: number;
    owner?: {
        id: string;
        name: string;
        email: string;
    };
}

export interface TeamMember {
    id: string;
    name: string;
    email: string;
    role?: string;
    joined_at: string;
}

export interface TeamStatistics {
    total_members: number;
    total_projects: number;
    active_projects: number;
    completed_projects: number;
    avg_members_per_team: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export interface TeamFilters {
    search?: string;
    owner_id?: string;
    is_public?: boolean;
    page?: number;
    per_page?: number;
}

class TeamService {
    // Configuration de base
    private baseUrl = "/teams";
    private protectedUrl = "/teamsCrud";

    // === Opérations CRUD ===

    async getTeams(
        filters: TeamFilters = {}
    ): Promise<PaginatedResponse<Team>> {
        const params = {
            page: filters.page || 1,
            per_page: filters.per_page || 15,
            ...(filters.search && { search: filters.search }),
            ...(filters.owner_id && { owner_id: filters.owner_id }),
            ...(filters.is_public !== undefined && {
                is_public: filters.is_public,
            }),
        };

        return api.get(this.baseUrl, { params });
    }

    async getTeam(id: string): Promise<Team> {
        return api.get(`${this.baseUrl}/${id}`);
    }

    async createTeam(data: {
        name: string;
        description?: string;
        is_public?: boolean;
    }): Promise<Team> {
        return api.post(this.protectedUrl, data);
    }

    async updateTeam(
        id: string,
        data: {
            name?: string;
            description?: string;
            is_public?: boolean;
        }
    ): Promise<Team> {
        return api.put(`${this.protectedUrl}/${id}`, data);
    }

    async deleteTeam(id: string): Promise<void> {
        return api.delete(`${this.protectedUrl}/${id}`);
    }

    // === Gestion des membres ===

    async getTeamMembers(teamId: string): Promise<TeamMember[]> {
        return api.get(`${this.protectedUrl}/${teamId}/members`);
    }

    async addMember(teamId: string, userId: string): Promise<void> {
        return api.post(`${this.protectedUrl}/${teamId}/members`, {
            user_id: userId,
        });
    }

    async removeMember(teamId: string, userId: string): Promise<void> {
        return api.delete(`${this.protectedUrl}/${teamId}/members/${userId}`);
    }

    // === Opérations spécifiques ===

    async getMyTeams(): Promise<Team[]> {
        return api.get(`${this.protectedUrl}/user/my-teams`);
    }

    async getTeamsByOwner(ownerId: string): Promise<Team[]> {
        return api.get(`${this.protectedUrl}/owner/${ownerId}`);
    }

    async getTeamStatistics(teamId: string): Promise<TeamStatistics> {
        return api.get(`${this.protectedUrl}/${teamId}/statistics`);
    }

    async transferOwnership(teamId: string, newOwnerId: string): Promise<Team> {
        return api.post(`${this.protectedUrl}/${teamId}/transfer-ownership`, {
            new_owner_id: newOwnerId,
        });
    }

    async checkOwnership(teamId: string): Promise<{ is_owner: boolean }> {
        return api.get(`${this.protectedUrl}/${teamId}/check-ownership`);
    }

    async checkMembership(teamId: string): Promise<{ is_member: boolean }> {
        return api.get(`${this.protectedUrl}/${teamId}/check-membership`);
    }

    async searchTeams(
        criteria: {
            search?: string;
            owner_id?: string;
            is_public?: boolean;
        },
        perPage: number = 15
    ): Promise<PaginatedResponse<Team>> {
        const params = {
            per_page: perPage,
            ...criteria,
        };
        return api.post(`${this.baseUrl}/search`, criteria, { params });
    }

    async advancedSearch(criteria: any): Promise<PaginatedResponse<Team>> {
        return api.post(`${this.protectedUrl}/search/advanced`, criteria);
    }

    // === Méthodes utilitaires ===

    async healthCheck(): Promise<any> {
        return api.get("/team/health");
    }

    // Méthode pour vérifier les permissions (réutilisable)
    canEditTeam(team: Team, currentUserId: string): boolean {
        return team.owner_id === currentUserId;
    }

    canDeleteTeam(team: Team, currentUserId: string): boolean {
        return team.owner_id === currentUserId;
    }

    async canAddMembers(team: Team, currentUserId: string): Promise<boolean> {
        return (
            team.owner_id === currentUserId ||
            (team.is_public &&
                (await this.checkMembership(team.id).then((r) => r.is_member)))
        );
    }
}

// Export d'une instance unique (Singleton)
export const teamService = new TeamService();
