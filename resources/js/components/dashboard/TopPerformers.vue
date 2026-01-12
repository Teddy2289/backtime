<template>
    <div class="top-performers">
        <h2 class="section-title">Top Performers</h2>

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

        <div v-else class="performers-content">
            <!-- Tabs -->
            <div class="tabs">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                    :class="['tab-button', { active: activeTab === tab.key }]">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Content -->
            <div class="tab-content">
                <!-- Top Task Completers -->
                <div v-if="activeTab === 'tasks'" class="performers-list">
                    <div v-if="!topTaskCompleters.length" class="empty-state">
                        <p>Aucune donnée disponible</p>
                    </div>

                    <div v-else class="performers-items">
                        <div v-for="(performer, index) in topTaskCompleters"
                            :key="`tasks-${performer.user_id || index}`" class="performer-item">
                            <div class="rank">{{ index + 1 }}</div>
                            <div class="performer-avatar">
                                <img v-if="performer.avatar" :src="performer.avatar" :alt="performer.name" />
                                <div v-else class="avatar-placeholder">
                                    {{ getInitials(performer.name) }}
                                </div>
                            </div>
                            <div class="performer-details">
                                <div class="performer-name">{{ performer.name || 'Utilisateur' }}</div>
                                <div class="performer-stats">
                                    <span class="stat">
                                        <CheckCircleIcon class="stat-icon" />
                                        {{ performer.completed_tasks || 0 }} tâches
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Commenters -->
                <div v-else-if="activeTab === 'comments'" class="performers-list">
                    <div v-if="!topCommenters.length" class="empty-state">
                        <p>Aucune donnée disponible</p>
                    </div>

                    <div v-else class="performers-items">
                        <div v-for="(performer, index) in topCommenters" :key="`comments-${performer.user_id || index}`"
                            class="performer-item">
                            <div class="rank">{{ index + 1 }}</div>
                            <div class="performer-avatar">
                                <img v-if="performer.avatar" :src="performer.avatar" :alt="performer.name" />
                                <div v-else class="avatar-placeholder">
                                    {{ getInitials(performer.name) }}
                                </div>
                            </div>
                            <div class="performer-details">
                                <div class="performer-name">{{ performer.name || 'Utilisateur' }}</div>
                                <div class="performer-stats">
                                    <span class="stat">
                                        <ChatBubbleLeftRightIcon class="stat-icon" />
                                        {{ performer.comment_count || 0 }} commentaires
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top File Uploaders -->
                <div v-else-if="activeTab === 'files'" class="performers-list">
                    <div v-if="!topFileUploaders.length" class="empty-state">
                        <p>Aucune donnée disponible</p>
                    </div>

                    <div v-else class="performers-items">
                        <div v-for="(performer, index) in topFileUploaders" :key="`files-${performer.user_id || index}`"
                            class="performer-item">
                            <div class="rank">{{ index + 1 }}</div>
                            <div class="performer-avatar">
                                <img v-if="performer.avatar" :src="performer.avatar" :alt="performer.name" />
                                <div v-else class="avatar-placeholder">
                                    {{ getInitials(performer.name) }}
                                </div>
                            </div>
                            <div class="performer-details">
                                <div class="performer-name">{{ performer.name || 'Utilisateur' }}</div>
                                <div class="performer-stats">
                                    <span class="stat">
                                        <PaperClipIcon class="stat-icon" />
                                        {{ performer.file_count || 0 }} fichiers
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard.store';
import {
    CheckCircleIcon,
    ChatBubbleLeftRightIcon,
    PaperClipIcon
} from '@heroicons/vue/24/outline';

const dashboardStore = useDashboardStore();

// Fonction pour charger les données
const loadData = async () => {
    try {
        await dashboardStore.fetchStats();
    } catch (error) {
        console.error('Erreur lors du chargement des top performers:', error);
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
const topPerformersData = computed(() => dashboardStore.topPerformers);

const topTaskCompleters = computed(() => {
    const performers = topPerformersData.value?.top_task_completers || [];
    // S'assurer que chaque performer a les propriétés requises
    return performers.map(performer => ({
        user_id: performer.user_id || 0,
        name: performer.name || 'Utilisateur',
        avatar: performer.avatar || '',
        completed_tasks: performer.completed_tasks || 0
    }));
});

const topCommenters = computed(() => {
    const performers = topPerformersData.value?.top_commenters || [];
    return performers.map(performer => ({
        user_id: performer.user_id || 0,
        name: performer.name || 'Utilisateur',
        avatar: performer.avatar || '',
        comment_count: performer.comment_count || 0
    }));
});

const topFileUploaders = computed(() => {
    const performers = topPerformersData.value?.top_file_uploaders || [];
    return performers.map(performer => ({
        user_id: performer.user_id || 0,
        name: performer.name || 'Utilisateur',
        avatar: performer.avatar || '',
        file_count: performer.file_count || 0
    }));
});

const activeTab = ref<'tasks' | 'comments' | 'files'>('tasks');

const tabs = [
    { key: 'tasks' as const, label: 'Tâches complétées' },
    { key: 'comments' as const, label: 'Commentaires' },
    { key: 'files' as const, label: 'Fichiers' }
];

const getInitials = (name: string) => {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};
</script>

<style scoped>
.top-performers {
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

.tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1.5rem;
    overflow-x: auto;
}

.tab-button {
    padding: 0.75rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.tab-button:hover {
    color: #374151;
    background: #f9fafb;
}

.tab-button.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
    font-weight: 600;
}

.tab-content {
    min-height: 200px;
}

.performers-list {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.performers-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.performer-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    transition: background 0.2s ease;
    animation: slideIn 0.3s ease;
    animation-fill-mode: both;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.performer-item:nth-child(1) {
    animation-delay: 0.1s;
}

.performer-item:nth-child(2) {
    animation-delay: 0.2s;
}

.performer-item:nth-child(3) {
    animation-delay: 0.3s;
}

.performer-item:nth-child(4) {
    animation-delay: 0.4s;
}

.performer-item:nth-child(5) {
    animation-delay: 0.5s;
}

.performer-item:hover {
    background: #f9fafb;
}

.rank {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #374151;
    font-weight: 700;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    flex-shrink: 0;
}

.performer-item:nth-child(1) .rank {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: white;
}

.performer-item:nth-child(2) .rank {
    background: linear-gradient(135deg, #d1d5db, #9ca3af);
    color: white;
}

.performer-item:nth-child(3) .rank {
    background: linear-gradient(135deg, #f472b6, #ec4899);
    color: white;
}

.performer-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #e5e7eb;
}

.performer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.performer-details {
    flex: 1;
    min-width: 0;
}

.performer-name {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.performer-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.stat-icon {
    width: 0.875rem;
    height: 0.875rem;
}

@media (max-width: 768px) {
    .tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
    }

    .tab-button {
        padding: 0.75rem 1rem;
        flex: 1;
        text-align: center;
    }

    .performer-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>