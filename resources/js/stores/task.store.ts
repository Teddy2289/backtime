import { defineStore } from "pinia";
import { taskService } from "@/services/task.service";
import type {
    Task,
    TaskFilter,
    CreateTaskData,
    UpdateTaskData,
} from "@/types/task";

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface TaskState {
    tasks: Task[];
    currentTask: Task | null;
    pagination: Pagination | null;
    filters: TaskFilter & { page?: number; per_page?: number };
    loading: boolean;
    error: string | null;
}

export const useTaskStore = defineStore("task", {
    state: (): TaskState => ({
        tasks: [],
        currentTask: null,
        pagination: null,
        filters: {},
        loading: false,
        error: null,
    }),

    getters: {
        getTasks: (state) => state.tasks,
        getCurrentTask: (state) => state.currentTask,
        getPagination: (state) => state.pagination,
        getLoading: (state) => state.loading,
        getError: (state) => state.error,

        getTaskById: (state) => (id: number) => {
            return state.tasks.find((task) => task.id === id);
        },
    },

    actions: {
        async fetchTasks(
            params?: TaskFilter & { per_page?: number; page?: number }
        ) {
            try {
                this.loading = true;
                this.error = null;

                // Fusionner les filtres
                const newFilters = { ...this.filters, ...params };
                this.filters = newFilters;

                // Appeler le service
                const response = await taskService.getTasks(newFilters);

                this.tasks = response.data;

                // Extraction des données de pagination
                this.pagination = {
                    current_page: response.current_page,
                    last_page: response.last_page,
                    per_page: response.per_page,
                    total: response.total,
                    from: response.from,
                    to: response.to,
                };

                return response;
            } catch (error: any) {
                this.error = error.message || "Failed to fetch tasks";
                console.error("Store fetchTasks error:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async fetchTask(id: number): Promise<Task> {
            try {
                this.loading = true;
                this.error = null;

                const task = await taskService.getTaskById(id);
                this.currentTask = task;

                // Mettre à jour aussi dans la liste si présente
                const index = this.tasks.findIndex((t) => t.id === id);
                if (index !== -1) {
                    this.tasks[index] = task;
                }

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to fetch task";
                console.error(`Store fetchTask(${id}) error:`, error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createTask(data: CreateTaskData): Promise<Task> {
            this.loading = true;
            try {
                const task = await taskService.createTask(data);
                // Ajouter en haut de la liste. Re-fetcher si l'ordre est important (pas fait ici).
                this.tasks.unshift(task);

                // 🌟 Amélioration: Mettre à jour le total de la pagination
                if (this.pagination) {
                    this.pagination.total++;
                }

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to create task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateTask(id: number, data: UpdateTaskData): Promise<Task> {
            this.loading = true;
            try {
                const task = await taskService.updateTask(id, data);

                // Mettre à jour dans la liste
                const index = this.tasks.findIndex((t) => t.id === id);
                if (index !== -1) {
                    Object.assign(this.tasks[index], task);
                }

                // Mettre à jour la tâche courante
                if (this.currentTask?.id === id) {
                    this.currentTask = task;
                }

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to update task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteTask(id: number): Promise<void> {
            this.loading = true;
            try {
                await taskService.deleteTask(id);

                // Retirer de la liste
                this.tasks = this.tasks.filter((t) => t.id !== id);

                // Réinitialiser la tâche courante si c'est elle
                if (this.currentTask?.id === id) {
                    this.currentTask = null;
                }

                // Mettre à jour la pagination
                if (this.pagination) {
                    this.pagination.total--;

                    if (
                        this.tasks.length === 0 &&
                        this.pagination.current_page > 1
                    ) {
                        this.fetchTasks({
                            page: this.pagination.current_page - 1,
                        });
                    }
                }
            } catch (error: any) {
                this.error = error.message || "Failed to delete task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Autres actions simplifiées...
        async updateStatus(id: number, status: string): Promise<Task> {
            // Utiliser le service direct pour l'API, puis updateTask pour la mise à jour du store
            const updatedTask = await taskService.updateTaskStatus(id, status);
            return this.updateTask(id, updatedTask); // Appel à updateTask pour la mise à jour locale
        },

        async assignTask(id: number, userId: number): Promise<Task> {
            // Utiliser le service direct pour l'API, puis updateTask pour la mise à jour du store
            const assignedTask = await taskService.assignTask(id, userId);
            return this.updateTask(id, assignedTask); // Appel à updateTask pour la mise à jour locale
        },
    },
});
