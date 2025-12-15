// src/stores/task.store.ts
import { defineStore } from "pinia";
import { taskService } from "../services/task.service";
import type {
    Task,
    PaginatedTasks,
    TaskFilter,
    TaskStatistics,
    CreateTaskData,
    UpdateTaskData,
} from "@/types/task";

export const useTaskStore = defineStore("task", {
    state: () => ({
        tasks: [] as Task[],
        currentTask: null as Task | null,
        pagination: null as {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        } | null,
        filters: {} as TaskFilter,
        statistics: null as TaskStatistics | null,
        loading: false,
        error: null as string | null,
    }),

    getters: {
        getTasks: (state) => state.tasks,
        getTaskById: (state) => (id: number) =>
            state.tasks.find((task) => task.id === id),
        getCurrentTask: (state) => state.currentTask,
        getPagination: (state) => state.pagination,
        getFilters: (state) => state.filters,
        getStatistics: (state) => state.statistics,
        isLoading: (state) => state.loading,
        getError: (state) => state.error,

        // Getters utilitaires
        getTasksByProject: (state) => (projectId: number) =>
            state.tasks.filter((task) => task.project_id === projectId),

        getTasksByStatus: (state) => (status: string) =>
            state.tasks.filter((task) => task.status === status),

        getOverdueTasks: (state) =>
            state.tasks.filter((task) => task.is_overdue),

        getAssignedTasks: (state) => (userId: number) =>
            state.tasks.filter((task) => task.assigned_to === userId),
    },

    actions: {
        async fetchTasks(
            params?: TaskFilter & { per_page?: number; page?: number }
        ) {
            try {
                this.loading = true;
                this.error = null;

                if (params) {
                    this.filters = { ...this.filters, ...params };
                }

                const response = await taskService.getTasks(params);

                this.tasks = response.data;
                this.pagination = {
                    current_page: response.current_page,
                    last_page: response.last_page,
                    per_page: response.per_page,
                    total: response.total,
                };

                return response;
            } catch (error: any) {
                this.error = error.message || "Failed to fetch tasks";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchTask(id: number) {
            try {
                this.loading = true;
                this.error = null;

                const task = await taskService.getTaskById(id);
                this.currentTask = task;

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to fetch task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createTask(data: CreateTaskData) {
            try {
                this.loading = true;
                this.error = null;

                const task = await taskService.createTask(data);
                this.tasks.unshift(task);

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to create task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateTask(id: number, data: UpdateTaskData) {
            try {
                this.loading = true;
                this.error = null;

                const task = await taskService.updateTask(id, data);

                // Mettre à jour dans la liste
                const index = this.tasks.findIndex((t) => t.id === id);
                if (index !== -1) {
                    this.tasks.splice(index, 1, task);
                }

                // Mettre à jour la tâche courante si c'est elle
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

        async deleteTask(id: number) {
            try {
                this.loading = true;
                this.error = null;

                await taskService.deleteTask(id);

                // Retirer de la liste
                const index = this.tasks.findIndex((task) => task.id === id);
                if (index !== -1) {
                    this.tasks.splice(index, 1);
                }

                // Effacer la tâche courante si c'est elle
                if (this.currentTask?.id === id) {
                    this.currentTask = null;
                }

                return true;
            } catch (error: any) {
                this.error = error.message || "Failed to delete task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchTasksByProject(projectId: number, params?: any) {
            try {
                this.loading = true;
                this.error = null;

                const response = await taskService.getTasksByProject(
                    projectId,
                    params
                );

                this.tasks = response.data;
                this.pagination = {
                    current_page: response.current_page,
                    last_page: response.last_page,
                    per_page: response.per_page,
                    total: response.total,
                };

                return response;
            } catch (error: any) {
                this.error = error.message || "Failed to fetch project tasks";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchStatistics(projectId?: number) {
            try {
                this.loading = true;
                this.error = null;

                const statistics = await taskService.getTaskStatistics(
                    projectId
                );
                this.statistics = statistics;

                return statistics;
            } catch (error: any) {
                this.error = error.message || "Failed to fetch statistics";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async assignTask(id: number, userId: number) {
            try {
                this.loading = true;
                this.error = null;

                const task = await taskService.assignTask(id, userId);

                // Mettre à jour dans la liste
                const index = this.tasks.findIndex((t) => t.id === id);
                if (index !== -1) {
                    this.tasks.splice(index, 1, task);
                }

                if (this.currentTask?.id === id) {
                    this.currentTask = task;
                }

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to assign task";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateStatus(id: number, status: string) {
            try {
                this.loading = true;
                this.error = null;

                const task = await taskService.updateTaskStatus(id, status);

                // Mettre à jour dans la liste
                const index = this.tasks.findIndex((t) => t.id === id);
                if (index !== -1) {
                    this.tasks.splice(index, 1, task);
                }

                if (this.currentTask?.id === id) {
                    this.currentTask = task;
                }

                return task;
            } catch (error: any) {
                this.error = error.message || "Failed to update status";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        clearCurrentTask() {
            this.currentTask = null;
        },

        clearTasks() {
            this.tasks = [];
            this.pagination = null;
        },

        resetFilters() {
            this.filters = {};
        },

        setFilter(key: keyof TaskFilter, value: any) {
            this.filters[key] = value;
        },

        removeFilter(key: keyof TaskFilter) {
            delete this.filters[key];
        },
    },
});
