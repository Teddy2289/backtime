<template>
    <div class="overview-stats">
        <h2 class="section-title">Aperçu général</h2>

        <!-- Ajoutez un état de chargement -->
        <div v-if="dashboardStore.loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Chargement des statistiques...</p>
        </div>

        <!-- Ajoutez un état d'erreur -->
        <div v-else-if="dashboardStore.error" class="error-state">
            <p>Erreur: {{ dashboardStore.error }}</p>
            <button @click="dashboardStore.fetchStats()" class="retry-button">
                Réessayer
            </button>
        </div>

        <!-- Affichez les statistiques seulement si elles existent -->
        <div v-else-if="statsList.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow" v-for="stat in statsList" :key="stat.label">
               <div class="flex items-center gap-4">
<div class="stat-icon" :style="{ backgroundColor: stat.color }">
                    <component :is="stat.icon" class="icon" />
                </div>
                <div class="stat-content">
                    <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                    <p class="text-sm text-gray-600">{{ stat.label }}</p>
                </div>
               </div>
                
            </div>
        </div>

        <!-- État vide -->
        <div v-else class="empty-state">
            <p>Aucune donnée disponible</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'; // Ajoutez onMounted
import { useDashboardStore } from '@/stores/dashboard.store';
import {
    UserGroupIcon,
    ClipboardDocumentCheckIcon,
    FolderOpenIcon,
    UsersIcon,
    ChatBubbleLeftRightIcon,
    PaperClipIcon,
    ClockIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const dashboardStore = useDashboardStore();

// Chargez les données quand le composant est monté
onMounted(() => {
    // Vérifiez si les données n'ont pas déjà été chargées
    if (!dashboardStore.stats && !dashboardStore.loading) {
        dashboardStore.fetchStats();
    }
});

const statsList = computed(() => {
    const overview = dashboardStore.overviewStats;
    if (!overview) return [];

    return [
        {
            label: 'Utilisateurs',
            value: overview.total_users || 0,
            icon: UserGroupIcon,
            color: '#e0f2fe'
        },
        {
            label: 'Tâches',
            value: overview.total_tasks || 0,
            icon: ClipboardDocumentCheckIcon,
            color: '#f0fdf4'
        },
        {
            label: 'Projets',
            value: overview.total_projects || 0,
            icon: FolderOpenIcon,
            color: '#fef3c7'
        },
        {
            label: 'Équipes',
            value: overview.total_teams || 0,
            icon: UsersIcon,
            color: '#fce7f3'
        }
    ];
});
</script>

<style scoped>
.overview-stats {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon .icon {
    width: 1.5rem;
    height: 1.5rem;
    color: #374151;
}

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

/* Styles pour les états de chargement et d'erreur */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6b7280;
}

.loading-spinner {
    width: 2rem;
    height: 2rem;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.error-state {
    padding: 2rem;
    text-align: center;
    color: #ef4444;
}

.retry-button {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background-color: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
}

.empty-state {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
    font-style: italic;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-value {
        font-size: 1.25rem;
    }
}
</style>