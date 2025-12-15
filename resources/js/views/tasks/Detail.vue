<!-- src/views/tasks/Detail.vue -->
<template>
    <div class="task-detail" v-if="task">
        <!-- En-tête avec navigation -->
        <div class="task-header">
            <div class="header-left">
                <router-link :to="{ name: 'tasks' }" class="back-link">
                    ← Back to Tasks
                </router-link>
                <h1 class="task-title">{{ task.title }}</h1>
                <div class="task-meta">
                    <span class="task-id">#{{ task.id }}</span>
                    <span class="task-project" v-if="task.project">
                        • {{ task.project.name }}
                    </span>
                    <span class="task-created">
                        • Created {{ formatDate(task.created_at) }}
                    </span>
                </div>
            </div>

            <div class="header-right">
                <div class="task-actions">
                    <button v-if="task.status !== 'done'" @click="completeTask" class="btn btn-success"
                        :disabled="updatingStatus">
                        <span v-if="updatingStatus">...</span>
                        <span v-else>✓ Complete</span>
                    </button>

                    <button v-if="task.status === 'todo'" @click="startTask" class="btn btn-warning"
                        :disabled="updatingStatus">
                        <span v-if="updatingStatus">...</span>
                        <span v-else>▶ Start</span>
                    </button>

                    <button v-if="task.status === 'doing'" @click="resetToTodo" class="btn btn-secondary"
                        :disabled="updatingStatus">
                        <span v-if="updatingStatus">...</span>
                        <span v-else>↺ Reset</span>
                    </button>

                    <div class="dropdown">
                        <button class="btn btn-more">⋮</button>
                        <div class="dropdown-content">
                            <a @click="editTask">Edit Task</a>
                            <a @click="assignTask">Assign to...</a>
                            <a @click="duplicateTask">Duplicate</a>
                            <a @click="deleteTask" class="danger">Delete Task</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="task-content">
            <!-- Colonne de gauche - Détails -->
            <div class="task-details">
                <!-- Barre d'état et priorité -->
                <div class="status-priority-section">
                    <div class="status-box">
                        <label>Status</label>
                        <div class="status-buttons">
                            <button v-for="statusOption in statusOptions" :key="statusOption.value"
                                @click="updateStatus(statusOption.value)" :class="[
                                    'status-btn',
                                    `status-${statusOption.value}`,
                                    { 'active': task.status === statusOption.value }
                                ]" :disabled="updatingStatus">
                                {{ statusOption.label }}
                            </button>
                        </div>
                    </div>

                    <div class="priority-box">
                        <label>Priority</label>
                        <div class="priority-buttons">
                            <button v-for="priorityOption in priorityOptions" :key="priorityOption.value"
                                @click="updatePriority(priorityOption.value)" :class="[
                                    'priority-btn',
                                    `priority-${priorityOption.value}`,
                                    { 'active': task.priority === priorityOption.value }
                                ]">
                                {{ priorityOption.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Assignation -->
                <div class="assignee-section">
                    <label>Assigned To</label>
                    <div class="assignee-selector">
                        <div v-if="task.assigned_user" class="current-assignee">
                            <div class="user-avatar">
                                <span v-if="task.assigned_user.avatar">
                                    <img :src="task.assigned_user.avatar" :alt="task.assigned_user.name" />
                                </span>
                                <span v-else class="avatar-fallback">
                                    {{ getInitials(task.assigned_user.name) }}
                                </span>
                            </div>
                            <div class="user-info">
                                <span class="user-name">{{ task.assigned_user.name }}</span>
                                <span class="user-email">{{ task.assigned_user.email }}</span>
                            </div>
                            <button @click="unassignTask" class="btn-unassign" title="Unassign">
                                ×
                            </button>
                        </div>
                        <div v-else class="no-assignee">
                            <span>Not assigned</span>
                            <button @click="assignTask" class="btn-assign">+ Assign</button>
                        </div>
                    </div>
                </div>

                <!-- Dates et temps -->
                <div class="dates-section">
                    <div class="date-input">
                        <label>Start Date</label>
                        <input type="date" v-model="localTask.start_date" @change="updateDates"
                            :class="{ 'has-value': localTask.start_date }" />
                        <button v-if="localTask.start_date" @click="clearStartDate" class="btn-clear">
                            ×
                        </button>
                    </div>

                    <div class="date-input">
                        <label>Due Date</label>
                        <input type="date" v-model="localTask.due_date" @change="updateDates" :class="{
                            'has-value': localTask.due_date,
                            'overdue': isOverdue
                        }" />
                        <button v-if="localTask.due_date" @click="clearDueDate" class="btn-clear">
                            ×
                        </button>
                        <span v-if="isOverdue" class="overdue-badge">OVERDUE</span>
                    </div>

                    <div class="time-input">
                        <label>Estimated Time</label>
                        <div class="time-input-group">
                            <input type="number" v-model="localTask.estimated_time" @change="updateEstimatedTime"
                                min="0" placeholder="0" />
                            <span class="time-unit">hours</span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="description-section">
                    <label>Description</label>
                    <div v-if="!editingDescription" class="description-view">
                        <div class="description-content" :class="{ 'empty': !task.description }"
                            @click="startEditingDescription">
                            <p v-if="task.description">
                                {{ task.description }}
                            </p>
                            <p v-else class="placeholder">
                                Click to add description...
                            </p>
                        </div>
                    </div>
                    <div v-else class="description-edit">
                        <textarea v-model="descriptionEdit" ref="descriptionTextarea"
                            @keydown.ctrl.enter="saveDescription" @keydown.esc="cancelEditDescription"
                            placeholder="Add a detailed description..." rows="4"></textarea>
                        <div class="edit-actions">
                            <button @click="saveDescription" class="btn btn-primary">
                                Save
                            </button>
                            <button @click="cancelEditDescription" class="btn btn-secondary">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div class="tags-section">
                    <label>Tags</label>
                    <div class="tags-container">
                        <span v-for="(tag, index) in task.tags" :key="index" class="tag">
                            {{ tag }}
                            <button @click="removeTag(tag)" class="tag-remove">×</button>
                        </span>
                        <input v-if="addingTag" ref="tagInput" v-model="newTag" @keydown.enter="addTag"
                            @keydown.esc="cancelAddTag" @blur="addTag" placeholder="Add tag..." class="tag-input" />
                        <button v-else @click="startAddingTag" class="btn-add-tag">
                            + Add tag
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="stats-section" v-if="statistics">
                    <h3>Task Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Progress</span>
                            <div class="stat-value">
                                <div class="progress-bar">
                                    <div class="progress-fill" :style="{ width: task.progress + '%' }"></div>
                                </div>
                                <span class="progress-text">{{ task.progress }}%</span>
                            </div>
                        </div>

                        <div class="stat-item">
                            <span class="stat-label">Status</span>
                            <span class="stat-value status-badge" :class="`status-${task.status}`">
                                {{ task.status }}
                            </span>
                        </div>

                        <div class="stat-item">
                            <span class="stat-label">Overdue</span>
                            <span class="stat-value" :class="{ 'overdue': task.is_overdue }">
                                {{ task.is_overdue ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite - Activité et commentaires -->
            <div class="task-sidebar">
                <!-- Activité -->
                <div class="activity-section">
                    <h3>Activity</h3>
                    <div class="activity-list">
                        <div v-for="activity in activities" :key="activity.id" class="activity-item">
                            <div class="activity-avatar">
                                {{ getInitials(activity.user_name) }}
                            </div>
                            <div class="activity-content">
                                <div class="activity-header">
                                    <span class="activity-user">{{ activity.user_name }}</span>
                                    <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
                                </div>
                                <p class="activity-text">{{ activity.text }}</p>
                            </div>
                        </div>
                        <div v-if="activities.length === 0" class="no-activity">
                            No activity yet
                        </div>
                    </div>
                </div>

                <!-- Commentaires -->
                <div class="comments-section">
                    <h3>Comments ({{ comments.length }})</h3>
                    <div class="comment-input">
                        <div class="comment-avatar">
                            {{ userInitials }}
                        </div>
                        <div class="comment-form">
                            <textarea v-model="newComment" @keydown.ctrl.enter="addComment"
                                placeholder="Add a comment..." rows="2" ref="commentInput"></textarea>
                            <div class="comment-actions">
                                <button @click="addComment" class="btn btn-primary" :disabled="!newComment.trim()">
                                    Comment
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="comments-list">
                        <div v-for="comment in comments" :key="comment.id" class="comment-item">
                            <div class="comment-avatar">
                                {{ getInitials(comment.user_name) }}
                            </div>
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-user">{{ comment.user_name }}</span>
                                    <span class="comment-time">{{ formatTime(comment.created_at) }}</span>
                                    <button v-if="canEditComment(comment)" @click="editComment(comment)"
                                        class="btn-edit-comment">
                                        Edit
                                    </button>
                                    <button v-if="canDeleteComment(comment)" @click="deleteComment(comment.id)"
                                        class="btn-delete-comment">
                                        ×
                                    </button>
                                </div>
                                <div v-if="editingCommentId === comment.id" class="comment-edit">
                                    <textarea v-model="editingCommentText" ref="editCommentTextarea"
                                        @keydown.ctrl.enter="saveCommentEdit" @keydown.esc="cancelCommentEdit"
                                        rows="2"></textarea>
                                    <div class="edit-actions">
                                        <button @click="saveCommentEdit" class="btn btn-primary">
                                            Save
                                        </button>
                                        <button @click="cancelCommentEdit" class="btn btn-secondary">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                <p v-else class="comment-text">{{ comment.content }}</p>
                            </div>
                        </div>
                        <div v-if="comments.length === 0" class="no-comments">
                            No comments yet. Be the first to comment!
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <TaskEditModal v-if="showEditModal" :task="task" @close="showEditModal = false" @saved="handleTaskUpdated" />

        <UserAssignModal v-if="showAssignModal" :project-id="task.project_id" @close="showAssignModal = false"
            @assign="handleUserAssigned" />

        <ConfirmModal v-if="showDeleteModal" title="Delete Task"
            message="Are you sure you want to delete this task? This action cannot be undone." @confirm="confirmDelete"
            @cancel="showDeleteModal = false" />
    </div>

    <!-- Loading state -->
    <div v-else-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Loading task...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-container">
        <h2>Error Loading Task</h2>
        <p>{{ error }}</p>
        <router-link :to="{ name: 'tasks' }" class="btn btn-primary">
            Back to Tasks
        </router-link>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTaskStore } from '@/stores/task.store';
import { useAuthStore } from '@/stores/auth.store';
import TaskEditModal from '@/components/tasks/TaskEditModal.vue';
import UserAssignModal from '@/components/tasks/UserAssignModal.vue';
import ConfirmModal from '@/components/common/ConfirmModal.vue';
import type { Task } from '@/types/task';

// Router et store
const route = useRoute();
const router = useRouter();
const taskStore = useTaskStore();
const authStore = useAuthStore();

// State
const task = ref<Task | null>(null);
const localTask = ref<any>({});
const loading = ref(false);
const error = ref<string | null>(null);
const updatingStatus = ref(false);

// Modals
const showEditModal = ref(false);
const showAssignModal = ref(false);
const showDeleteModal = ref(false);

// Description editing
const editingDescription = ref(false);
const descriptionEdit = ref('');
const descriptionTextarea = ref<HTMLTextAreaElement | null>(null);

// Tags
const addingTag = ref(false);
const newTag = ref('');
const tagInput = ref<HTMLInputElement | null>(null);

// Comments
const comments = ref<any[]>([]);
const newComment = ref('');
const commentInput = ref<HTMLTextAreaElement | null>(null);
const editingCommentId = ref<number | null>(null);
const editingCommentText = ref('');

// Activity (simulé pour l'exemple)
const activities = ref<any[]>([
    { id: 1, user_name: 'John Doe', text: 'Task created', created_at: new Date().toISOString() },
    { id: 2, user_name: 'Jane Smith', text: 'Status changed to "doing"', created_at: new Date().toISOString() }
]);

// Options
const statusOptions = [
    { value: 'backlog', label: 'Backlog' },
    { value: 'todo', label: 'Todo' },
    { value: 'doing', label: 'Doing' },
    { value: 'done', label: 'Done' }
];

const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' }
];

// Computed
const isOverdue = computed(() => {
    if (!task.value?.due_date) return false;
    return new Date(task.value.due_date) < new Date() && task.value.status !== 'done';
});

const userInitials = computed(() => {
    const user = authStore.currentUser;
    return user ? getInitials(user.name) : '??';
});

const statistics = computed(() => {
    if (!task.value) return null;

    return {
        totalTime: '0h',
        commentsCount: comments.value.length,
        filesCount: task.value.files?.length || 0
    };
});

// Lifecycle
onMounted(async () => {
    await loadTask();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});

// Methods
const loadTask = async () => {
    const taskId = parseInt(route.params.id as string);

    if (isNaN(taskId)) {
        error.value = 'Invalid task ID';
        return;
    }

    try {
        loading.value = true;
        error.value = null;

        const loadedTask = await taskStore.fetchTask(taskId);
        task.value = loadedTask;
        localTask.value = { ...loadedTask };

        // Load comments (simulé)
        loadComments();
    } catch (err: any) {
        error.value = err.message || 'Failed to load task';
        console.error('Error loading task:', err);
    } finally {
        loading.value = false;
    }
};

const loadComments = async () => {
    // Simuler le chargement des commentaires
    // Dans la réalité, vous appelleriez votre API
    comments.value = [
        {
            id: 1,
            user_id: 1,
            user_name: 'John Doe',
            content: 'This task looks good!',
            created_at: new Date().toISOString()
        },
        {
            id: 2,
            user_id: 2,
            user_name: 'Jane Smith',
            content: 'I think we need more details here.',
            created_at: new Date().toISOString()
        }
    ];
};

// Status updates
const updateStatus = async (status: string) => {
    if (!task.value || task.value.status === status) return;

    try {
        updatingStatus.value = true;
        const updatedTask = await taskStore.updateStatus(task.value.id, status);
        task.value = updatedTask;
    } catch (err) {
        console.error('Failed to update status:', err);
    } finally {
        updatingStatus.value = false;
    }
};

const completeTask = async () => {
    await updateStatus('done');
};

const startTask = async () => {
    await updateStatus('doing');
};

const resetToTodo = async () => {
    await updateStatus('todo');
};

const updatePriority = async (priority: string) => {
    if (!task.value || task.value.priority === priority) return;

    try {
        await taskStore.updateTask(task.value.id, { priority });
        task.value.priority = priority;
    } catch (err) {
        console.error('Failed to update priority:', err);
    }
};

// Assignation
const assignTask = () => {
    showAssignModal.value = true;
};

const unassignTask = async () => {
    if (!task.value) return;

    try {
        await taskStore.updateTask(task.value.id, { assigned_to: null });
        task.value.assigned_to = undefined;
    } catch (err) {
        console.error('Failed to unassign task:', err);
    }
};

const handleUserAssigned = async (userId: number) => {
    if (!task.value) return;

    try {
        await taskStore.assignTask(task.value.id, userId);
        // Recharger la tâche pour obtenir les données de l'utilisateur
        await loadTask();
    } catch (err) {
        console.error('Failed to assign user:', err);
    } finally {
        showAssignModal.value = false;
    }
};

// Dates
const updateDates = async () => {
    if (!task.value) return;

    try {
        await taskStore.updateTask(task.value.id, {
            start_date: localTask.value.start_date || null,
            due_date: localTask.value.due_date || null
        });

        // Mettre à jour la tâche locale
        if (task.value) {
            task.value.start_date = localTask.value.start_date;
            task.value.due_date = localTask.value.due_date;
        }
    } catch (err) {
        console.error('Failed to update dates:', err);
    }
};

const clearStartDate = () => {
    localTask.value.start_date = null;
    updateDates();
};

const clearDueDate = () => {
    localTask.value.due_date = null;
    updateDates();
};

const updateEstimatedTime = async () => {
    if (!task.value) return;

    const estimatedTime = parseInt(localTask.value.estimated_time) || 0;

    try {
        await taskStore.updateTask(task.value.id, {
            estimated_time: estimatedTime
        });
        task.value.estimated_time = estimatedTime;
    } catch (err) {
        console.error('Failed to update estimated time:', err);
    }
};

// Description
const startEditingDescription = async () => {
    editingDescription.value = true;
    descriptionEdit.value = task.value?.description || '';

    await nextTick();
    if (descriptionTextarea.value) {
        descriptionTextarea.value.focus();
        descriptionTextarea.value.select();
    }
};

const saveDescription = async () => {
    if (!task.value) return;

    try {
        await taskStore.updateTask(task.value.id, {
            description: descriptionEdit.value
        });
        task.value.description = descriptionEdit.value;
        editingDescription.value = false;
    } catch (err) {
        console.error('Failed to save description:', err);
    }
};

const cancelEditDescription = () => {
    editingDescription.value = false;
    descriptionEdit.value = '';
};

// Tags
const startAddingTag = () => {
    addingTag.value = true;
    newTag.value = '';

    nextTick(() => {
        if (tagInput.value) {
            tagInput.value.focus();
        }
    });
};

const addTag = async () => {
    if (!newTag.value.trim() || !task.value) {
        addingTag.value = false;
        return;
    }

    const tag = newTag.value.trim();
    const currentTags = task.value.tags || [];

    if (currentTags.includes(tag)) {
        addingTag.value = false;
        newTag.value = '';
        return;
    }

    const updatedTags = [...currentTags, tag];

    try {
        await taskStore.updateTask(task.value.id, { tags: updatedTags });
        task.value.tags = updatedTags;
        addingTag.value = false;
        newTag.value = '';
    } catch (err) {
        console.error('Failed to add tag:', err);
    }
};

const cancelAddTag = () => {
    addingTag.value = false;
    newTag.value = '';
};

const removeTag = async (tagToRemove: string) => {
    if (!task.value) return;

    const updatedTags = (task.value.tags || []).filter(tag => tag !== tagToRemove);

    try {
        await taskStore.updateTask(task.value.id, { tags: updatedTags });
        task.value.tags = updatedTags;
    } catch (err) {
        console.error('Failed to remove tag:', err);
    }
};

// Comments
const addComment = async () => {
    if (!newComment.value.trim()) return;

    const comment = {
        content: newComment.value.trim(),
        user_id: authStore.currentUser?.id,
        user_name: authStore.currentUser?.name || 'Anonymous'
    };

    // Simuler l'ajout d'un commentaire
    comments.value.unshift({
        id: comments.value.length + 1,
        ...comment,
        created_at: new Date().toISOString()
    });

    newComment.value = '';

    // Dans la réalité, vous enverriez à l'API :
    // await commentService.createComment(task.value.id, comment);
};

const editComment = (comment: any) => {
    editingCommentId.value = comment.id;
    editingCommentText.value = comment.content;

    nextTick(() => {
        if (editingCommentId.value === comment.id) {
            const textarea = document.querySelector(`#edit-comment-${comment.id} textarea`) as HTMLTextAreaElement;
            if (textarea) {
                textarea.focus();
                textarea.select();
            }
        }
    });
};

const saveCommentEdit = async () => {
    if (!editingCommentId.value) return;

    const commentIndex = comments.value.findIndex(c => c.id === editingCommentId.value);
    if (commentIndex !== -1) {
        comments.value[commentIndex].content = editingCommentText.value;

        // Dans la réalité, vous mettriez à jour via l'API
        // await commentService.updateComment(editingCommentId.value, { content: editingCommentText.value });
    }

    cancelCommentEdit();
};

const cancelCommentEdit = () => {
    editingCommentId.value = null;
    editingCommentText.value = '';
};

const deleteComment = async (commentId: number) => {
    if (confirm('Delete this comment?')) {
        const index = comments.value.findIndex(c => c.id === commentId);
        if (index !== -1) {
            comments.value.splice(index, 1);

            // Dans la réalité, vous supprimeriez via l'API
            // await commentService.deleteComment(commentId);
        }
    }
};

const canEditComment = (comment: any) => {
    return comment.user_id === authStore.currentUser?.id;
};

const canDeleteComment = (comment: any) => {
    return comment.user_id === authStore.currentUser?.id || authStore.isAdmin;
};

// Gestion de la tâche
const editTask = () => {
    showEditModal.value = true;
};

const handleTaskUpdated = async (updatedTask: Task) => {
    task.value = updatedTask;
    showEditModal.value = false;
};

const duplicateTask = async () => {
    if (!task.value) return;

    try {
        const { id, created_at, updated_at, ...taskData } = task.value;
        taskData.title = `${taskData.title} (Copy)`;

        const duplicatedTask = await taskStore.createTask(taskData);
        router.push({ name: 'task-detail', params: { id: duplicatedTask.id } });
    } catch (err) {
        console.error('Failed to duplicate task:', err);
    }
};

const deleteTask = () => {
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    if (!task.value) return;

    try {
        await taskStore.deleteTask(task.value.id);
        router.push({ name: 'tasks' });
    } catch (err) {
        console.error('Failed to delete task:', err);
        showDeleteModal.value = false;
    }
};

// Polling pour les mises à jour en temps réel
let pollingInterval: NodeJS.Timeout | null = null;

const startPolling = () => {
    // Mettre à jour toutes les 30 secondes
    pollingInterval = setInterval(async () => {
        if (task.value) {
            try {
                const updatedTask = await taskStore.fetchTask(task.value.id);
                task.value = updatedTask;
            } catch (err) {
                console.error('Polling error:', err);
            }
        }
    }, 30000);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

// Helpers
const getInitials = (name: string) => {
    if (!name) return '??';
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;

    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;

    return formatDate(dateString);
};
</script>

<style scoped>
.task-detail {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.header-left {
    flex: 1;
}

.back-link {
    display: inline-block;
    margin-bottom: 10px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
}

.back-link:hover {
    color: #333;
    text-decoration: underline;
}

.task-title {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #333;
}

.task-meta {
    font-size: 14px;
    color: #666;
}

.task-meta span {
    margin-right: 10px;
}

.header-right {
    display: flex;
    gap: 10px;
}

.task-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Content Layout */
.task-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

/* Task Details */
.task-details>* {
    margin-bottom: 25px;
}

.status-priority-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.status-box,
.priority-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.status-buttons,
.priority-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.status-btn,
.priority-btn {
    padding: 6px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    background: white;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.status-btn:hover:not(.active),
.priority-btn:hover:not(.active) {
    border-color: #999;
}

.status-btn.active {
    border-color: transparent;
    color: white;
}

.priority-btn.active {
    border-color: transparent;
    color: white;
}

/* Status colors */
.status-backlog.active {
    background: #666;
}

.status-todo.active {
    background: #2196f3;
}

.status-doing.active {
    background: #ff9800;
}

.status-done.active {
    background: #4caf50;
}

/* Priority colors */
.priority-low.active {
    background: #4caf50;
}

.priority-medium.active {
    background: #ff9800;
}

.priority-high.active {
    background: #f44336;
}

/* Assignee */
.assignee-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.assignee-selector {
    display: flex;
    align-items: center;
    gap: 12px;
}

.current-assignee {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    flex: 1;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #666;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.user-info {
    flex: 1;
}

.user-name {
    display: block;
    font-weight: 500;
    color: #333;
}

.user-email {
    display: block;
    font-size: 12px;
    color: #666;
}

.btn-unassign {
    background: none;
    border: none;
    font-size: 20px;
    color: #999;
    cursor: pointer;
    padding: 0 5px;
}

.btn-unassign:hover {
    color: #f44336;
}

.no-assignee {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    width: 100%;
}

.btn-assign {
    background: #2196f3;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

/* Dates */
.dates-section {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.date-input,
.time-input {
    position: relative;
}

.date-input input,
.time-input-group {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.date-input input:focus,
.time-input-group:focus-within {
    outline: none;
    border-color: #2196f3;
}

.date-input input.has-value {
    border-color: #4caf50;
}

.date-input input.overdue {
    border-color: #f44336;
}

.time-input-group {
    display: flex;
    align-items: center;
    border: 2px solid #e0e0e0;
    padding: 0;
}

.time-input-group input {
    flex: 1;
    border: none;
    padding: 10px 12px;
    outline: none;
}

.time-unit {
    padding: 0 12px;
    color: #666;
    font-size: 14px;
    background: #f8f9fa;
    height: 100%;
    display: flex;
    align-items: center;
}

.btn-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    font-size: 18px;
}

.btn-clear:hover {
    color: #f44336;
}

.overdue-badge {
    position: absolute;
    top: -8px;
    right: 10px;
    background: #f44336;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
}

/* Description */
.description-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.description-view {
    cursor: pointer;
}

.description-content {
    min-height: 60px;
    padding: 12px;
    background: white;
    border-radius: 6px;
    border: 2px solid transparent;
}

.description-content:hover {
    border-color: #e0e0e0;
}

.description-content.empty {
    color: #999;
}

.description-content .placeholder {
    color: #999;
    font-style: italic;
}

.description-edit textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    resize: vertical;
}

.description-edit textarea:focus {
    outline: none;
    border-color: #2196f3;
}

.edit-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}

/* Tags */
.tags-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #e0e0e0;
    padding: 4px 8px 4px 12px;
    border-radius: 20px;
    font-size: 13px;
}

.tag-remove {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 16px;
    padding: 0 4px;
}

.tag-remove:hover {
    color: #f44336;
}

.tag-input {
    padding: 4px 8px;
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    font-size: 13px;
    width: 100px;
}

.tag-input:focus {
    outline: none;
    border-color: #2196f3;
}

.btn-add-tag {
    background: none;
    border: 2px dashed #e0e0e0;
    color: #666;
    padding: 4px 12px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
}

.btn-add-tag:hover {
    border-color: #999;
    color: #333;
}

/* Stats */
.stats-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.stats-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-weight: 600;
    color: #333;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #8bc34a);
    transition: width 0.3s;
}

.progress-text {
    min-width: 40px;
    text-align: right;
}

.stat-value.overdue {
    color: #f44336;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

/* Sidebar */
.task-sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.activity-section,
.comments-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.activity-section h3,
.comments-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.activity-list,
.comments-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item,
.comment-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #e0e0e0;
}

.activity-item:last-child,
.comment-item:last-child {
    border-bottom: none;
}

.activity-avatar,
.comment-avatar {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 50%;
    background: #2196f3;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
}

.activity-content,
.comment-content {
    flex: 1;
}

.activity-header,
.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.activity-user,
.comment-user {
    font-weight: 600;
    color: #333;
}

.activity-time,
.comment-time {
    font-size: 12px;
    color: #666;
}

.activity-text,
.comment-text {
    font-size: 14px;
    color: #333;
    margin: 0;
}

.comment-input {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.comment-form {
    flex: 1;
}

.comment-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    resize: vertical;
}

.comment-form textarea:focus {
    outline: none;
    border-color: #2196f3;
}

.comment-actions {
    margin-top: 8px;
    text-align: right;
}

.no-activity,
.no-comments {
    text-align: center;
    color: #999;
    padding: 20px;
    font-style: italic;
}

.btn-edit-comment,
.btn-delete-comment {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 12px;
    padding: 2px 6px;
}

.btn-edit-comment:hover {
    color: #2196f3;
}

.btn-delete-comment:hover {
    color: #f44336;
}

.comment-edit {
    margin-top: 8px;
}

.comment-edit textarea {
    width: 100%;
    padding: 8px 10px;
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    font-size: 14px;
    resize: vertical;
}

/* Loading and Error States */
.loading-container,
.error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 60vh;
    text-align: center;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e0e0e0;
    border-top-color: #2196f3;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.error-container h2 {
    color: #f44336;
    margin-bottom: 10px;
}

/* Dropdown */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background: white;
    min-width: 160px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    z-index: 1000;
}

.dropdown-content a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    cursor: pointer;
}

.dropdown-content a:hover {
    background: #f5f5f5;
}

.dropdown-content a.danger {
    color: #f44336;
}

.dropdown:hover .dropdown-content {
    display: block;
}

/* Buttons */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: #2196f3;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #1976d2;
}

.btn-secondary {
    background: #e0e0e0;
    color: #333;
}

.btn-secondary:hover:not(:disabled) {
    background: #bdbdbd;
}

.btn-success {
    background: #4caf50;
    color: white;
}

.btn-success:hover:not(:disabled) {
    background: #388e3c;
}

.btn-warning {
    background: #ff9800;
    color: white;
}

.btn-warning:hover:not(:disabled) {
    background: #f57c00;
}

.btn-danger {
    background: #f44336;
    color: white;
}

.btn-danger:hover:not(:disabled) {
    background: #d32f2f;
}

.btn-more {
    padding: 8px 12px;
    background: #f5f5f5;
    color: #666;
    border: 1px solid #e0e0e0;
}

.btn-more:hover {
    background: #e0e0e0;
}

/* Responsive */
@media (max-width: 1024px) {
    .task-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .task-header {
        flex-direction: column;
        gap: 15px;
    }

    .header-right {
        width: 100%;
        justify-content: flex-end;
    }

    .status-priority-section {
        grid-template-columns: 1fr;
    }

    .dates-section {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>