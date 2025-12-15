// src/services/task.service.ts
import { api } from "@/services/api";
import type {
    Task,
    PaginatedTasks,
    TaskFilter,
    TaskStatistics,
    CreateTaskData,
    UpdateTaskData,
} from "@/types/task";
import type { AxiosRequestConfig } from "axios";

class TaskService {
    // Routes principales
    public async getTasks(
        params?: TaskFilter & { per_page?: number; page?: number }
    ): Promise<PaginatedTasks> {
        const config: AxiosRequestConfig = {
            params: {
                ...params,
                per_page: params?.per_page || 15,
                page: params?.page || 1,
            },
        };

        return api.get<PaginatedTasks>("/tasks", config);
    }

    public async getTaskById(id: number): Promise<Task> {
        return api.get<Task>(`/tasks/${id}`);
    }

    public async createTask(data: CreateTaskData): Promise<Task> {
        return api.post<Task>("/tasks", data);
    }

    public async updateTask(id: number, data: UpdateTaskData): Promise<Task> {
        return api.put<Task>(`/tasks/${id}`, data);
    }

    public async deleteTask(id: number): Promise<void> {
        return api.delete(`/tasks/${id}`);
    }

    public async restoreTask(id: number): Promise<Task> {
        return api.post<Task>(`/tasks/${id}/restore`);
    }

    // Gestion des statuts
    public async completeTask(id: number): Promise<Task> {
        return api.post<Task>(`/tasks/${id}/complete`);
    }

    public async startTask(id: number): Promise<Task> {
        return api.post<Task>(`/tasks/${id}/start`);
    }

    public async resetTaskToTodo(id: number): Promise<Task> {
        return api.post<Task>(`/tasks/${id}/reset-todo`);
    }

    public async updateTaskStatus(id: number, status: string): Promise<Task> {
        return api.post<Task>(`/tasks/${id}/status`, { status });
    }

    public async assignTask(id: number, userId: number): Promise<Task> {
        return api.post<Task>(`/tasks/${id}/assign`, { user_id: userId });
    }

    // Filtres et recherches
    public async getTasksByProject(
        projectId: number,
        params?: Omit<TaskFilter, "project_id"> & {
            per_page?: number;
            page?: number;
        }
    ): Promise<PaginatedTasks> {
        const config: AxiosRequestConfig = {
            params: {
                ...params,
                per_page: params?.per_page || 15,
                page: params?.page || 1,
            },
        };

        return api.get<PaginatedTasks>(`/tasks/project/${projectId}`, config);
    }

    public async getTasksByAssignee(
        userId: number,
        params?: Omit<TaskFilter, "assigned_to"> & {
            per_page?: number;
            page?: number;
        }
    ): Promise<PaginatedTasks> {
        const config: AxiosRequestConfig = {
            params: {
                ...params,
                per_page: params?.per_page || 15,
                page: params?.page || 1,
            },
        };

        return api.get<PaginatedTasks>(`/tasks/user/${userId}`, config);
    }

    public async searchTasks(
        query: string,
        filters?: Omit<TaskFilter, "search">
    ): Promise<PaginatedTasks> {
        return api.post<PaginatedTasks>("/tasks/search", {
            query,
            ...filters,
        });
    }

    public async getOverdueTasks(projectId?: number): Promise<PaginatedTasks> {
        const config: AxiosRequestConfig = {
            params: projectId ? { project_id: projectId } : {},
        };

        return api.get<PaginatedTasks>("/tasks/overdue", config);
    }

    public async getUpcomingTasks(projectId?: number): Promise<PaginatedTasks> {
        const config: AxiosRequestConfig = {
            params: projectId ? { project_id: projectId } : {},
        };

        return api.get<PaginatedTasks>("/tasks/upcoming", config);
    }

    // Statistiques
    public async getTaskStatistics(
        projectId?: number
    ): Promise<TaskStatistics> {
        const config: AxiosRequestConfig = {
            params: projectId ? { project_id: projectId } : {},
        };

        return api.get<TaskStatistics>("/tasks/statistics", config);
    }

    public async getCountByStatus(
        projectId?: number
    ): Promise<Record<string, number>> {
        const config: AxiosRequestConfig = {
            params: projectId ? { project_id: projectId } : {},
        };

        return api.get<Record<string, number>>(
            "/tasks/count-by-status",
            config
        );
    }

    // Gestion des équipes et utilisateurs assignables
    public async getAssignableUsers(projectId: number): Promise<any[]> {
        return api.get<any[]>(`/tasks/project/${projectId}/assignable-users`);
    }

    public async getTeamMembers(projectId: number): Promise<any[]> {
        return api.get<any[]>(`/tasks/project/${projectId}/team-members`);
    }

    // Routes de santé
    public async checkHealth(): Promise<any> {
        return api.get<any>("/tasks/health");
    }

    public async test(): Promise<any> {
        return api.get<any>("/tasks/test");
    }
}

export const taskService = new TaskService();
