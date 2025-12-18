<template>
    <div class="tasks-container">
        <!-- Header -->
        <div class="header-container">
            <div class="header-content">
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
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-container">
            <div class="search-wrapper">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="searchQuery" @input="handleSearch" placeholder="Rechercher une tâche..."
                    class="search-input" />
            </div>

            <div class="filter-group">
                <select v-model="statusFilter" @change="handleFilterChange" class="filter-select">
                    <option value="">Tous les statuts</option>
                    <option value="backlog">Backlog</option>
                    <option value="todo">À faire</option>
                    <option value="doing">En cours</option>
                    <option value="done">Terminé</option>
                </select>
            </div>

            <div class="filter-group">
                <select v-model="priorityFilter" @change="handleFilterChange" class="filter-select">
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

        <!-- Tasks Grid -->
        <div v-else class="tasks-grid">
            <div v-for="task in tasks" :key="task.id" class="task-card" :class="{
                'task-overdue': task.is_overdue,
                [`task-priority-${task.priority}`]: true,
                'task-done': task.status === 'done'
            }" @click="openTask(task)">
                <!-- Task Header -->
                <div class="task-header">
                    <div class="task-title-section">
                        <h3 class="task-title">{{ task.title }}</h3>
                        <div class="task-id">#{{ task.id }}</div>
                    </div>
                    <div class="task-status-badge" :class="`status-${task.status}`">
                        {{ formatStatus(task.status) }}
                    </div>
                </div>

                <!-- Task Description -->
                <p v-if="task.description" class="task-description">
                    {{ truncateDescription(task.description) }}
                </p>
                <p v-else class="task-description-placeholder">
                    Aucune description
                </p>

                <!-- Task Meta -->
                <div class="task-meta">
                    <div class="meta-item">
                        <svg class="meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        <span v-if="task.due_date" class="meta-text" :class="{ 'overdue-text': task.is_overdue }">
                            {{ formatDate(task.due_date) }}
                        </span>
                        <span v-else class="meta-text">Aucune date</span>
                    </div>

                    <div class="meta-item">
                        <svg class="meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="meta-text">{{ task.estimated_time || 0 }}h</span>
                    </div>

                    <div class="priority-badge" :class="`priority-${task.priority}`">
                        {{ formatPriority(task.priority) }}
                    </div>
                </div>

                <!-- Task Footer -->
                <div class="task-footer">
                    <div class="assigned-to" v-if="task.assigned_user">
                        <div class="avatar">
                            {{ getInitials(task.assigned_user.name) }}
                        </div>
                        <span class="assigned-name">{{ task.assigned_user.name }}</span>
                    </div>
                    <div v-else class="unassigned">
                        Non assigné
                    </div>

                    <div class="task-actions">
                        <button v-if="task.status !== 'done'" @click.stop="completeTask(task.id)"
                            class="action-button complete-button" title="Marquer comme terminé">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button @click.stop="deleteTask(task.id)" class="action-button delete-button" title="Supprimer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
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

const truncateDescription = (description?: string) => {
    if (!description) return '';
    return description.length > 120
        ? description.substring(0, 120) + '...'
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

<style scoped>
.tasks-container {
    padding: 24px;
    max-width: 1440px;
    margin: 0 auto;
}

/* Header */
.header-container {
    margin-bottom: 32px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
}

.header-left {
    flex: 1;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 4px 0;
}

.page-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.create-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background-color: #31b6b8;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.create-button:hover {
    background-color: #289396;
    transform: translateY(-1px);
}

.create-button:active {
    transform: translateY(0);
}

.button-icon {
    flex-shrink: 0;
}

/* Filters */
.filters-container {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.search-wrapper {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 12px 12px 12px 42px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1a1a;
    background-color: white;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #31b6b8;
    box-shadow: 0 0 0 3px rgba(49, 182, 184, 0.1);
}

.search-input::placeholder {
    color: #999;
}

.filter-group {
    flex: 0 1 auto;
}

.filter-select {
    padding: 12px 40px 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1a1a;
    background-color: white;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 160px;
    appearance: none;
}

.filter-select:focus {
    outline: none;
    border-color: #31b6b8;
    box-shadow: 0 0 0 3px rgba(49, 182, 184, 0.1);
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f0f0f0;
    border-top-color: #31b6b8;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

.loading-text {
    font-size: 14px;
    color: #666;
    margin: 0;
}

/* Error State */
.error-state {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    margin-bottom: 24px;
}

.error-icon {
    flex-shrink: 0;
    color: #dc2626;
}

.error-content {
    flex: 1;
}

.error-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 4px 0;
}

.error-message {
    font-size: 14px;
    color: #666;
    margin: 0 0 12px 0;
}

.retry-button {
    padding: 8px 16px;
    background-color: #dc2626;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.retry-button:hover {
    background-color: #b91c1c;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    text-align: center;
}

.empty-icon {
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 8px 0;
}

.empty-message {
    font-size: 14px;
    color: #666;
    margin: 0 0 20px 0;
    max-width: 400px;
}

.empty-action-button {
    padding: 10px 20px;
    background-color: #31b6b8;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.empty-action-button:hover {
    background-color: #289396;
}

/* Tasks Grid */
.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.task-card {
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.task-card:hover {
    border-color: #31b6b8;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.task-card.task-done {
    opacity: 0.8;
}

.task-card.task-done:hover {
    opacity: 1;
}

.task-card.task-overdue {
    border-left: 4px solid #dc2626;
}

.task-card.task-priority-low {
    border-left: 4px solid #10b981;
}

.task-card.task-priority-medium {
    border-left: 4px solid #f59e0b;
}

.task-card.task-priority-high {
    border-left: 4px solid #dc2626;
}

/* Task Header */
.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.task-title-section {
    flex: 1;
    min-width: 0;
}

.task-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 4px 0;
    line-height: 1.4;
    word-break: break-word;
}

.task-id {
    font-size: 12px;
    color: #999;
    font-family: 'Monaco', 'Consolas', monospace;
}

.task-status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    flex-shrink: 0;
}

.status-backlog {
    background-color: #f3f4f6;
    color: #6b7280;
}

.status-todo {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-doing {
    background-color: #fef3c7;
    color: #92400e;
}

.status-done {
    background-color: #d1fae5;
    color: #065f46;
}

/* Task Description */
.task-description {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
    margin: 0 0 16px 0;
    word-break: break-word;
}

.task-description-placeholder {
    font-size: 14px;
    color: #999;
    font-style: italic;
    margin: 0 0 16px 0;
}

/* Task Meta */
.task-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.meta-icon {
    flex-shrink: 0;
    color: #999;
}

.meta-text {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
}

.meta-text.overdue-text {
    color: #dc2626;
    font-weight: 500;
}

.priority-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}

.priority-low {
    background-color: #d1fae5;
    color: #065f46;
}

.priority-medium {
    background-color: #fef3c7;
    color: #92400e;
}

.priority-high {
    background-color: #fef2f2;
    color: #991b1b;
}

/* Task Footer */
.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.assigned-to {
    display: flex;
    align-items: center;
    gap: 8px;
}

.avatar {
    width: 28px;
    height: 28px;
    background-color: #31b6b8;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
}

.assigned-name {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

.unassigned {
    font-size: 13px;
    color: #999;
    font-style: italic;
}

.task-actions {
    display: flex;
    gap: 8px;
}

.action-button {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: white;
    color: #666;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}

.action-button:hover {
    border-color: #d1d5db;
    background-color: #f9fafb;
}

.complete-button:hover {
    color: #10b981;
    border-color: #a7f3d0;
    background-color: #ecfdf5;
}

.delete-button:hover {
    color: #dc2626;
    border-color: #fecaca;
    background-color: #fef2f2;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 24px;
    padding: 24px 0;
    border-top: 1px solid #e5e7eb;
}

.pagination-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: white;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-button:not(:disabled):hover {
    border-color: #31b6b8;
    color: #31b6b8;
    background-color: #f0fdf4;
}

.prev-button {
    justify-content: flex-start;
}

.next-button {
    justify-content: flex-end;
}

.pagination-info {
    font-size: 14px;
    color: #666;
    text-align: center;
}

.current-page {
    font-weight: 600;
    color: #1a1a1a;
}

/* Animations */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .tasks-container {
        padding: 16px;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
    }

    .create-button {
        width: 100%;
        justify-content: center;
    }

    .search-wrapper {
        min-width: 100%;
    }

    .filter-select {
        min-width: 100%;
    }

    .filters-container {
        flex-direction: column;
        gap: 12px;
    }

    .tasks-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .pagination-container {
        flex-direction: column;
        gap: 16px;
    }

    .pagination-button {
        width: 100%;
        justify-content: center;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .tasks-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}
</style>