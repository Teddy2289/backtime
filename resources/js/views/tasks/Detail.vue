<template>
    <div class="task-detail">
        <!-- État de chargement -->
        <div v-if="loading" class="loading-container">
            <div class="loading-spinner"></div>
            <p class="loading-text text-xs">Chargement...</p>
        </div>

        <!-- État d'erreur -->
        <div v-else-if="error" class="error-container">
            <div class="error-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ab2283" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
            </div>
            <h2 class="error-title text-sm">Erreur</h2>
            <p class="error-message text-xs">{{ error }}</p>
            <router-link :to="{ name: 'tasks' }" class="btn btn-primary">
                ← Retour
            </router-link>
        </div>

        <!-- Tâche non trouvée -->
        <div v-else-if="!task" class="not-found-container">
            <div class="not-found-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#474665" stroke-width="1.5">
                    <path
                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </div>
            <h2 class="not-found-title text-sm">Introuvable</h2>
            <p class="not-found-message text-xs">Cette tâche n'existe pas.</p>
            <router-link :to="{ name: 'tasks' }" class="btn btn-primary">
                ← Retour
            </router-link>
        </div>

        <!-- Contenu de la tâche -->
        <div v-else class="task-content">
            <!-- En-tête -->
            <div class="task-header">
                <div class="header-top">
                    <router-link :to="{ name: 'tasks' }" class="back-btn text-xs">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="15 18 9 12 15 6" />
                        </svg>
                        Retour
                    </router-link>
                    <div class="header-actions">
                        <span class="task-id text-xs">#{{ task.id }}</span>
                        <!-- Bouton Modifier -->
                        <button @click="showEditModal = true" class="edit-btn text-xs">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                            Modifier
                        </button>
                    </div>
                </div>

                <h1 class="task-title text-lg">{{ task.title }}</h1>

                <!-- Métadonnées -->
                <div class="task-meta">
                    <span class="badge text-xs" :class="`badge-${task.status}`">
                        {{ formatStatus(task.status) }}
                    </span>
                    <span class="badge text-xs" :class="`badge-priority-${task.priority}`">
                        {{ formatPriority(task.priority) }}
                    </span>
                    <span v-if="task.is_overdue" class="badge badge-overdue text-xs">
                        En retard
                    </span>
                    <span v-if="task.progress === 100" class="badge badge-completed text-xs">
                        Terminée
                    </span>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="task-main">
                <!-- Description -->
                <div class="section">
                    <h3 class="section-title text-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <polyline points="10 9 9 9 8 9" />
                        </svg>
                        Description
                    </h3>
                    <div class="section-content">
                        <p v-if="task.description" class="description-text text-xs">{{ task.description }}</p>
                        <p v-else class="text-muted text-xs">Aucune description</p>
                    </div>
                </div>

                <!-- Détails -->
                <div class="section">
                    <h3 class="section-title text-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                        Détails
                    </h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label text-xs">Projet</span>
                            <span class="detail-value text-xs">{{ task.project?.name || '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Assigné à</span>
                            <span class="detail-value text-xs">
                                {{ task.assigned_user?.name || 'Non assigné' }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Échéance</span>
                            <span class="detail-value text-xs" :class="{ 'overdue': task.is_overdue }">
                                {{ task.due_date ? formatDate(task.due_date) : '-' }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Progression</span>
                            <div class="progress-container">
                                <div class="progress-bar" :style="{ width: `${task.progress}%` }"></div>
                                <span class="progress-text text-xs">{{ task.progress }}%</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Temps estimé</span>
                            <span class="detail-value text-xs">
                                {{ task.estimated_time || 0 }}h
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Créée le</span>
                            <span class="detail-value text-xs">
                                {{ task.created_at ? formatDate(task.created_at) : '-' }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Démarrée le</span>
                            <span class="detail-value text-xs">
                                {{ task.start_date ? formatDate(task.start_date) : '-' }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label text-xs">Mise à jour</span>
                            <span class="detail-value text-xs">
                                {{ task.updated_at ? formatDate(task.updated_at) : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Étiquettes -->
                <div class="section">
                    <h3 class="section-title text-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                            <line x1="7" y1="7" x2="7.01" y2="7" />
                        </svg>
                        Étiquettes
                    </h3>
                    <div class="section-content">
                        <div v-if="task.tags && task.tags.length > 0" class="tags-container">
                            <span v-for="(tag, index) in task.tags" :key="index" class="tag text-xs">
                                {{ tag }}
                            </span>
                        </div>
                        <p v-else class="text-muted text-xs">Aucune étiquette</p>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="section">
                    <h3 class="section-title text-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                        </svg>
                        Statistiques
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-value text-sm">{{ task.files?.length || 0 }}</span>
                            <span class="stat-label text-xs">Fichiers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value text-sm">{{ task.comments?.length || 0 }}</span>
                            <span class="stat-label text-xs">Commentaires</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value text-sm">{{ task.time_logs?.length || 0 }}</span>
                            <span class="stat-label text-xs">Journaux</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value text-sm">{{ task.total_worked_time || 0 }}h</span>
                            <span class="stat-label text-xs">Temps travaillé</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="section actions-section">
                    <h3 class="section-title text-sm">Actions rapides</h3>
                    <div class="action-buttons">
                        <button @click="completeTask" class="btn btn-primary text-xs"
                            :class="{ 'btn-disabled': task.status === 'done' }" :disabled="task.status === 'done'">
                            <svg v-if="task.status === 'done'" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            {{ task.status === 'done' ? '✓ Terminée' : 'Marquer comme terminée' }}
                        </button>
                        <button @click="showEditModal = true" class="btn btn-edit text-xs">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                            Modifier
                        </button>
                        <button @click="goBack" class="btn btn-secondary text-xs">
                            ← Retour à la liste
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal d'édition -->
        <TaskEditModal v-if="showEditModal && task" :task="task" @close="showEditModal = false"
            @saved="handleTaskUpdated" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTaskStore } from '@/stores/task.store';
import TaskEditModal from '@/components/tasks/TaskEditModal.vue';
import type { Task } from '@/types/task';

const route = useRoute();
const router = useRouter();
const taskStore = useTaskStore();

const task = ref<Task | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const showEditModal = ref(false);

onMounted(() => {
    loadTask();
});

const loadTask = async () => {
    const taskId = parseInt(route.params.id as string);

    if (isNaN(taskId)) {
        error.value = 'ID invalide';
        return;
    }

    try {
        loading.value = true;
        error.value = null;
        const loadedTask = await taskStore.fetchTask(taskId);
        task.value = loadedTask;
    } catch (err: any) {
        error.value = err.message || 'Échec du chargement';
        console.error('Erreur:', err);
    } finally {
        loading.value = false;
    }
};

const completeTask = async () => {
    if (!task.value || task.value.status === 'done') return;

    try {
        await taskStore.updateStatus(task.value.id, 'done');
        await loadTask();
    } catch (err) {
        console.error('Échec:', err);
    }
};

const handleTaskUpdated = (updatedTask: Task) => {
    task.value = updatedTask;
    showEditModal.value = false;
};

const goBack = () => {
    router.push({ name: 'tasks' });
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatStatus = (status: string) => {
    const statusMap: Record<string, string> = {
        'todo': 'À faire',
        'in_progress': 'En cours',
        'doing': 'En cours',
        'done': 'Terminée',
        'review': 'En revue',
        'backlog': 'Backlog'
    };
    return statusMap[status] || status;
};

const formatPriority = (priority: string) => {
    const priorityMap: Record<string, string> = {
        'low': 'Basse',
        'medium': 'Moyenne',
        'high': 'Haute',
        'critical': 'Critique'
    };
    return priorityMap[priority] || priority;
};
</script>

<style scoped>
/* Styles de base */
.task-detail {
    min-height: 100vh;
    background: #fafafa;
    padding: 16px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Typographie */
.text-xs {
    font-size: 11px;
    line-height: 1.25;
}

.text-sm {
    font-size: 13px;
    line-height: 1.3;
}

.text-lg {
    font-size: 18px;
    line-height: 1.4;
    font-weight: 600;
}

/* États */
.loading-container,
.error-container,
.not-found-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    background: white;
    border-radius: 8px;
    padding: 32px;
}

.loading-spinner {
    width: 32px;
    height: 32px;
    border: 2px solid #e5e5e5;
    border-top-color: #31b6b8;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 12px;
}

.error-icon,
.not-found-icon {
    margin-bottom: 16px;
}

.error-title,
.not-found-title {
    color: #474665;
    margin-bottom: 8px;
    font-weight: 600;
}

.error-message,
.not-found-message {
    color: #666;
    margin-bottom: 20px;
    max-width: 300px;
    text-align: center;
}

/* Contenu principal */
.task-content {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* En-tête */
.task-header {
    padding: 24px;
    border-bottom: 1px solid #e5e5e5;
    background: linear-gradient(to right, #fafafa, #ffffff);
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #474665;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
}

.back-btn:hover {
    color: #ab2283;
    border-color: #ab2283;
    background: #fff5ff;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.task-id {
    color: #999;
    font-family: 'Monaco', 'Consolas', monospace;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.edit-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #31b6b8;
    background: #f0f9ff;
    border: 1px solid #b3e5e6;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.edit-btn:hover {
    background: #e0f4f5;
    border-color: #31b6b8;
    color: #289396;
}

.task-title {
    color: #474665;
    margin-bottom: 16px;
    font-weight: 700;
}

.task-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 500;
    border: 1px solid transparent;
}

.badge-todo {
    background: #fff8e6;
    color: #d97706;
    border-color: #fed7aa;
}

.badge-in_progress,
.badge-doing {
    background: #f0f9ff;
    color: #31b6b8;
    border-color: #b3e5e6;
}

.badge-done {
    background: #f0fff4;
    color: #2ecc71;
    border-color: #c6f6d5;
}

.badge-completed {
    background: #ab2283;
    color: white;
    border-color: #8a1c6a;
}

.badge-backlog {
    background: #f5f5f5;
    color: #666;
    border-color: #e0e0e0;
}

.badge-priority-low {
    background: #f8f9fa;
    color: #474665;
    border-color: #e9ecef;
}

.badge-priority-medium {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.badge-priority-high {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.badge-priority-critical {
    background: #ab2283;
    color: white;
    border-color: #8a1c6a;
}

.badge-overdue {
    background: #fef2f2;
    color: #ab2283;
    border-color: #fecaca;
}

/* Sections */
.task-main {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.section {
    padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f0;
}

.section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #474665;
    font-weight: 600;
    margin-bottom: 16px;
}

.section-content {
    color: #666;
}

/* Description */
.description-text {
    white-space: pre-wrap;
    line-height: 1.6;
}

/* Détails */
.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

@media (max-width: 640px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px;
    background: #fafafa;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
}

.detail-label {
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 10px;
}

.detail-value {
    color: #474665;
    font-weight: 500;
}

.detail-value.overdue {
    color: #ab2283;
    font-weight: 600;
}

/* Barre de progression */
.progress-container {
    position: relative;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background: linear-gradient(to right, #31b6b8, #ab2283);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: 600;
    font-size: 10px;
}

/* Étiquettes */
.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.tag {
    background: #f0f9ff;
    color: #31b6b8;
    padding: 4px 10px;
    border-radius: 12px;
    border: 1px solid #b3e5e6;
    font-weight: 500;
}

/* Statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.stat-item {
    text-align: center;
    padding: 12px;
    background: #fafafa;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
}

.stat-value {
    display: block;
    color: #474665;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Actions */
.actions-section {
    padding-top: 20px;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

/* Boutons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    flex: 1;
    min-width: 120px;
}

.btn-primary {
    background: linear-gradient(to right, #31b6b8, #2aa5a7);
    color: white;
    box-shadow: 0 2px 4px rgba(49, 182, 184, 0.2);
}

.btn-primary:hover:not(:disabled) {
    background: linear-gradient(to right, #2aa5a7, #249497);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(49, 182, 184, 0.3);
}

.btn-primary.btn-disabled {
    background: #d1fae5;
    color: #065f46;
    cursor: not-allowed;
    box-shadow: none;
}

.btn-edit {
    background: linear-gradient(to right, #ab2283, #8a1c6a);
    color: white;
    box-shadow: 0 2px 4px rgba(171, 34, 131, 0.2);
}

.btn-edit:hover {
    background: linear-gradient(to right, #8a1c6a, #701654);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(171, 34, 131, 0.3);
}

.btn-secondary {
    background: #f5f5f5;
    color: #474665;
    border: 1px solid #e0e0e0;
}

.btn-secondary:hover {
    background: #e9e9e9;
    border-color: #d0d0d0;
}

/* Utilitaires */
.text-muted {
    color: #999;
    font-style: italic;
}

/* Animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive */
@media (max-width: 640px) {
    .task-detail {
        padding: 12px;
    }

    .task-header {
        padding: 16px;
    }

    .task-main {
        padding: 16px;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
</style>