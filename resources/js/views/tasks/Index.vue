<template>
    <div class="tasks-container">
        <!-- Header -->
        <div class="header-container">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-primary/5 p-6 mb-8">  <div class="header-content">
                <div class="header-left">
                    <h1 class="page-title">Tâches</h1>
                    <p class="page-subtitle" v-if="pagination">
                        {{ pagination.total }} tâches au total
                    </p>
                </div>
                <button @click="showCreateModal = true" class="create-button">
                    <svg class="button-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvelle tâche
                </button>
            </div></div>
            
        </div>

        <!-- Filters -->
        <div class="filters-container">
            <div class="search-wrapper">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="searchQuery" @input="handleSearch" placeholder="Rechercher une tâche..."
                    class="pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white" />
            </div>

            <div class="filter-group">
                <select v-model="statusFilter" @change="handleFilterChange" class="appearance-none pl-4 pr-10 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white min-w-[140px]">
                    <option value="">Tous les statuts</option>
                    <option value="backlog">Backlog</option>
                    <option value="todo">À faire</option>
                    <option value="doing">En cours</option>
                    <option value="done">Terminé</option>
                </select>
            </div>

            <div class="filter-group">
                <select v-model="priorityFilter" @change="handleFilterChange" class="appearance-none pl-4 pr-10 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white min-w-[140px]">
                    <option value="">Toutes les priorités</option>
                    <option value="low">Basse</option>
                    <option value="medium">Moyenne</option>
                    <option value="high">Haute</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p class="loading-text">Chargement des tâches...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
            <svg class="error-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <div class="error-content">
                <h3 class="error-title">Erreur de chargement</h3>
                <p class="error-message">{{ error }}</p>
                <button @click="fetchTasksWithFilters(1)" class="retry-button">
                    Réessayer
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && tasks.length === 0" class="empty-state">
            <svg class="empty-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="empty-title">Aucune tâche trouvée</h3>
            <p class="empty-message">
                {{ searchQuery || statusFilter || priorityFilter ?
                    'Essayez de modifier vos filtres de recherche' :
                    'Commencez par créer votre première tâche' }}
            </p>
            <button @click="showCreateModal = true" class="empty-action-button">
                Créer une tâche
            </button>
        </div>

        <!-- Tasks Table -->
        <div v-else class="tasks-table-container">
            <div class="table-responsive">
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th class="table-header">
                                <div class="header-content">
                                    <span>Tâche</span>
                                </div>
                            </th>
                            <th class="table-header">
                                <div class="header-content">
                                    <span>Statut</span>
                                </div>
                            </th>
                            <th class="table-header">
                                <div class="header-content">
                                    <span>Priorité</span>
                                </div>
                            </th>
                            <th class="table-header">
                                <div class="header-content">
                                    <span>Échéance</span>
                                </div>
                            </th>
                            <th class="table-header">
                                <div class="header-content">
                                    <span>Assigné à</span>
                                </div>
                            </th>
                            <th class="table-header actions-header">
                                <div class="header-content">
                                    <span>Actions</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="task in tasks" :key="task.id" class="table-row" :class="{
                            'row-overdue': task.is_overdue,
                            'row-done': task.status === 'done',
                            'row-priority-high': task.priority === 'high'
                        }" @click="openTask(task)">
                            <!-- Task Info -->
                            <td class="table-cell task-info-cell">
                                <div class="task-info">
                                    <div class="task-title-wrapper">
                                        <h3 class="task-title-table">{{ task.title }}</h3>
                                        <span class="task-id-table">#{{ task.id }}</span>
                                    </div>
                                    <p v-if="task.description" class="task-description-table">
                                        {{ truncateDescription(task.description, 60) }}
                                    </p>
                                    <p v-else class="task-description-placeholder-table">
                                        Aucune description
                                    </p>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="table-cell status-cell">
                                <div class="status-wrapper">
                                    <span class="status-badge-table" :class="`status-${task.status}`">
                                        {{ formatStatus(task.status) }}
                                    </span>
                                    <div v-if="task.is_overdue" class="overdue-indicator">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor">
                                            <circle cx="12" cy="12" r="10" />
                                            <polyline points="12 6 12 12 16 14" />
                                        </svg>
                                        <span>En retard</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Priority -->
                            <td class="table-cell priority-cell">
                                <div class="priority-indicator" :class="`priority-${task.priority}`">
                                    <div class="priority-dot"></div>
                                    <span class="priority-text">{{ formatPriority(task.priority) }}</span>
                                </div>
                            </td>

                            <!-- Due Date -->
                            <td class="table-cell date-cell">
                                <div class="date-wrapper">
                                    <div class="due-date">
                                        <svg class="date-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor">
                                            <circle cx="12" cy="12" r="10" />
                                            <polyline points="12 6 12 12 16 14" />
                                        </svg>
                                        <span :class="{ 'overdue-date': task.is_overdue }">
                                            {{ task.due_date ? formatDate(task.due_date) : 'Non définie' }}
                                        </span>
                                    </div>
                                    <div v-if="task.estimated_time" class="estimated-time">
                                        {{ task.estimated_time }}h estimées
                                    </div>
                                </div>
                            </td>

                            <!-- Assigned User -->
                            <td class="table-cell assigned-cell">
                                <div v-if="task.assigned_user" class="assigned-user">
                                    <div class="avatar-table">
                                        {{ getInitials(task.assigned_user.name) }}
                                    </div>
                                    <span class="assigned-name-table">{{ task.assigned_user.name }}</span>
                                </div>
                                <div v-else class="unassigned-table">
                                    Non assigné
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="table-cell actions-cell">
                                <div class="actions-wrapper" @click.stop>
                                    <button v-if="task.status !== 'done'" @click.stop="completeTask(task.id)"
                                        class="action-button-table complete-button-table" title="Marquer comme terminé">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button @click.stop="deleteTask(task.id)"
                                        class="action-button-table delete-button-table" title="Supprimer">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="pagination-container">
            <button :disabled="pagination.current_page === 1" @click="goToPage(pagination.current_page - 1)"
                class="pagination-button prev-button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Précédent
            </button>

            <div class="pagination-info">
                Page <span class="current-page">{{ pagination.current_page }}</span>
                sur {{ pagination.last_page }}
            </div>

            <button :disabled="pagination.current_page === pagination.last_page"
                @click="goToPage(pagination.current_page + 1)" class="pagination-button next-button">
                Suivant
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Create Modal -->
        <TaskCreateModal v-if="showCreateModal" @close="showCreateModal = false" @created="handleTaskCreated" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useTaskStore } from '@/stores/task.store';
import TaskCreateModal from '@/components/tasks/TaskCreateModal.vue';
import router from '@/router';
import type { Task } from "@/types/task";

const taskStore = useTaskStore();
const searchQuery = ref('');
const statusFilter = ref('');
const priorityFilter = ref('');
const showCreateModal = ref(false);

const tasks = computed(() => taskStore.getTasks);
const loading = computed(() => taskStore.getLoading);
const error = computed(() => taskStore.getError);
const pagination = computed(() => taskStore.getPagination);

onMounted(async () => {
    await taskStore.fetchTasks();
});

const fetchTasksWithFilters = (page?: number) => {
    taskStore.fetchTasks({
        page: page || taskStore.getPagination?.current_page,
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value as "low" | "medium" | "high" | undefined,
    });
}

const handleSearch = () => {
    fetchTasksWithFilters(1);
};

const handleFilterChange = () => {
    fetchTasksWithFilters(1);
};

const openTask = (task: Task) => {
    router.push({ name: 'task-detail', params: { id: task.id } });
};

const completeTask = async (id: number) => {
    try {
        await taskStore.updateStatus(id, 'done');
    } catch (err: any) {
        console.error('Failed to complete task:', err);
    }
};

const deleteTask = async (id: number) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        try {
            await taskStore.deleteTask(id);
        } catch (err: any) {
            console.error('Failed to delete task:', err);
        }
    }
};

const handleTaskCreated = async () => {
    showCreateModal.value = false;
    await taskStore.fetchTasks();
};

const goToPage = async (page: number) => {
    fetchTasksWithFilters(page);
};

const truncateDescription = (description?: string, maxLength: number = 100) => {
    if (!description) return '';
    return description.length > maxLength
        ? description.substring(0, maxLength) + '...'
        : description;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatStatus = (status: string) => {
    const statusMap: Record<string, string> = {
        'backlog': 'Backlog',
        'todo': 'À faire',
        'doing': 'En cours',
        'done': 'Terminé'
    };
    return statusMap[status] || status;
};

const formatPriority = (priority: string) => {
    const priorityMap: Record<string, string> = {
        'low': 'Basse',
        'medium': 'Moyenne',
        'high': 'Haute'
    };
    return priorityMap[priority] || priority;
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};
</script>

<style src="@/components/styles/tasks.css" scoped></style>