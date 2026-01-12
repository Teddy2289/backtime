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

class ProjectsTeamsService {
    private basePath = "/projectsTeams";

    async getProjects(
        params?: ProjectTeamFilter
    ): Promise<PaginatedProjectsTeams> {
        try {
            // IMPORTANT: Utilisez l'intercepteur d'api qui gère déjà response.data
            const response = await api.get<any>(this.basePath, { params });

            // Si l'API retourne { data: [...], meta: {...} }
            if (response && typeof response === "object") {
                // Vérifiez si c'est la structure Laravel typique
                if (response.data && Array.isArray(response.data)) {
                    return {
                        data: response.data,
                        current_page: response.meta?.current_page || 1,
                        last_page: response.meta?.last_page || 1,
                        per_page: response.meta?.per_page || 15,
                        total: response.meta?.total || response.data.length,
                        from: response.meta?.from || 1,
                        to: response.meta?.to || response.data.length,
                    };
                }

                // Si response est déjà un tableau (sans wrapper)
                if (Array.isArray(response)) {
                    return {
                        data: response,
                        current_page: 1,
                        last_page: 1,
                        per_page: response.length,
                        total: response.length,
                        from: 1,
                        to: response.length,
                    };
                }
            }

            // Fallback
            return {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0,
            };
        } catch (error) {
            console.error("❌ ProjectsTeamsService.getProjects error:", error);
            throw error;
        }
    }

    async getProjectById(id: number): Promise<ProjectTeam> {
        try {
            // api.get extrait déjà data, donc on reçoit directement l'objet ProjectTeam
            return await api.get<ProjectTeam>(`${this.basePath}/${id}`);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectById(${id}) error:`,
                error
            );
            throw error;
        }
    }

    // Get project with team details
    async getProjectWithTeam(id: number): Promise<ProjectTeam> {
        try {
            return await api.get<ProjectTeam>(
                `${this.basePath}/${id}/with-team`
            );
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectWithTeam(${id}) error:`,
                error
            );
            throw error;
        }
    }

    // Create new project
    async createProject(data: CreateProjectTeamData): Promise<ProjectTeam> {
        try {
            return await api.post<ProjectTeam>(this.basePath, data);
        } catch (error) {
            console.error("ProjectsTeamsService.createProject error:", error);
            throw error;
        }
    }

    // Update project
    async updateProject(
        id: number,
        data: UpdateProjectTeamData
    ): Promise<ProjectTeam> {
        try {
            return await api.put<ProjectTeam>(`${this.basePath}/${id}`, data);
        } catch (error) {
            console.error(
                `ProjectsTeamsService.updateProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    // Delete project
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

    // Restore project
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

    // Get projects by team - adapté comme getProjects
    async getProjectsByTeam(
        teamId: number,
        params?: ProjectTeamFilter
    ): Promise<PaginatedProjectsTeams> {
        try {
            const response = await api.get<any>(
                `${this.basePath}/team/${teamId}`,
                { params }
            );

            console.log("getProjectsByTeam - Raw response:", response);

            // Même logique que getProjects
            if (response && typeof response === "object") {
                if (
                    response.data !== undefined &&
                    response.meta !== undefined
                ) {
                    return {
                        data: response.data,
                        current_page: response.meta.current_page,
                        last_page: response.meta.last_page,
                        per_page: response.meta.per_page,
                        total: response.meta.total,
                        from: response.meta.from,
                        to: response.meta.to,
                    };
                }

                if (Array.isArray(response)) {
                    return {
                        data: response,
                        current_page: 1,
                        last_page: 1,
                        per_page: response.length,
                        total: response.length,
                        from: 1,
                        to: response.length,
                    };
                }

                if (
                    response.data !== undefined &&
                    Array.isArray(response.data)
                ) {
                    return {
                        data: response.data,
                        current_page: 1,
                        last_page: 1,
                        per_page: response.data.length,
                        total: response.data.length,
                        from: 1,
                        to: response.data.length,
                    };
                }
            }

            return {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0,
            };
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectsByTeam(${teamId}) error:`,
                error
            );
            throw error;
        }
    }

    // Search projects
    async searchProjects(
        query: string,
        params?: ProjectTeamFilter
    ): Promise<ProjectTeam[]> {
        try {
            const response = await api.get<ProjectTeam[]>(
                `${this.basePath}/search`,
                {
                    params: { query, ...params },
                }
            );

            // api.get extrait déjà data, on vérifie la structure
            if (response && typeof response === "object") {
                if (Array.isArray(response)) {
                    return response;
                }
                if (response.data && Array.isArray(response.data)) {
                    return response.data;
                }
            }

            return [];
        } catch (error) {
            console.error("ProjectsTeamsService.searchProjects error:", error);
            throw error;
        }
    }

    // Get projects by status
    async getProjectsByStatus(status: string): Promise<ProjectTeam[]> {
        try {
            const response = await api.get<ProjectTeam[]>(
                `${this.basePath}/status/${status}`
            );

            if (response && typeof response === "object") {
                if (Array.isArray(response)) {
                    return response;
                }
                if (response.data && Array.isArray(response.data)) {
                    return response.data;
                }
            }

            return [];
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectsByStatus(${status}) error:`,
                error
            );
            throw error;
        }
    }

    // Get upcoming projects
    async getUpcomingProjects(days: number = 7): Promise<ProjectTeam[]> {
        try {
            const response = await api.get<ProjectTeam[]>(
                `${this.basePath}/upcoming`,
                {
                    params: { days },
                }
            );

            if (response && typeof response === "object") {
                if (Array.isArray(response)) {
                    return response;
                }
                if (response.data && Array.isArray(response.data)) {
                    return response.data;
                }
            }

            return [];
        } catch (error) {
            console.error(
                "ProjectsTeamsService.getUpcomingProjects error:",
                error
            );
            throw error;
        }
    }

    // Get statistics
    async getStatistics(teamId?: number): Promise<ProjectStatistics> {
        try {
            const response = await api.get<ProjectStatistics>(
                `${this.basePath}/statistics`,
                {
                    params: teamId ? { team_id: teamId } : {},
                }
            );

            // api.get extrait déjà data
            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
        } catch (error) {
            console.error("ProjectsTeamsService.getStatistics error:", error);
            throw error;
        }
    }

    // Update project status
    async updateProjectStatus(
        id: number,
        status: string
    ): Promise<ProjectTeam> {
        try {
            const response = await api.put<ProjectTeam>(
                `${this.basePath}/${id}/status`,
                {
                    status,
                }
            );

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
        } catch (error) {
            console.error(
                `ProjectsTeamsService.updateProjectStatus(${id}) error:`,
                error
            );
            throw error;
        }
    }

    // Quick status actions
    async completeProject(id: number): Promise<ProjectTeam> {
        try {
            const response = await api.put<ProjectTeam>(
                `${this.basePath}/${id}/complete`
            );

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
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
            const response = await api.put<ProjectTeam>(
                `${this.basePath}/${id}/on-hold`
            );

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
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
            const response = await api.put<ProjectTeam>(
                `${this.basePath}/${id}/cancel`
            );

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
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
            const response = await api.put<ProjectTeam>(
                `${this.basePath}/${id}/reactivate`
            );

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
        } catch (error) {
            console.error(
                `ProjectsTeamsService.reactivateProject(${id}) error:`,
                error
            );
            throw error;
        }
    }

    // Get team members for a project
    async getProjectTeamUsers(projectId: number): Promise<any[]> {
        try {
            const response = await api.get<any[]>(
                `${this.basePath}/${projectId}/team-users`
            );

            if (response && typeof response === "object") {
                if (Array.isArray(response)) {
                    return response;
                }
                if (response.data && Array.isArray(response.data)) {
                    return response.data;
                }
            }

            return [];
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getProjectTeamUsers(${projectId}) error:`,
                error
            );
            throw error;
        }
    }

    // Get assignable users
    async getAssignableUsers(projectId: number): Promise<AssignableUser[]> {
        try {
            const response = await api.get<AssignableUser[]>(
                `${this.basePath}/${projectId}/assignable-users`
            );

            if (response && typeof response === "object") {
                if (Array.isArray(response)) {
                    return response;
                }
                if (response.data && Array.isArray(response.data)) {
                    return response.data;
                }
            }

            return [];
        } catch (error) {
            console.error(
                `ProjectsTeamsService.getAssignableUsers(${projectId}) error:`,
                error
            );
            throw error;
        }
    }

    // Check if project belongs to team
    async checkTeamMembership(
        projectId: number,
        teamId: number
    ): Promise<{ belongs: boolean }> {
        try {
            const response = await api.get<{ belongs: boolean }>(
                `${this.basePath}/${projectId}/check-team/${teamId}`
            );

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
        } catch (error) {
            console.error(
                `ProjectsTeamsService.checkTeamMembership(${projectId}, ${teamId}) error:`,
                error
            );
            throw error;
        }
    }

    // Health check
    async healthCheck(): Promise<any> {
        try {
            const response = await api.get<any>(`${this.basePath}/health`);

            if (response && typeof response === "object") {
                if (response.data) {
                    return response.data;
                }
                return response;
            }

            throw new Error("Invalid response format");
        } catch (error) {
            console.error("ProjectsTeamsService.healthCheck error:", error);
            throw error;
        }
    }
}

// Export singleton instance
export const projectsTeamsService = new ProjectsTeamsService();
