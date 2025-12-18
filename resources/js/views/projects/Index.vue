<template>
    <div class="tasks-container">
        <!-- Header -->
        <div class="header-container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="page-title">Projets</h1>
                    <p class="page-subtitle" v-if="pagination">
                        {{ pagination.total }} projets au total
                    </p>
                </div>
                <button @click="showCreateModal = true" class="create-button">
                    <svg class="button-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouveau projet
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
                <input v-model="filters.search" @input="debouncedSearch" placeholder="Rechercher un projet..."
                    class="search-input" />
            </div>

            <div class="filter-group">
                <select v-model="filters.status" @change="handleFilterChange" class="filter-select">
                    <option value="">Tous les statuts</option>
                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </div>

            <div class="filter-group">
                <select v-model="filters.team_id" @change="handleFilterChange" class="filter-select">
                    <option value="">Toutes les équipes</option>
                    <option v-for="team in teams" :key="team.id" :value="team.id">
                        {{ team.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Statistics -->
        <div v-if="statistics && !loading" class="statistics-grid">
            <div class="stat-card" v-for="stat in [
                { key: 'total', label: 'Total', icon: 'folder', color: '#31b6b8' },
                { key: 'active', label: 'Actifs', icon: 'check', color: '#10b981' },
                { key: 'on_hold', label: 'En attente', icon: 'clock', color: '#f59e0b' },
                { key: 'completed', label: 'Terminés', icon: 'x-circle', color: '#ef4444' }
            ]" :key="stat.key">
                <div class="stat-icon" :style="{ backgroundColor: `${stat.color}20` }">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        :style="{ color: stat.color }">
                        <path v-if="stat.icon === 'folder'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        <path v-if="stat.icon === 'check'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M5 13l4 4L19 7" />
                        <path v-if="stat.icon === 'clock'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path v-if="stat.icon === 'x-circle'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ statistics[stat.key] }}</p>
                    <p class="stat-label">{{ stat.label }}</p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p class="loading-text">Chargement des projets...</p>
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
                <button @click="retryFetch" class="retry-button">
                    Réessayer
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && projects.length === 0" class="empty-state">
            <svg class="empty-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <h3 class="empty-title">Aucun projet trouvé</h3>
            <p class="empty-message">
                {{ filters.search || filters.status || filters.team_id ?
                    'Essayez de modifier vos filtres de recherche' :
                    'Commencez par créer votre premier projet' }}
            </p>
            <button @click="showCreateModal = true" class="empty-action-button">
                Créer un projet
            </button>
        </div>

        <!-- Projects Grid -->
        <div v-else class="tasks-grid">
            <div v-for="project in projects" :key="project.id" class="task-card" :class="{
                'project-overdue': isOverdue(project.end_date),
                [`project-status-${project.status}`]: true,
                'project-completed': project.status === 'completed'
            }" @click="showDetail(project)">
                <!-- Project Header -->
                <div class="task-header">
                    <div class="task-title-section">
                        <h3 class="task-title">{{ project.name }}</h3>
                        <div class="task-id">#{{ project.id }}</div>
                    </div>
                    <div class="task-status-badge" :class="`status-${project.status}`">
                        {{ getStatusLabel(project.status) }}
                    </div>
                </div>

                <!-- Project Description -->
                <p v-if="project.description" class="task-description">
                    {{ truncateDescription(project.description) }}
                </p>
                <p v-else class="task-description-placeholder">
                    Aucune description
                </p>

                <!-- Project Meta -->
                <div class="task-meta">
                    <div class="meta-item">
                        <svg class="meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="meta-text">
                            {{ formatDate(project.start_date) }}
                        </span>
                    </div>

                    <div class="meta-item">
                        <svg class="meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="meta-text" :class="{ 'overdue-text': isOverdue(project.end_date) }">
                            {{ formatDate(project.end_date) }}
                        </span>
                    </div>

                    <div class="priority-badge" :class="`priority-${getPriorityFromStatus(project.status)}`">
                        {{ getPriorityLabel(project.status) }}
                    </div>
                </div>

                <!-- Project Footer -->
                <div class="task-footer">
                    <div class="assigned-to" v-if="project.team">
                        <div class="avatar">
                            {{ getInitials(project.team.name) }}
                        </div>
                        <span class="assigned-name">{{ project.team.name }}</span>
                    </div>
                    <div v-else class="unassigned">
                        Aucune équipe
                    </div>

                    <div class="task-actions">
                        <button @click.stop="editProject(project)" class="action-button edit-button" title="Modifier">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click.stop="confirmDelete(project)" class="action-button delete-button"
                            title="Supprimer">
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

        <!-- Create/Edit Modal -->
        <ProjectModal v-if="showCreateModal || showEditModal" :show="showCreateModal || showEditModal"
            :project="editingProject" :teams="teams" @close="closeModal" @saved="handleProjectSaved" />

        <!-- Detail Modal -->
        <ProjectDetailModal v-if="selectedProject" :show="!!selectedProject" :project="selectedProject"
            @close="selectedProject = null" @edit="editProject(selectedProject)" @deleted="handleProjectDeleted" />

        <!-- Delete Confirmation -->
        <ConfirmModal v-if="projectToDelete" :show="!!projectToDelete" title="Supprimer le projet"
            message="Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible."
            confirm-text="Supprimer" cancel-text="Annuler" variant="danger" @confirm="deleteProject"
            @cancel="projectToDelete = null" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useProjectTeamStore } from '@/stores/projectsTeams.store';
import { useTeamStore } from '@/stores/team.store';
import type { ProjectTeam } from '@/types/projectsTeams';
import ProjectModal from '@/components/projects/ProjectModal.vue';
import ProjectDetailModal from '@/components/projects/ProjectDetailModal.vue';
import ConfirmModal from '@/components/projects/ConfirmModal.vue';
import debounce from 'lodash/debounce';

// Stores
const projectStore = useProjectTeamStore();
const teamStore = useTeamStore();

const {
    projects: storeProjects,
    pagination: storePagination,
    statistics: storeStatistics,
    loading: storeLoading,
    error: storeError
} = storeToRefs(projectStore);

const { teams: storeTeams } = storeToRefs(teamStore);

// State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingProject = ref<ProjectTeam | null>(null);
const selectedProject = ref<ProjectTeam | null>(null);
const projectToDelete = ref<ProjectTeam | null>(null);

const filters = ref({
    search: '',
    status: '',
    team_id: undefined as number | undefined,
    order_by: 'created_at',
    order_direction: 'desc' as 'asc' | 'desc',
    start_date_from: '',
    start_date_to: '',
    end_date_from: '',
    end_date_to: '',
    page: 1,
    per_page: 12,
});

// Computed
const projects = computed(() => storeProjects.value);
const pagination = computed(() => storePagination.value);
const statistics = computed(() => storeStatistics.value);
const loading = computed(() => storeLoading.value);
const error = computed(() => storeError.value);
const teams = computed(() => storeTeams.value);

const statusOptions = [
    { value: 'active', label: 'Actif' },
    { value: 'completed', label: 'Terminé' },
    { value: 'on_hold', label: 'En attente' },
    { value: 'cancelled', label: 'Annulé' },
];

// Méthodes utilitaires
const getStatusLabel = (status: string): string => {
    const option = statusOptions.find(opt => opt.value === status);
    return option?.label || status.charAt(0).toUpperCase() + status.slice(1);
};

const getPriorityFromStatus = (status: string): string => {
    const priorityMap: Record<string, string> = {
        'active': 'medium',
        'completed': 'low',
        'on_hold': 'medium',
        'cancelled': 'high'
    };
    return priorityMap[status] || 'medium';
};

const getPriorityLabel = (status: string): string => {
    const priorityMap: Record<string, string> = {
        'active': 'Actif',
        'completed': 'Terminé',
        'on_hold': 'En attente',
        'cancelled': 'Annulé'
    };
    return priorityMap[status] || status;
};

const getInitials = (name: string): string => {
    if (!name) return '??';
    return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
};

const formatDate = (date: string | null): string => {
    if (!date) return 'Non définie';
    try {
        return new Date(date).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch {
        return 'Date invalide';
    }
};

const isOverdue = (date: string | null): boolean => {
    if (!date) return false;
    try {
        return new Date(date) < new Date() && new Date(date).toDateString() !== new Date().toDateString();
    } catch {
        return false;
    }
};

const truncateDescription = (description: string): string => {
    if (!description) return '';
    return description.length > 120
        ? description.substring(0, 120) + '...'
        : description;
};

// Filtres
const debouncedSearch = debounce(() => {
    filters.value.page = 1;
    applyFilters();
}, 500);

const handleFilterChange = () => {
    filters.value.page = 1;
    applyFilters();
};

const applyFilters = async () => {
    try {
        await projectStore.fetchProjects(filters.value);
        if (filters.value.team_id) {
            await projectStore.fetchStatistics(filters.value.team_id);
        } else {
            await projectStore.fetchStatistics();
        }
    } catch (err) {
        console.error('Erreur lors de l\'application des filtres:', err);
    }
};

// Pagination
const goToPage = (page: number) => {
    if (page < 1 || page > (pagination.value?.last_page || 1)) return;
    filters.value.page = page;
    applyFilters();
};

// Actions sur les projets
const editProject = (project: ProjectTeam) => {
    editingProject.value = project;
    showEditModal.value = true;
};

const showDetail = (project: ProjectTeam) => {
    selectedProject.value = project;
};

const confirmDelete = (project: ProjectTeam) => {
    projectToDelete.value = project;
};

const deleteProject = async () => {
    if (!projectToDelete.value) return;
    try {
        await projectStore.deleteProject(projectToDelete.value.id);
        projectToDelete.value = null;
    } catch (error) {
        console.error('Échec de la suppression du projet:', error);
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingProject.value = null;
};

const handleProjectSaved = () => {
    closeModal();
    applyFilters();
};

const handleProjectDeleted = () => {
    selectedProject.value = null;
    applyFilters();
};

const retryFetch = () => {
    applyFilters();
};

// Lifecycle
onMounted(async () => {
    try {
        await Promise.all([
            projectStore.fetchProjects(filters.value),
            projectStore.fetchStatistics(),
            teamStore.fetchTeams(),
        ]);
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    }
});

// Watch
watch(
    () => filters.value.page,
    () => applyFilters()
);
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

/* Statistics */
.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
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

.task-card.project-completed {
    opacity: 0.8;
}

.task-card.project-completed:hover {
    opacity: 1;
}

.task-card.project-overdue {
    border-left: 4px solid #dc2626;
}

.task-card.project-status-active {
    border-left: 4px solid #10b981;
}

.task-card.project-status-on_hold {
    border-left: 4px solid #f59e0b;
}

.task-card.project-status-cancelled {
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

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-completed {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-on_hold {
    background-color: #fef3c7;
    color: #92400e;
}

.status-cancelled {
    background-color: #fef2f2;
    color: #991b1b;
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

.edit-button:hover {
    color: #3b82f6;
    border-color: #bfdbfe;
    background-color: #eff6ff;
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

    .statistics-grid {
        grid-template-columns: 1fr;
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