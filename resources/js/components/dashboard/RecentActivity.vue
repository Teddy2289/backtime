<template>
    <div class="recent-activity">
        <h2 class="section-title">Activité récente</h2>

        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Chargement...</p>
        </div>

        <div v-else-if="error" class="error-state">
            <p>{{ error }}</p>
        </div>

        <div v-else class="activity-content">
            <!-- Tabs -->
            <div class="tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="['tab-button', { active: activeTab === tab.key }]"
                >
                    {{ tab.label }}
                    <span class="tab-count">{{ getTabCount(tab.key) }}</span>
                </button>
            </div>

            <!-- Content -->
            <div class="tab-content">
                <!-- Tâches récentes -->
                <div v-if="activeTab === 'tasks'" class="activity-list">
                    <div v-if="!recentTasks.length" class="empty-state">
                        <p>Aucune tâche récente</p>
                    </div>

                    <div v-else class="activity-items">
                        <div
                            v-for="task in recentTasks"
                            :key="task.id"
                            class="activity-item"
                        >
                            <div class="activity-icon">
                                <ClipboardDocumentIcon />
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <span class="task-title">{{
                                        task.title
                                    }}</span>
                                    <span
                                        class="task-status"
                                        :class="`status-${task.status}`"
                                    >
                                        {{ getStatusLabel(task.status) }}
                                    </span>
                                </div>
                                <div class="activity-meta">
                                    <span
                                        v-if="task.assigned_to"
                                        class="assigned-to"
                                    >
                                        <UserIcon class="meta-icon" />
                                        {{ task.assigned_to.name }}
                                    </span>
                                    <span v-if="task.project" class="project">
                                        <FolderIcon class="meta-icon" />
                                        {{ task.project.name }}
                                    </span>
                                    <span class="timestamp">
                                        <ClockIcon class="meta-icon" />
                                        {{ task.created_at }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commentaires récents -->
                <div v-else-if="activeTab === 'comments'" class="activity-list">
                    <div v-if="!recentComments.length" class="empty-state">
                        <p>Aucun commentaire récent</p>
                    </div>

                    <div v-else class="activity-items">
                        <div
                            v-for="comment in recentComments"
                            :key="comment.id"
                            class="activity-item"
                        >
                            <div class="activity-icon">
                                <ChatBubbleLeftRightIcon />
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <span class="user-name">{{
                                        comment.user.name
                                    }}</span>
                                    <span
                                        class="comment-edited"
                                        v-if="comment.is_edited"
                                        >(modifié)</span
                                    >
                                </div>
                                <div class="activity-content">
                                    {{ comment.content }}
                                </div>
                                <div class="activity-meta">
                                    <span class="task">
                                        <DocumentTextIcon class="meta-icon" />
                                        {{ comment.task.title }}
                                    </span>
                                    <span class="timestamp">
                                        <ClockIcon class="meta-icon" />
                                        {{ comment.created_at }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fichiers récents -->
                <div v-else-if="activeTab === 'files'" class="activity-list">
                    <div v-if="!recentFiles.length" class="empty-state">
                        <p>Aucun fichier récent</p>
                    </div>

                    <div v-else class="activity-items">
                        <div
                            v-for="file in recentFiles"
                            :key="file.id"
                            class="activity-item"
                        >
                            <div
                                class="activity-icon file-icon"
                                :class="{ image: file.is_image }"
                            >
                                <component :is="getFileIcon(file)" />
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <span class="file-name">{{
                                        file.file_name
                                    }}</span>
                                    <span class="file-size">{{
                                        file.file_size
                                    }}</span>
                                </div>
                                <div class="activity-meta">
                                    <span class="uploader">
                                        <UserIcon class="meta-icon" />
                                        {{ file.uploader.name }}
                                    </span>
                                    <span class="task">
                                        <DocumentTextIcon class="meta-icon" />
                                        {{ file.task.title }}
                                    </span>
                                    <span class="timestamp">
                                        <ClockIcon class="meta-icon" />
                                        {{ file.created_at }}
                                    </span>
                                </div>
                            </div>
                            <button class="download-button" title="Télécharger">
                                <ArrowDownTrayIcon />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useDashboardStore } from "@/stores/dashboard.store";
import {
    ClipboardDocumentIcon,
    ChatBubbleLeftRightIcon,
    PaperClipIcon,
    UserIcon,
    FolderIcon,
    ClockIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    PhotoIcon,
    DocumentIcon,
} from "@heroicons/vue/24/outline";

const dashboardStore = useDashboardStore();

const loading = computed(() => dashboardStore.loading);
const error = computed(() => dashboardStore.error);
const recentActivity = computed(() => dashboardStore.recentActivity);

const recentTasks = computed(() => recentActivity.value?.recent_tasks || []);
const recentComments = computed(
    () => recentActivity.value?.recent_comments || []
);
const recentFiles = computed(() => recentActivity.value?.recent_files || []);

const activeTab = ref<"tasks" | "comments" | "files">("tasks");

const tabs = [
    { key: "tasks" as const, label: "Tâches", icon: ClipboardDocumentIcon },
    {
        key: "comments" as const,
        label: "Commentaires",
        icon: ChatBubbleLeftRightIcon,
    },
    { key: "files" as const, label: "Fichiers", icon: PaperClipIcon },
];

const getTabCount = (tab: string) => {
    switch (tab) {
        case "tasks":
            return recentTasks.value.length;
        case "comments":
            return recentComments.value.length;
        case "files":
            return recentFiles.value.length;
        default:
            return 0;
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        todo: "À faire",
        doing: "En cours",
        done: "Terminé",
        backlog: "Backlog",
    };
    return labels[status] || status;
};

const getFileIcon = (file: any) => {
    if (file.is_image) return PhotoIcon;
    return DocumentIcon;
};
</script>

<style scoped>
.recent-activity {
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

.tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1.5rem;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
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

.tab-count {
    background: #e5e7eb;
    color: #374151;
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    min-width: 1.5rem;
    text-align: center;
}

.tab-button.active .tab-count {
    background: #3b82f6;
    color: white;
}

.activity-items {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s ease;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: #f9fafb;
    border-radius: 0.5rem;
}

.activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #e0f2fe;
    color: #0ea5e9;
}

.activity-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.activity-icon.file-icon {
    background: #f3e8ff;
    color: #8b5cf6;
}

.activity-icon.file-icon.image {
    background: #fce7f3;
    color: #db2777;
}

.activity-details {
    flex: 1;
    min-width: 0;
}

.activity-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    gap: 0.5rem;
}

.task-title,
.user-name,
.file-name {
    font-weight: 500;
    color: #1f2937;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.task-status {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    flex-shrink: 0;
}

.status-todo {
    background: #fef3c7;
    color: #92400e;
}

.status-doing {
    background: #dbeafe;
    color: #1e40af;
}

.status-done {
    background: #d1fae5;
    color: #065f46;
}

.status-backlog {
    background: #f3f4f6;
    color: #374151;
}

.comment-edited {
    font-size: 0.75rem;
    color: #9ca3af;
    font-style: italic;
}

.file-size {
    font-size: 0.75rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
}

.activity-content {
    color: #4b5563;
    font-size: 0.875rem;
    line-height: 1.5;
    margin-bottom: 0.5rem;
    word-break: break-word;
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.activity-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.meta-icon {
    width: 0.875rem;
    height: 0.875rem;
}

.download-button {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.download-button:hover {
    background: #f3f4f6;
    color: #4b5563;
}

.download-button svg {
    width: 1.25rem;
    height: 1.25rem;
}

@media (max-width: 768px) {
    .tabs {
        overflow-x: auto;
    }

    .tab-button {
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }

    .activity-meta {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .activity-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>
