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

    async getTasks(
        params?: TaskFilter & { per_page?: number; page?: number }
    ): Promise<PaginatedTasks> {
        try {
            const response = await api.get(this.baseUrl, { params });

            const apiResponse = response.data;

            // Cas 1 : L'API retourne un objet structuré (avec data et meta) - VOTRE CAS ATTENDU
            // Cas 2 : L'API retourne directement un tableau - VOTRE CAS RÉEL

            let tasks: Task[] = [];
            let paginationData = {
                current_page: 1,
                last_page: 1,
                per_page: params?.per_page || 15,
                total: 0,
                from: 0,
                to: 0,
            };

            // Vérifier si c'est un tableau (cas 2)
            if (Array.isArray(apiResponse)) {
                tasks = apiResponse;
                paginationData.total = apiResponse.length;
                paginationData.to = apiResponse.length;
            }
            // Vérifier si c'est un objet avec data (cas 1 - structure Laravel paginée)
            else if (
                apiResponse &&
                typeof apiResponse === "object" &&
                apiResponse.data
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
                        to: apiResponse.meta.to || 0,
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

    async getTaskById(id: number): Promise<Task> {
        try {
            const response = await api.get(`${this.baseUrl}/${id}`);
            const apiResponse = response.data;
            let taskData: any;

            if (apiResponse && typeof apiResponse === "object") {
                if (
                    "data" in apiResponse &&
                    apiResponse.data !== null &&
                    apiResponse.data !== undefined
                ) {
                    taskData = apiResponse.data;
                } else {
                    taskData = apiResponse;
                }
            }

            if (!taskData) {
                throw new Error("Task not found in API response");
            }

            return taskData as Task;
        } catch (error) {
            console.error(`Error fetching task ${id}:`, error);
            throw error;
        }
    }

    async createTask(data: CreateTaskData): Promise<Task> {
        const response = await api.post(this.baseUrl, data);
        return response.data.data as Task;
    }

    async updateTask(id: number, data: UpdateTaskData): Promise<Task> {
        const response = await api.put(`${this.baseUrl}/${id}`, data);
        return response.data.data as Task;
    }

    public async deleteTask(id: number): Promise<void> {
        await api.delete(`${this.baseUrl}/${id}`);
    }

    public async restoreTask(id: number): Promise<Task> {
        const response = await api.post<Task>(`/tasks/${id}/restore`);
        return response.data || (response.data.data as Task);
    }

    async updateTaskStatus(id: number, status: string): Promise<Task> {
        const response = await api.post(`${this.baseUrl}/${id}/status`, {
            status,
        });
        return response.data.data as Task;
    }

    async assignTask(id: number, userId: number): Promise<Task> {
        const response = await api.post(`${this.baseUrl}/${id}/assign`, {
            user_id: userId,
        });
        return response.data.data as Task;
    }
}

export const taskService = new TaskService();
