import { api } from "@/services/api";
import type {
    ProjectTeam,
    CreateProjectTeamData,
    UpdateProjectTeamData,
    ProjectTeamFilter,
    PaginatedProjectsTeams,
    ProjectStatistics,
    AssignableUser,
} from "@/types/projectsTeams";

// Interfaces pour les réponses API
interface ApiResponse<T> {
    data: T;
}

interface PaginatedApiResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from?: number;
        to?: number;
    };
}

// Type guards pour vérifier la structure des réponses
function isPaginatedResponse<T>(
    response: unknown
): response is PaginatedApiResponse<T> {
    return (
        !!response &&
        typeof response === "object" &&
        "data" in response &&
        Array.isArray((response as any).data) &&
        "meta" in response &&
        typeof (response as any).meta === "object" &&
        "current_page" in (response as any).meta
    );
}

function isWrappedResponse<T>(response: unknown): response is ApiResponse<T> {
    return !!response && typeof response === "object" && "data" in response;
}

class ProjectsTeamsService {
    private basePath = "/projectsTeams";

    // Helper pour extraire les données de la réponse
    private unwrapResponse<T>(response: unknown): T {
        if (isWrappedResponse<T>(response)) {
            return response.data;
        }
        return response as T;
    }

    private unwrapPaginatedResponse<T>(
        response: unknown
    ): PaginatedApiResponse<T> {
        if (isPaginatedResponse<T>(response)) {
            return response;
        }

        // Si c'est déjà un tableau
        if (Array.isArray(response)) {
            return {
                data: response as T[],
                meta: {
                    current_page: 1,
                    last_page: 1,
                    per_page: response.length,
                    total: response.length,
                    from: 1,
                    to: response.length,
                },
            };
        }

        // Fallback
        return {
            data: [] as T[],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0,
            },
        };
    }

    async getProjects(
        params?: ProjectTeamFilter
    ): Promise<PaginatedProjectsTeams> {
        try {
            const response = await api.get<unknown>(this.basePath, { params });
            const paginatedResponse =
                this.unwrapPaginatedResponse<ProjectTeam>(response);

            return {
                data: paginatedResponse.data,
                current_page: paginatedResponse.meta.current_page,
                last_page: paginatedResponse.meta.last_page,
                per_page: paginatedResponse.meta.per_page,
                total: paginatedResponse.meta.total,
                from: paginatedResponse.meta.from || 1,
                to: paginatedResponse.meta.to || paginatedResponse.data.length,
            };
        } catch (error) {
            console.error("❌ ProjectsTeamsService.getProjects error:", error);
            throw error;
        }
    }

    async getProjectById(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.get<unknown>(`${this.basePath}/${id}`);
            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectById(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async getProjectWithTeam(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/${id}/with-team`
            );
            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectWithTeam(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async createProject(data: CreateProjectTeamData): Promise<ProjectTeam> {
        try {
            const response = await api.post<unknown>(this.basePath, data);
            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error("ProjectsTeamsService.createProject error:", error);
            throw error;
        }
    }

    async updateProject(
        id: number,
        data: UpdateProjectTeamData
    ): Promise<ProjectTeam> {
        try {
            const response = await api.put<unknown>(
                `${this.basePath}/${id}`,
                data
            );
            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.updateProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async deleteProject(id: number): Promise<void> {
        try {
            await api.delete<void>(`${this.basePath}/${id}`);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.deleteProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async restoreProject(id: number): Promise<void> {
        try {
            await api.put<void>(`${this.basePath}/${id}/restore`);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.restoreProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async getProjectsByTeam(
        teamId: number,
        params?: ProjectTeamFilter
    ): Promise<PaginatedProjectsTeams> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/team/${teamId}`,
                { params }
            );

            const paginatedResponse =
                this.unwrapPaginatedResponse<ProjectTeam>(response);

            return {
                data: paginatedResponse.data,
                current_page: paginatedResponse.meta.current_page,
                last_page: paginatedResponse.meta.last_page,
                per_page: paginatedResponse.meta.per_page,
                total: paginatedResponse.meta.total,
                from: paginatedResponse.meta.from || 1,
                to: paginatedResponse.meta.to || paginatedResponse.data.length,
            };
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectsByTeam(${teamId}) error:`,
                error
            );
            throw error;
        }
    }

    async searchProjects(
        query: string,
        params?: ProjectTeamFilter
    ): Promise<ProjectTeam[]> {
        try {
            const response = await api.get<unknown>(`${this.basePath}/search`, {
                params: { query, ...params },
            });

            const unwrapped = this.unwrapResponse<
                ProjectTeam[] | ProjectTeam[]
            >(response);
            return Array.isArray(unwrapped) ? unwrapped : [];
        } catch (error) {
            console.error("ProjectsTeamsService.searchProjects error:", error);
            throw error;
        }
    }

    async getProjectsByStatus(status: string): Promise<ProjectTeam[]> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/status/${status}`
            );

            const unwrapped = this.unwrapResponse<
                ProjectTeam[] | ProjectTeam[]
            >(response);
            return Array.isArray(unwrapped) ? unwrapped : [];
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectsByStatus(${status}) error:`,
                error
            );
            throw error;
        }
    }

    async getUpcomingProjects(days: number = 7): Promise<ProjectTeam[]> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/upcoming`,
                {
                    params: { days },
                }
            );

            const unwrapped = this.unwrapResponse<
                ProjectTeam[] | ProjectTeam[]
            >(response);
            return Array.isArray(unwrapped) ? unwrapped : [];
        } catch (error) {
            console.error(
                "ProjectsTeamsService.getUpcomingProjects error:",
                error
            );
            throw error;
        }
    }

    async getStatistics(teamId?: number): Promise<ProjectStatistics> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/statistics`,
                {
                    params: teamId ? { team_id: teamId } : {},
                }
            );

            return this.unwrapResponse<ProjectStatistics>(response);
        } catch (error) {
            console.error("ProjectsTeamsService.getStatistics error:", error);
            throw error;
        }
    }

    async updateProjectStatus(
        id: number,
        status: string
    ): Promise<ProjectTeam> {
        try {
            const response = await api.put<unknown>(
                `${this.basePath}/${id}/status`,
                { status }
            );

            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.updateProjectStatus(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async completeProject(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.put<unknown>(
                `${this.basePath}/${id}/complete`
            );

            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.completeProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async putProjectOnHold(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.put<unknown>(
                `${this.basePath}/${id}/on-hold`
            );

            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.putProjectOnHold(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async cancelProject(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.put<unknown>(
                `${this.basePath}/${id}/cancel`
            );

            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.cancelProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async reactivateProject(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.put<unknown>(
                `${this.basePath}/${id}/reactivate`
            );

            return this.unwrapResponse<ProjectTeam>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.reactivateProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    async getProjectTeamUsers(projectId: number): Promise<any[]> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/${projectId}/team-users`
            );

            const unwrapped = this.unwrapResponse<any[] | any[]>(response);
            return Array.isArray(unwrapped) ? unwrapped : [];
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectTeamUsers(${projectId}) error:`,
                error
            );
            throw error;
        }
    }

    async getAssignableUsers(projectId: number): Promise<AssignableUser[]> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/${projectId}/assignable-users`
            );

            const unwrapped = this.unwrapResponse<
                AssignableUser[] | AssignableUser[]
            >(response);
            return Array.isArray(unwrapped) ? unwrapped : [];
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getAssignableUsers(${projectId}) error:`,
                error
            );
            throw error;
        }
    }

    async checkTeamMembership(
        projectId: number,
        teamId: number
    ): Promise<{ belongs: boolean }> {
        try {
            const response = await api.get<unknown>(
                `${this.basePath}/${projectId}/check-team/${teamId}`
            );

            return this.unwrapResponse<{ belongs: boolean }>(response);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.checkTeamMembership(${projectId}, ${teamId}) error:`,
                error
            );
            throw error;
        }
    }

    async healthCheck(): Promise<any> {
        try {
            const response = await api.get<unknown>(`${this.basePath}/health`);
            return this.unwrapResponse<any>(response);
        } catch (error) {
            console.error("ProjectsTeamsService.healthCheck error:", error);
            throw error;
        }
    }
}

// Export singleton instance
export const projectsTeamsService = new ProjectsTeamsService();
