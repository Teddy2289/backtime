// services/team.service.ts
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
    id: string; // Ce sera l'user_id de l'API
    name: string;
    email: string;
    role?: string;
    joined_at: string;
    user_id?: string; // Pour conserver l'user_id original
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
    async createTeam(data: {
        name: string;
        description?: string;
        is_public?: boolean;
    }): Promise<Team> {
        const response = await api.post(this.protectedUrl, data);
        return response.data.data;
    }

    async updateTeam(
        id: string,
        data: {
            name?: string;
            description?: string;
            is_public?: boolean;
        }
    ): Promise<Team> {
        const response = await api.put(`${this.protectedUrl}/${id}`, data);
        return response.data.data;
    }

    async deleteTeam(id: string): Promise<void> {
        await api.delete(`${this.protectedUrl}/${id}`);
    }

    async getTeamMembers(teamId: string): Promise<TeamMember[]> {
        try {
            const response = await api.get(
                `${this.protectedUrl}/${teamId}/members`
            );

            // Vérifiez la structure complète de la réponse
            console.log("Réponse API complète:", response);
            console.log("Réponse data:", response.data);

            // Votre API retourne: {success, message, data, timestamp}
            // Le tableau de membres est dans response.data.data
            const apiResponse = response.data;

            // Vérifiez si data existe et est un tableau
            const membersData = apiResponse?.data || [];
            console.log("Données membres brutes:", membersData);

            // Transformez les données
            const members = membersData.map((member: any) => ({
                id: String(member.user_id || member.id), // ID pour l'affichage
                name: member.name || "",
                email: member.email || "",
                role: member.role,
                joined_at: member.joined_at || member.created_at,
                user_id: String(member.user_id || member.id), // ID original
            }));

            console.log("Membres transformés:", members);
            return members;
        } catch (error) {
            console.error("Erreur dans getTeamMembers:", error);
            throw error;
        }
    }

    async getTeamStatistics(teamId: string): Promise<TeamStatistics> {
        const response = await api.get(
            `${this.protectedUrl}/${teamId}/statistics`
        );

        // Même logique: extraire response.data.data
        const apiResponse = response.data;
        console.log("Réponse stats complète:", apiResponse);
        console.log("Données stats (apiResponse.data):", apiResponse.data);

        // Retournez directement les données statistiques
        return apiResponse.data;
    }

    // === Opérations CRUD ===

    // services/team.service.ts
    // services/team.service.ts
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

        const response = await api.get(this.baseUrl, { params });

        // 🔥 Votre API retourne: {success, message, data: [], meta: {}}
        const apiResponse = response.data;

        console.log("API Response structure:", apiResponse);
        console.log("Teams data:", apiResponse.data);

        // Retournez la structure attendue par votre store
        return {
            data: apiResponse.data || [], // ⬅️ C'est ICI que sont les équipes
            meta: apiResponse.meta || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            },
        };
    }

    async getTeam(id: string): Promise<Team> {
        const response = await api.get(`${this.baseUrl}/${id}`);
        const apiResponse = response.data;
        return apiResponse.data; // Extrait les données de l'équipe
    }
    async addMember(teamId: string, userId: string): Promise<void> {
        await api.post(`${this.protectedUrl}/${teamId}/members`, {
            user_id: userId,
        });
    }

    async removeMember(teamId: string, userId: string): Promise<void> {
        await api.delete(`${this.protectedUrl}/${teamId}/members/${userId}`);
    }

    // === Opérations spécifiques ===

    async getMyTeams(): Promise<Team[]> {
        const response = await api.get(`${this.protectedUrl}/user/my-teams`);
        const apiResponse = response.data;

        // Cette route retourne {success, message, data: {data: [...]}}
        // Donc on doit extraire apiResponse.data.data
        return apiResponse.data?.data || [];
    }

    async getTeamsByOwner(ownerId: string): Promise<Team[]> {
        const response = await api.get(`${this.protectedUrl}/owner/${ownerId}`);
        return response.data.data;
    }

    async transferOwnership(teamId: string, newOwnerId: string): Promise<Team> {
        const response = await api.post(
            `${this.protectedUrl}/${teamId}/transfer-ownership`,
            {
                new_owner_id: newOwnerId,
            }
        );
        return response.data.data;
    }

    async checkOwnership(teamId: string): Promise<{ is_owner: boolean }> {
        const response = await api.get(
            `${this.protectedUrl}/${teamId}/check-ownership`
        );
        return response.data.data;
    }

    async checkMembership(teamId: string): Promise<{ is_member: boolean }> {
        const response = await api.get(
            `${this.protectedUrl}/${teamId}/check-membership`
        );
        return response.data.data;
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
        const response = await api.post(`${this.baseUrl}/search`, criteria, {
            params,
        });
        return response.data;
    }

    async advancedSearch(criteria: any): Promise<PaginatedResponse<Team>> {
        const response = await api.post(
            `${this.protectedUrl}/search/advanced`,
            criteria
        );
        return response.data;
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
