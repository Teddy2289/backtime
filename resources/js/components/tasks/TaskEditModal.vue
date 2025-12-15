<!-- src/components/tasks/TaskEditModal.vue -->
<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h2>Edit Task</h2>
                <button @click="$emit('close')" class="btn-close">×</button>
            </div>

            <form @submit.prevent="saveChanges" class="modal-form">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input id="title" v-model="form.title" type="text" required placeholder="Enter task title" />
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" v-model="form.description" placeholder="Enter task description"
                        rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="project_id">Project</label>
                        <select id="project_id" v-model="form.project_id" required>
                            <option value="">Select project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assigned_to">Assign To</label>
                        <select id="assigned_to" v-model="form.assigned_to">
                            <option :value="null">Unassigned</option>
                            <option v-for="user in assignableUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" v-model="form.status">
                            <option value="backlog">Backlog</option>
                            <option value="todo">Todo</option>
                            <option value="doing">Doing</option>
                            <option value="done">Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" v-model="form.priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input id="start_date" v-model="form.start_date" type="date" />
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input id="due_date" v-model="form.due_date" type="date" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="estimated_time">Estimated Time (hours)</label>
                    <input id="estimated_time" v-model.number="form.estimated_time" type="number" min="0" step="0.5" />
                </div>

                <div class="form-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span v-if="loading">Saving...</span>
                        <span v-else>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { taskService } from '@/services/task.service';
import type { Task, UpdateTaskData } from '@/types/task';
import { p } from 'vue-router/dist/router-CWoNjPRp.mjs';

interface Props {
    task: Task;
}

interface Emits {
    (e: 'close'): void;
    (e: 'saved', task: Task): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = ref<UpdateTaskData>({
    title: props.task.title,
    description: props.task.description,
    status: props.task.status,
    priority: props.task.priority,
    assigned_to: props.task.assigned_to,
    start_date: props.task.start_date?.split('T')[0],
    due_date: props.task.due_date?.split('T')[0],
    estimated_time: props.task.estimated_time,
    tags: props.task.tags,
    project_id: props.task.project_id
});

const projects = ref<any[]>([]);
const assignableUsers = ref<any[]>([]);
const loading = ref(false);

onMounted(async () => {
    await loadAssignableUsers();
    // Load projects here if needed
});

watch(() => props.task.project_id, async () => {
    await loadAssignableUsers();
});

const loadAssignableUsers = async () => {
    try {
        const users = await taskService.getAssignableUsers(props.task.project_id);
        assignableUsers.value = users;
    } catch (err) {
        console.error('Failed to load assignable users:', err);
    }
};

const saveChanges = async () => {
    try {
        loading.value = true;

        const updatedTask = await taskService.updateTask(props.task.id, form.value);
        emit('saved', updatedTask);
    } catch (err) {
        console.error('Failed to update task:', err);
        alert('Failed to update task. Please try again.');
    } finally {
        loading.value = false;
    }
};

const closeModal = (e: MouseEvent) => {
    if ((e.target as HTMLElement).classList.contains('modal-overlay')) {
        emit('close');
    }
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}

.btn-close:hover {
    color: #333;
}

.modal-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2196f3;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>