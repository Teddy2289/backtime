<!-- src/views/tasks/List.vue -->
<template>
    <div class="tasks-list">
        <div class="header">
            <h1>Tasks</h1>
            <button @click="showCreateModal = true" class="btn-primary">
                + New Task
            </button>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <input v-model="searchQuery" @input="handleSearch" placeholder="Search tasks..." class="search-input" />

            <select v-model="statusFilter" @change="handleFilterChange">
                <option value="">All Status</option>
                <option value="backlog">Backlog</option>
                <option value="todo">Todo</option>
                <option value="doing">Doing</option>
                <option value="done">Done</option>
            </select>

            <select v-model="priorityFilter" @change="handleFilterChange">
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading">
            Loading tasks...
        </div>

        <!-- Error -->
        <div v-if="error" class="error">
            {{ error }}
        </div>

        <!-- Liste des tâches -->
        <div v-if="tasks.length > 0" class="tasks-grid">
            <div v-for="task in tasks" :key="task.id" class="task-card" :class="{
                'task-overdue': task.is_overdue,
                [`task-priority-${task.priority}`]: true
            }" @click="openTask(task)">
                <div class="task-header">
                    <span class="task-title">{{ task.title }}</span>
                    <span class="task-status" :class="`status-${task.status}`">
                        {{ task.status }}
                    </span>
                </div>

                <div class="task-description">
                    {{ truncateDescription(task.description) }}
                </div>

                <div class="task-footer">
                    <div class="task-meta">
                        <span class="task-priority">
                            {{ task.priority }}
                        </span>
                        <span v-if="task.due_date" class="task-due-date">
                            Due: {{ formatDate(task.due_date) }}
                        </span>
                    </div>

                    <div class="task-actions">
                        <button v-if="task.status !== 'done'" @click.stop="completeTask(task.id)" class="btn-small">
                            Complete
                        </button>
                        <button @click.stop="deleteTask(task.id)" class="btn-small btn-danger">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination" class="pagination">
            <button :disabled="pagination.current_page === 1" @click="goToPage(pagination.current_page - 1)">
                Previous
            </button>

            <span>
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>

            <button :disabled="pagination.current_page === pagination.last_page"
                @click="goToPage(pagination.current_page + 1)">
                Next
            </button>
        </div>

        <!-- Modal de création -->
        <TaskCreateModal v-if="showCreateModal" @close="showCreateModal = false" @created="handleTaskCreated" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useTaskStore } from '@/stores/task.store';
import TaskCreateModal from '@/components/tasks/TaskCreateModal.vue';

// Composition API avec Pinia
const taskStore = useTaskStore();
const searchQuery = ref('');
const statusFilter = ref('');
const priorityFilter = ref('');
const showCreateModal = ref(false);

// Computed properties
const tasks = computed(() => taskStore.getTasks);
const loading = computed(() => taskStore.isLoading);
const error = computed(() => taskStore.getError);
const pagination = computed(() => taskStore.getPagination);

onMounted(async () => {
    await taskStore.fetchTasks();
});

const handleSearch = () => {
    taskStore.fetchTasks({
        search: searchQuery.value,
        status: statusFilter.value,
        priority: priorityFilter.value
    });
};

const handleFilterChange = () => {
    taskStore.fetchTasks({
        search: searchQuery.value,
        status: statusFilter.value,
        priority: priorityFilter.value
    });
};

const openTask = (task: any) => {
    // Naviguer vers la page de détail ou ouvrir un modal
    console.log('Open task:', task);
};

const completeTask = async (id: number) => {
    try {
        await taskStore.updateStatus(id, 'done');
        // Optionnel: Rafraîchir la liste
        await taskStore.fetchTasks();
    } catch (error) {
        console.error('Failed to complete task:', error);
    }
};

const deleteTask = async (id: number) => {
    if (confirm('Are you sure you want to delete this task?')) {
        try {
            await taskStore.deleteTask(id);
        } catch (error) {
            console.error('Failed to delete task:', error);
        }
    }
};

const handleTaskCreated = async () => {
    showCreateModal.value = false;
    await taskStore.fetchTasks();
};

const goToPage = async (page: number) => {
    await taskStore.fetchTasks({
        page,
        search: searchQuery.value,
        status: statusFilter.value,
        priority: priorityFilter.value
    });
};

// Helper functions
const truncateDescription = (description?: string) => {
    if (!description) return '';
    return description.length > 100
        ? description.substring(0, 100) + '...'
        : description;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<style scoped>
.tasks-list {
    padding: 20px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.task-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: box-shadow 0.3s;
}

.task-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.task-card.task-overdue {
    border-left: 4px solid #f44336;
}

.task-priority-low {
    border-left: 4px solid #4caf50;
}

.task-priority-medium {
    border-left: 4px solid #ff9800;
}

.task-priority-high {
    border-left: 4px solid #f44336;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.task-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-backlog {
    background-color: #e0e0e0;
    color: #616161;
}

.status-todo {
    background-color: #bbdefb;
    color: #1976d2;
}

.status-doing {
    background-color: #fff3cd;
    color: #856404;
}

.status-done {
    background-color: #d4edda;
    color: #155724;
}

.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.task-actions {
    display: flex;
    gap: 5px;
}
</style>