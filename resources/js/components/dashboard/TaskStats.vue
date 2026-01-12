<template>
    <div class="task-stats">
        <h2 class="section-title">Statistiques des tâches</h2>

        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Chargement...</p>
        </div>

        <div v-else-if="error" class="error-state">
            <p>{{ error }}</p>
            <button @click="loadStats" class="retry-button">
                Réessayer
            </button>
        </div>

        <div v-else class="stats-content">
            <!-- Distribution par statut -->


            <!-- Statistiques rapides -->
            <div class="quick-stats">
                <div class="quick-stat">
                    <div class="quick-stat-icon overdue">
                        <ExclamationTriangleIcon />
                    </div>
                    <div class="quick-stat-content">
                        <div class="quick-stat-value">{{ taskStatsValue?.overdue_tasks || 0 }}</div>
                        <div class="quick-stat-label">En retard</div>
                    </div>
                </div>

                <div class="quick-stat">
                    <div class="quick-stat-icon upcoming">
                        <CalendarDaysIcon />
                    </div>
                    <div class="quick-stat-content">
                        <div class="quick-stat-value">{{ taskStatsValue?.upcoming_tasks || 0 }}</div>
                        <div class="quick-stat-label">À venir</div>
                    </div>
                </div>

                <div class="quick-stat">
                    <div class="quick-stat-icon completed">
                        <CheckCircleIcon />
                    </div>
                    <div class="quick-stat-content">
                        <div class="quick-stat-value">{{ taskStatsValue?.completed_this_month || 0 }}</div>
                        <div class="quick-stat-label">Terminées ce mois</div>
                    </div>
                </div>

                <div class="quick-stat">
                    <div class="quick-stat-icon rate">
                        <ChartBarIcon />
                    </div>
                    <div class="quick-stat-content">
                        <div class="quick-stat-value">{{ taskStatsValue?.completion_rate || 0 }}%</div>
                        <div class="quick-stat-label">Taux d'achèvement</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'; // Ajoutez onMounted
import { useDashboardStore } from '@/stores/dashboard.store';
import {
    ExclamationTriangleIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

const dashboardStore = useDashboardStore();

// Charge les statistiques au montage
const loadStats = async () => {
    try {
        await dashboardStore.fetchStats();
        console.log('Statistiques des tâches chargées avec succès.', dashboardStore.taskStats);
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
    }
};

onMounted(() => {
    // Vérifie si les données sont déjà chargées
    if (!dashboardStore.stats && !dashboardStore.loading) {
        loadStats();
    }
});

const loading = computed(() => dashboardStore.loading);
const error = computed(() => dashboardStore.error);
const taskStatsValue = computed(() => dashboardStore.taskStats);

const statusList = computed(() => {
    if (!taskStatsValue.value?.status_distribution) return [];

    return Object.entries(taskStatsValue.value.status_distribution).map(([key, data]: [string, any]) => ({
        key,
        label: data.label || key,
        count: data.count || 0,
        percentage: data.percentage || 0
    }));
});
</script>

<style scoped>
.task-stats {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.loading-state,
.error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #6b7280;
}

.loading-spinner {
    width: 2.5rem;
    height: 2.5rem;
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
    font-size: 0.875rem;
}

.retry-button:hover {
    background-color: #2563eb;
}

.empty-state {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
    font-style: italic;
}

.stats-section {
    margin-bottom: 2rem;
}

.subsection-title {
    font-size: 1rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 1rem;
}

.status-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.status-label {
    font-weight: 600;
    color: #374151;
}

.status-percentage {
    font-weight: 700;
    font-size: 0.875rem;
}

.status-todo .status-percentage {
    color: #f59e0b;
}

.status-doing .status-percentage {
    color: #3b82f6;
}

.status-done .status-percentage {
    color: #10b981;
}

.status-backlog .status-percentage {
    color: #6b7280;
}

.progress-bar {
    height: 0.5rem;
    background: #e5e7eb;
    border-radius: 0.25rem;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    border-radius: 0.25rem;
    transition: width 0.3s ease;
}

.status-todo .progress-fill {
    background: #f59e0b;
}

.status-doing .progress-fill {
    background: #3b82f6;
}

.status-done .progress-fill {
    background: #10b981;
}

.status-backlog .progress-fill {
    background: #6b7280;
}

.status-count {
    font-size: 0.875rem;
    color: #6b7280;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.quick-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
}

.quick-stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.quick-stat-icon svg {
    width: 1.5rem;
    height: 1.5rem;
}

.quick-stat-icon.overdue {
    background: #fee2e2;
    color: #dc2626;
}

.quick-stat-icon.upcoming {
    background: #dbeafe;
    color: #3b82f6;
}

.quick-stat-icon.completed {
    background: #d1fae5;
    color: #059669;
}

.quick-stat-icon.rate {
    background: #f3e8ff;
    color: #8b5cf6;
}

.quick-stat-content {
    flex: 1;
}

.quick-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.quick-stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .quick-stats {
        grid-template-columns: 1fr;
    }
}
</style>