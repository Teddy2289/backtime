import { api } from "@/services/api";
import type {
    Task,
    PaginatedTasks,
    TaskFilter,
    CreateTaskData,
    UpdateTaskData,
} from "@/types/task";

class TaskService {
    private baseUrl = "/tasks";

    // Helper pour extraire les données de la réponse
    private extractData<T>(response: any): T {
        // Vérifier si c'est une structure ApiResponse
        if (response && typeof response === "object") {
            // Structure { success, data, message }
            if ("success" in response && "data" in response) {
                return response.data;
            }
            // Structure { data, meta } (pagination)
            if ("data" in response && response.data !== undefined) {
                return response.data;
            }
        }
        // Sinon, retourner la réponse directement
        return response;
    }

    async getTasks(
        params?: TaskFilter & { per_page?: number; page?: number },
    ): Promise<PaginatedTasks> {
        try {
            const response = await api.get(this.baseUrl, { params });

            // Extraire les données de la réponse
            const apiResponse = this.extractData<any>(response);

            let tasks: Task[] = [];
            let paginationData = {
                current_page: 1,
                last_page: 1,
                per_page: params?.per_page || 15,
                total: 0,
                from: 0,
                to: 0,
            };

            // Vérifier si c'est un tableau
            if (Array.isArray(apiResponse)) {
                tasks = apiResponse;
                paginationData.total = apiResponse.length;
                paginationData.to = apiResponse.length;
            }
            // Vérifier si c'est un objet avec data (structure paginée)
            else if (
                apiResponse &&
                typeof apiResponse === "object" &&
                "data" in apiResponse
            ) {
                tasks = Array.isArray(apiResponse.data) ? apiResponse.data : [];

                // Si meta existe, utiliser ses valeurs
                if (apiResponse.meta) {
                    paginationData = {
                        current_page: apiResponse.meta.current_page || 1,
                        last_page: apiResponse.meta.last_page || 1,
                        per_page: apiResponse.meta.per_page || 15,
                        total: apiResponse.meta.total || tasks.length,
                        from: apiResponse.meta.from || 0,
                        to: apiResponse.meta.to || tasks.length,
                    };
                } else {
                    // Sinon, calculer à partir des données
                    paginationData.total = tasks.length;
                    paginationData.to = tasks.length;
                }
            }
            // Autres cas (erreur ou structure inattendue)
            else {
                tasks = [];
            }

            return {
                data: tasks,
                ...paginationData,
            };
        } catch (error) {
            console.error("Error fetching tasks:", error);
            throw error;
        }
    }

    async getTeamMembers(projectId: number): Promise<any[]> {
        try {
            const response = await api.get(
                `/projectsTeams/${projectId}/team-users`,
            );

            // Utiliser extractData pour gérer la structure de réponse
            const extractedData = this.extractData<any>(response);

            // Vérifier si c'est un tableau
            if (Array.isArray(extractedData)) {
                return extractedData;
            }

            return [];
        } catch (error) {
            console.error(
                `Error fetching team members for project ${projectId}:`,
                error,
            );
            throw error;
        }
    }

    async getTasksByProject(
        projectId: number,
        params?: any,
    ): Promise<PaginatedTasks> {
        try {
            const response = await api.get(this.baseUrl, {
                params: { ...params, project_id: projectId },
            });

            // Extraire les données et traiter comme getTasks
            const apiResponse = this.extractData<any>(response);

            let tasks: Task[] = [];
            let paginationData = {
                current_page: 1,
                last_page: 1,
                per_page: params?.per_page || 15,
                total: 0,
                from: 0,
                to: 0,
            };

            if (Array.isArray(apiResponse)) {
                tasks = apiResponse;
                paginationData.total = apiResponse.length;
                paginationData.to = apiResponse.length;
            } else if (
                apiResponse &&
                typeof apiResponse === "object" &&
                "data" in apiResponse
            ) {
                tasks = Array.isArray(apiResponse.data) ? apiResponse.data : [];

                if (apiResponse.meta) {
                    paginationData = {
                        current_page: apiResponse.meta.current_page || 1,
                        last_page: apiResponse.meta.last_page || 1,
                        per_page: apiResponse.meta.per_page || 15,
                        total: apiResponse.meta.total || tasks.length,
                        from: apiResponse.meta.from || 0,
                        to: apiResponse.meta.to || tasks.length,
                    };
                } else {
                    paginationData.total = tasks.length;
                    paginationData.to = tasks.length;
                }
            }

            return {
                data: tasks,
                ...paginationData,
            };
        } catch (error) {
            console.error(
                `Error fetching tasks for project ${projectId}:`,
                error,
            );
            throw error;
        }
    }

    async getTaskById(id: number): Promise<Task> {
        try {
            const response = await api.get(`${this.baseUrl}/${id}`);
            const taskData = this.extractData<Task>(response);

            if (!taskData) {
                throw new Error("Task not found in API response");
            }

            return taskData;
        } catch (error) {
            console.error(`Error fetching task ${id}:`, error);
            throw error;
        }
    }

    async createTask(data: CreateTaskData): Promise<Task> {
        const response = await api.post(this.baseUrl, data);
        return this.extractData<Task>(response);
    }

    async updateTask(id: number, data: UpdateTaskData): Promise<Task> {
        const response = await api.put(`${this.baseUrl}/${id}`, data);
        return this.extractData<Task>(response);
    }

    async deleteTask(id: number): Promise<void> {
        await api.delete(`${this.baseUrl}/${id}`);
    }

    async restoreTask(id: number): Promise<Task> {
        const response = await api.post(`${this.baseUrl}/${id}/restore`);
        return this.extractData<Task>(response);
    }

    async updateTaskStatus(id: number, status: string): Promise<Task> {
        const response = await api.post(`${this.baseUrl}/${id}/status`, {
            status,
        });
        return this.extractData<Task>(response);
    }

    async assignTask(id: number, userId: number): Promise<Task> {
        const response = await api.post(`${this.baseUrl}/${id}/assign`, {
            user_id: userId,
        });
        return this.extractData<Task>(response);
    }
}

export const taskService = new TaskService();
