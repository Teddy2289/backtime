import { defineStore } from "pinia";
import { projectsTeamsService } from "@/services/projectsTeams.service";
import type {
    ProjectTeam,
    ProjectTeamFilter,
    CreateProjectTeamData,
    UpdateProjectTeamData,
    ProjectStatistics,
    AssignableUser,
} from "@/types/projectsTeams";

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface ProjectTeamState {
    projects: ProjectTeam[];
    currentProject: ProjectTeam | null;
    pagination: Pagination | null;
    filters: ProjectTeamFilter;
    statistics: ProjectStatistics | null;
    assignableUsers: AssignableUser[];
    loading: boolean;
    error: string | null;
}

export const useProjectTeamStore = defineStore("projectsTeams", {
    state: (): ProjectTeamState => ({
        projects: [],
        currentProject: null,
        pagination: null,
        filters: {},
        statistics: null,
        assignableUsers: [],
        loading: false,
        error: null,
    }),

    getters: {
        getProjects: (state) => state.projects,
        getCurrentProject: (state) => state.currentProject,
        getPagination: (state) => state.pagination,
        getStatistics: (state) => state.statistics,
        getAssignableUsers: (state) => state.assignableUsers,
        getLoading: (state) => state.loading,
        getError: (state) => state.error,

        getProjectById: (state) => (id: number) => {
            return state.projects.find((project) => project.id === id);
        },

        getProjectsByTeam: (state) => (teamId: number) => {
            return state.projects.filter(
                (project) => project.team_id === teamId
            );
        },

        getProjectsByStatus: (state) => (status: string) => {
            return state.projects.filter(
                (project) => project.status === status
            );
        },
    },

    actions: {
        async fetchProjects(params?: ProjectTeamFilter) {
            try {
                this.loading = true;
                this.error = null;

                // Merge filters
                const newFilters = { ...this.filters, ...params };
                this.filters = newFilters;

                // Call service
                const paginatedResponse =
                    await projectsTeamsService.getProjects(newFilters);

                // 1. Vérifiez que data existe
                if (
                    !paginatedResponse.data ||
                    !Array.isArray(paginatedResponse.data)
                ) {
                    console.error(
                        "❌ Store: Response doesn't have valid data property"
                    );
                    this.projects = [];
                    this.pagination = null;
                    return paginatedResponse;
                }

                // 2. Assignez les données
                this.projects = paginatedResponse.data;

                // 3. Créez l'objet pagination
                this.pagination = {
                    current_page: paginatedResponse.current_page || 1,
                    last_page: paginatedResponse.last_page || 1,
                    per_page:
                        paginatedResponse.per_page ||
                        paginatedResponse.data.length,
                    total:
                        paginatedResponse.total ||
                        paginatedResponse.data.length,
                    from: paginatedResponse.from || 1,
                    to: paginatedResponse.to || paginatedResponse.data.length,
                };

                return paginatedResponse;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to fetch projects";
                console.error("Store fetchProjects error:", error);

                // Réinitialiser en cas d'erreur
                this.projects = [];
                this.pagination = null;

                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchProject(id: number): Promise<ProjectTeam> {
            try {
                this.loading = true;
                this.error = null;

                const project = await projectsTeamsService.getProjectById(id);
                this.currentProject = project;

                // Update in list if present
                const index = this.projects.findIndex((p) => p.id === id);
                if (index !== -1) {
                    this.projects[index] = project;
                }

                return project;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to fetch project";
                console.error(`Store fetchProject(${id}) error:`, error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchProjectWithTeam(id: number): Promise<ProjectTeam> {
            try {
                this.loading = true;
                this.error = null;

                const project = await projectsTeamsService.getProjectWithTeam(
                    id
                );
                this.currentProject = project;

                return project;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to fetch project with team";
                console.error(
                    `Store fetchProjectWithTeam(${id}) error:`,
                    error
                );
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createProject(data: CreateProjectTeamData): Promise<ProjectTeam> {
            try {
                this.loading = true;
                this.error = null;

                const project = await projectsTeamsService.createProject(data);

                // Add to beginning of list
                this.projects.unshift(project);

                // Update pagination total
                if (this.pagination) {
                    this.pagination.total++;
                }

                return project;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to create project";
                console.error("Store createProject error:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateProject(
            id: number,
            data: UpdateProjectTeamData
        ): Promise<ProjectTeam> {
            try {
                this.loading = true;
                this.error = null;

                const project = await projectsTeamsService.updateProject(
                    id,
                    data
                );

                // Update in list
                const index = this.projects.findIndex((p) => p.id === id);
                if (index !== -1) {
                    Object.assign(this.projects[index], project);
                }

                // Update current project
                if (this.currentProject?.id === id) {
                    this.currentProject = project;
                }

                return project;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to update project";
                console.error(`Store updateProject(${id}) error:`, error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteProject(id: number): Promise<void> {
            try {
                this.loading = true;
                this.error = null;

                await projectsTeamsService.deleteProject(id);

                // Remove from list
                this.projects = this.projects.filter((p) => p.id !== id);

                // Reset current project if it's the one deleted
                if (this.currentProject?.id === id) {
                    this.currentProject = null;
                }

                // Update pagination
                if (this.pagination) {
                    this.pagination.total--;
                    // If current page is empty and not first page, go to previous page
                    if (
                        this.projects.length === 0 &&
                        this.pagination.current_page > 1
                    ) {
                        await this.fetchProjects({
                            page: this.pagination.current_page - 1,
                        });
                    }
                }
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to delete project";
                console.error(`Store deleteProject(${id}) error:`, error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async restoreProject(id: number): Promise<void> {
            try {
                this.loading = true;
                this.error = null;

                await projectsTeamsService.restoreProject(id);

                // Fetch the project again to add to list
                // const project = await this.fetchProject(id);

                // Update pagination total
                if (this.pagination) {
                    this.pagination.total++;
                }
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to restore project";
                console.error(`Store restoreProject(${id}) error:`, error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchStatistics(teamId?: number): Promise<ProjectStatistics> {
            try {
                this.loading = true;
                this.error = null;

                const stats = await projectsTeamsService.getStatistics(teamId);
                this.statistics = stats;

                return stats;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to fetch statistics";
                console.error("Store fetchStatistics error:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchAssignableUsers(
            projectId: number
        ): Promise<AssignableUser[]> {
            try {
                this.loading = true;
                this.error = null;

                const users = await projectsTeamsService.getAssignableUsers(
                    projectId
                );
                this.assignableUsers = users;

                return users;
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to fetch assignable users";
                console.error(
                    `Store fetchAssignableUsers(${projectId}) error:`,
                    error
                );
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Status management actions
        async updateProjectStatus(
            id: number,
            status: string
        ): Promise<ProjectTeam> {
            try {
                const updatedProject =
                    await projectsTeamsService.updateProjectStatus(id, status);
                return await this.updateProject(id, updatedProject as any);
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to update project status";
                throw error;
            }
        },

        async completeProject(id: number): Promise<ProjectTeam> {
            try {
                const completedProject =
                    await projectsTeamsService.completeProject(id);
                return await this.updateProject(id, completedProject as any);
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to complete project";
                throw error;
            }
        },

        async putProjectOnHold(id: number): Promise<ProjectTeam> {
            try {
                const updatedProject =
                    await projectsTeamsService.putProjectOnHold(id);
                return await this.updateProject(id, updatedProject as any);
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to put project on hold";
                throw error;
            }
        },

        async cancelProject(id: number): Promise<ProjectTeam> {
            try {
                const updatedProject = await projectsTeamsService.cancelProject(
                    id
                );
                return await this.updateProject(id, updatedProject as any);
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to cancel project";
                throw error;
            }
        },

        async reactivateProject(id: number): Promise<ProjectTeam> {
            try {
                const updatedProject =
                    await projectsTeamsService.reactivateProject(id);
                return await this.updateProject(id, updatedProject as any);
            } catch (error: any) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    "Failed to reactivate project";
                throw error;
            }
        },

        // Clear current project
        clearCurrentProject(): void {
            this.currentProject = null;
        },

        // Clear error
        clearError(): void {
            this.error = null;
        },

        // Reset store
        reset(): void {
            this.projects = [];
            this.currentProject = null;
            this.pagination = null;
            this.filters = {};
            this.statistics = null;
            this.assignableUsers = [];
            this.error = null;
            this.loading = false;
        },
    },
});
