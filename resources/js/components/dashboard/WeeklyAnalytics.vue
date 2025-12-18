<template>
    <div class="weekly-analytics">
        <div class="section-header">
            <h2 class="section-title">Analytics Hebdomadaires</h2>
            <div class="period-selector">
                <button @click="changePeriod('week')" :class="{ active: period === 'week' }">
                    7 jours
                </button>
                <button @click="changePeriod('month')" :class="{ active: period === 'month' }">
                    30 jours
                </button>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Chargement...</p>
        </div>

        <div v-else-if="error" class="error-state">
            <p>{{ error }}</p>
            <button @click="loadData" class="retry-button">
                Réessayer
            </button>
        </div>

        <div v-else-if="hasData" class="analytics-content">
            <!-- Chart Container -->
            <div class="chart-container">
                <canvas ref="chartCanvas"></canvas>
            </div>

            <!-- Stats Summary -->
            <div class="stats-summary">
                <div class="stat-summary">
                    <div class="stat-summary-icon tasks">
                        <ClipboardDocumentIcon />
                    </div>
                    <div class="stat-summary-content">
                        <div class="stat-summary-value">{{ weeklyAnalyticsData?.weekly_tasks_completed || 0 }}</div>
                        <div class="stat-summary-label">Tâches terminées</div>
                    </div>
                </div>

                <div class="stat-summary">
                    <div class="stat-summary-icon comments">
                        <ChatBubbleLeftRightIcon />
                    </div>
                    <div class="stat-summary-content">
                        <div class="stat-summary-value">{{ totalComments }}</div>
                        <div class="stat-summary-label">Commentaires</div>
                    </div>
                </div>

                <div class="stat-summary">
                    <div class="stat-summary-icon files">
                        <PaperClipIcon />
                    </div>
                    <div class="stat-summary-content">
                        <div class="stat-summary-value">{{ totalFiles }}</div>
                        <div class="stat-summary-label">Fichiers</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="empty-state">
            <p>Aucune donnée disponible pour cette période</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useDashboardStore } from '@/stores/dashboard.store';
import { Chart, registerables } from 'chart.js';
import {
    ClipboardDocumentIcon,
    ChatBubbleLeftRightIcon,
    PaperClipIcon
} from '@heroicons/vue/24/outline';

Chart.register(...registerables);

const dashboardStore = useDashboardStore();
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;
const period = ref<'week' | 'month'>('week');

// Fonction pour charger les données
const loadData = async () => {
    try {
        await dashboardStore.fetchStats();
    } catch (error) {
        console.error('Erreur lors du chargement des analytics:', error);
    }
};

// Charge les données au montage
onMounted(() => {
    if (!dashboardStore.stats && !dashboardStore.loading) {
        loadData();
    }
});

const loading = computed(() => dashboardStore.loading);
const error = computed(() => dashboardStore.error);
const weeklyAnalyticsData = computed(() => dashboardStore.weeklyAnalytics);

// Vérifie s'il y a des données à afficher
const hasData = computed(() => {
    return weeklyAnalyticsData.value &&
        (weeklyAnalyticsData.value.labels?.length > 0 ||
            weeklyAnalyticsData.value.weekly_tasks_completed > 0);
});

const totalComments = computed(() => {
    if (!weeklyAnalyticsData.value?.comments) return 0;
    return weeklyAnalyticsData.value.comments.reduce((a: number, b: number) => a + b, 0);
});

const totalFiles = computed(() => {
    if (!weeklyAnalyticsData.value?.files) return 0;
    return weeklyAnalyticsData.value.files.reduce((a: number, b: number) => a + b, 0);
});

const changePeriod = (newPeriod: 'week' | 'month') => {
    period.value = newPeriod;
    // Note: Vous devriez implémenter un appel API spécifique pour chaque période
    // Pour l'instant, rechargez simplement les données
    loadData();
};

const renderChart = () => {
    if (!chartCanvas.value || !weeklyAnalyticsData.value) return;

    // Détruire le chart existant
    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = chartCanvas.value.getContext('2d');
    if (!ctx) return;

    const labels = weeklyAnalyticsData.value.labels || [];
    const tasksData = weeklyAnalyticsData.value.tasks || [];
    const commentsData = weeklyAnalyticsData.value.comments || [];
    const filesData = weeklyAnalyticsData.value.files || [];

    // Vérifier si on a des données à afficher
    const hasChartData = tasksData.length > 0 || commentsData.length > 0 || filesData.length > 0;

    if (!hasChartData) {
        // Afficher un message ou un graphique vide
        return;
    }

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Tâches',
                    data: tasksData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Commentaires',
                    data: commentsData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Fichiers',
                    data: filesData,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
};

// Surveille les changements de données
watch(weeklyAnalyticsData, () => {
    if (weeklyAnalyticsData.value) {
        // Petit délai pour s'assurer que le DOM est mis à jour
        setTimeout(() => {
            renderChart();
        }, 100);
    }
}, { deep: true });

// Surveille le changement de période
watch(period, () => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
    // Re-render quand les données sont prêtes
    if (weeklyAnalyticsData.value) {
        renderChart();
    }
});

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>

<style scoped>
.weekly-analytics {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.period-selector {
    display: flex;
    gap: 0.5rem;
}

.period-selector button {
    padding: 0.375rem 0.75rem;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s ease;
}

.period-selector button:hover {
    background: #e5e7eb;
}

.period-selector button.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.loading-state,
.error-state,
.empty-state {
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
    font-style: italic;
}

.chart-container {
    height: 300px;
    margin-bottom: 1.5rem;
}

.stats-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-summary {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
}

.stat-summary-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-summary-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.stat-summary-icon.tasks {
    background: #dbeafe;
    color: #3b82f6;
}

.stat-summary-icon.comments {
    background: #d1fae5;
    color: #10b981;
}

.stat-summary-icon.files {
    background: #f3e8ff;
    color: #8b5cf6;
}

.stat-summary-content {
    flex: 1;
}

.stat-summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.stat-summary-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.125rem;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .period-selector {
        align-self: stretch;
    }

    .period-selector button {
        flex: 1;
        text-align: center;
    }

    .stats-summary {
        grid-template-columns: 1fr;
    }
}
</style>