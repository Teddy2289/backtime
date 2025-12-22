<!-- src/components/tasks/TaskCreateModal.vue -->
<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h2>Create New Task</h2>
                <button @click="$emit('close')" class="btn-close">×</button>
            </div>

            <form @submit.prevent="createTask" class="modal-form">
                <!-- Titre -->
                <div class="form-group">
                    <label for="title" class="required">Task Title</label>
                    <input id="title" v-model="form.title" type="text" placeholder="Enter task title" required
                        :class="{ 'error': errors.title }" @input="clearError('title')" />
                    <div v-if="errors.title" class="error-message">
                        {{ errors.title }}
                    </div>
                </div>

                <!-- Projet -->
                <div class="form-group">
                    <label for="project_id" class="required">Project</label>
                    <div class="select-wrapper">
                        <select id="project_id" v-model="form.project_id" required
                            :class="{ 'error': errors.project_id }" @change="onProjectChange">
                            <option value="">Select a project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.name }}
                            </option>
                        </select>
                        <div class="select-arrow">▼</div>
                    </div>
                    <div v-if="errors.project_id" class="error-message">
                        {{ errors.project_id }}
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <div class="textarea-wrapper">
                        <textarea id="description" v-model="form.description" placeholder="Describe the task details..."
                            rows="4" @input="updateDescriptionCount"></textarea>
                        <div class="char-count" :class="{ 'limit': descriptionCount > 1000 }">
                            {{ descriptionCount }}/1000
                        </div>
                    </div>
                    <div class="hint">
                        Markdown is supported. Use **bold**, *italic*, and `code` formatting.
                    </div>
                </div>

                <!-- Assigné à -->
                <div class="form-group">
                    <label for="assigned_to">Assign To</label>
                    <div class="assignee-selector">
                        <div class="select-wrapper">
                            <select id="assigned_to" v-model="form.assigned_to"
                                :disabled="!form.project_id || teamMembersLoading"
                                :class="{ 'error': errors.assigned_to }">
                                <option :value="null">Unassigned</option>
                                <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.role || 'member' }})
                                </option>
                            </select>
                            <div class="select-arrow">▼</div>
                        </div>
                        <div v-if="teamMembersLoading" class="loading-indicator">
                            Loading team members...
                        </div>
                    </div>
                    <div v-if="errors.assigned_to" class="error-message">
                        {{ errors.assigned_to }}
                    </div>
                    <div v-if="form.project_id && teamMembers.length === 0 && !teamMembersLoading" class="hint">
                        No team members found for this project.
                    </div>
                </div>

                <!-- Statut et Priorité -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <div class="status-buttons">
                            <button v-for="status in statusOptions" :key="status.value" type="button"
                                @click="form.status = status.value" :class="[
                                    'status-btn',
                                    `status-${status.value}`,
                                    { 'active': form.status === status.value }
                                ]" :title="status.description">
                                {{ status.label }}
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <div class="priority-buttons">
                            <button v-for="priority in priorityOptions" :key="priority.value" type="button"
                                @click="form.priority = priority.value" :class="[
                                    'priority-btn',
                                    `priority-${priority.value}`,
                                    { 'active': form.priority === priority.value }
                                ]" :title="priority.description">
                                {{ priority.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dates -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <div class="date-input">
                            <input id="start_date" v-model="form.start_date" type="date" :min="minStartDate"
                                :max="form.due_date" />
                            <button v-if="form.start_date" @click="clearDate('start_date')" type="button"
                                class="btn-clear-date" title="Clear date">
                                ×
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <div class="date-input">
                            <input id="due_date" v-model="form.due_date" type="date"
                                :min="form.start_date || minStartDate" />
                            <button v-if="form.due_date" @click="clearDate('due_date')" type="button"
                                class="btn-clear-date" title="Clear date">
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Temps estimé -->
                <div class="form-group">
                    <label for="estimated_time">Estimated Time</label>
                    <div class="time-input-group">
                        <input id="estimated_time" v-model.number="form.estimated_time" type="number" min="0" step="0.5"
                            placeholder="0" />
                        <div class="time-unit">hours</div>
                        <div class="time-presets">
                            <button v-for="preset in timePresets" :key="preset" type="button"
                                @click="form.estimated_time = preset" class="time-preset-btn">
                                {{ preset }}h
                            </button>
                        </div>
                    </div>
                    <div class="hint">
                        Enter estimated time in hours (0.5 = 30 minutes)
                    </div>
                </div>

                <!-- Tags -->
                <div class="form-group">
                    <label>Tags</label>
                    <div class="tags-input">
                        <div class="tags-container">
                            <span v-for="(tag, index) in form.tags" :key="index" class="tag">
                                {{ tag }}
                                <button @click="removeTag(index)" type="button" class="tag-remove" title="Remove tag">
                                    ×
                                </button>
                            </span>
                            <input v-model="newTag" type="text" placeholder="Add tag..." @keydown.enter.prevent="addTag"
                                @keydown.esc="newTag = ''" class="tag-input" />
                        </div>
                        <div class="tags-hint">
                            Press Enter to add tag. Common tags:
                            <button v-for="commonTag in commonTags" :key="commonTag" @click="addCommonTag(commonTag)"
                                type="button" class="common-tag-btn">
                                {{ commonTag }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Options avancées -->
                <div class="form-group advanced-section">
                    <button type="button" @click="showAdvanced = !showAdvanced" class="btn-toggle-advanced">
                        <span class="toggle-icon">{{ showAdvanced ? '▼' : '▶' }}</span>
                        Advanced Options
                    </button>

                    <div v-if="showAdvanced" class="advanced-options">
                        <!-- Parent Task -->
                        <div class="form-group">
                            <label for="parent_task_id">Parent Task</label>
                            <select id="parent_task_id" v-model="form.parent_task_id" :disabled="!form.project_id">
                                <option :value="null">None (Main Task)</option>
                                <option v-for="task in availableParentTasks" :key="task.id" :value="task.id">
                                    #{{ task.id }} {{ task.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Points de story -->
                        <div class="form-group">
                            <label for="story_points">Story Points</label>
                            <div class="story-points">
                                <button v-for="points in storyPointsOptions" :key="points" type="button"
                                    @click="form.story_points = points" :class="[
                                        'story-point-btn',
                                        { 'active': form.story_points === points }
                                    ]">
                                    {{ points }}
                                </button>
                            </div>
                        </div>

                        <!-- Confidential -->
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input v-model="form.is_confidential" type="checkbox" class="checkbox" />
                                <span class="checkbox-custom"></span>
                                Confidential Task
                            </label>
                            <div class="hint">
                                Only assigned users and admins can view this task
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input v-model="form.send_notifications" type="checkbox" class="checkbox" />
                                <span class="checkbox-custom"></span>
                                Send Notifications
                            </label>
                            <div class="hint">
                                Notify assigned users and team members
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-secondary" :disabled="loading">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span v-if="loading" class="loading-spinner"></span>
                        {{ loading ? 'Creating...' : 'Create Task' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { taskService } from '@/services/task.service';
import { useTaskStore } from '@/stores/task.store';
import type { CreateTaskData } from '@/types/task';
import { projectsTeamsService } from '@/services/projectsTeams.service';

interface Emits {
    (e: 'close'): void;
    (e: 'created', task: any): void;
}

interface Project {
    id: number;
    name: string;
    team_id?: number;
}

interface TeamMember {
    id: number;
    name: string;
    email: string;
    role?: string;
    avatar?: string;
}

const emit = defineEmits<Emits>();

// État du formulaire
const form = ref<CreateTaskData & {
    parent_task_id?: number | null;
    story_points?: number;
    is_confidential?: boolean;
    send_notifications?: boolean;
}>({
    title: '',
    project_id: undefined,
    description: '',
    assigned_to: undefined,
    status: 'todo',
    priority: 'medium',
    start_date: '',
    due_date: '',
    estimated_time: undefined,
    tags: [],
    parent_task_id: null,
    story_points: undefined,
    is_confidential: false,
    send_notifications: true
});

// Données
const projects = ref<Project[]>([]);
const teamMembers = ref<TeamMember[]>([]);
const availableParentTasks = ref<any[]>([]);

// État
const loading = ref(false);
const teamMembersLoading = ref(false);
const showAdvanced = ref(false);
const newTag = ref('');
const errors = ref<Record<string, string>>({});

// Options
const statusOptions = [
    { value: 'backlog', label: 'Backlog', description: 'Not yet started' },
    { value: 'todo', label: 'Todo', description: 'Ready to start' },
    { value: 'doing', label: 'Doing', description: 'In progress' },
    { value: 'done', label: 'Done', description: 'Completed' }
];

const priorityOptions = [
    { value: 'low', label: 'Low', description: 'Low priority' },
    { value: 'medium', label: 'Medium', description: 'Normal priority' },
    { value: 'high', label: 'High', description: 'High priority' }
];

const timePresets = [0.5, 1, 2, 4, 8, 16, 24];
const commonTags = ['urgent', 'bug', 'feature', 'improvement', 'design', 'backend', 'frontend', 'testing'];
const storyPointsOptions = [1, 2, 3, 5, 8, 13, 21];

// Computed
const descriptionCount = computed(() => form.value.description?.length || 0);
const minStartDate = computed(() => new Date().toISOString().split('T')[0]);

// Chargement initial
onMounted(async () => {
    await loadProjects();
});

// Watch project changes
watch(() => form.value.project_id, async (newProjectId) => {
    if (newProjectId) {
        await loadTeamMembers(newProjectId);
        await loadParentTasks(newProjectId);
    } else {
        teamMembers.value = [];
        availableParentTasks.value = [];
    }
});

// Methods
const loadProjects = async () => {
    try {
        // Utilisez le service projectsTeamsService pour récupérer les projets
        const response = await projectsTeamsService.getProjects({
            per_page: 100 // Récupérer tous les projets
        });

        // La réponse devrait contenir un tableau data
        if (response && response.data) {
            projects.value = response.data.map((project: any) => ({
                id: project.id,
                name: project.name,
                team_id: project.team_id || project.team?.id
            }));
        }

        // Si pas de données, utiliser les données simulées
        if (projects.value.length === 0) {
            console.warn('No projects found, using sample data');
            projects.value = [
                { id: 2, name: 'Mobile App Development', team_id: 2 },
                { id: 1, name: 'Website Redesign', team_id: 1 },
                { id: 3, name: 'API Integration', team_id: 3 },
                { id: 4, name: 'Marketing Campaign', team_id: 4 }
            ];
        }

        console.log('Projects loaded:', projects.value);

    } catch (error) {
        console.error('Failed to load projects:', error);
        showError('Failed to load projects. Please try again.');

        // Utiliser les données simulées en cas d'erreur
        projects.value = [
            { id: 2, name: 'Mobile App Development', team_id: 2 },
            { id: 1, name: 'Website Redesign', team_id: 1 },
            { id: 3, name: 'API Integration', team_id: 3 },
            { id: 4, name: 'Marketing Campaign', team_id: 4 }
        ];
    }
};

// Dans TaskCreateModal.vue, modifiez la méthode loadTeamMembers :
const loadTeamMembers = async (projectId: number) => {
    try {
        teamMembersLoading.value = true;
        errors.value.assigned_to = '';

        // Utilisez le service taskService au lieu de l'appel direct
        const members = await taskService.getTeamMembers(projectId);

        // Formatage des données si nécessaire
        teamMembers.value = members.map(member => ({
            id: member.id,
            name: member.name,
            email: member.email,
            role: member.role || 'member',
            avatar: member.avatar,
            avatar_url: member.avatar_url,
            initials: member.initials
        }));

        // Log pour debug
        console.log('Team members loaded:', teamMembers.value);

    } catch (error: any) {
        console.error('Failed to load team members:', error);
        teamMembers.value = [];
        errors.value.assigned_to = 'Failed to load team members';

        // Afficher un message à l'utilisateur
        if (error.response?.data?.message) {
            showError(`Failed to load team members: ${error.response.data.message}`);
        }
    } finally {
        teamMembersLoading.value = false;
    }
};

const loadParentTasks = async (projectId: number) => {
    try {
        const response = await taskService.getTasksByProject(projectId, {
            per_page: 50,
            status: 'backlog,todo,doing' // Exclure les tâches terminées
        });
        availableParentTasks.value = response.data;
    } catch (error) {
        console.error('Failed to load parent tasks:', error);
        availableParentTasks.value = [];
    }
};

const createTask = async () => {
    // Validation
    if (!validateForm()) {
        return;
    }

    try {
        loading.value = true;
        errors.value = {};

        // Préparer les données
        const taskData: CreateTaskData = {
            title: form.value.title.trim(),
            project_id: form.value.project_id!,
            description: form.value.description?.trim() || undefined,
            assigned_to: form.value.assigned_to || undefined,
            status: form.value.status,
            priority: form.value.priority,
            start_date: form.value.start_date || undefined,
            due_date: form.value.due_date || undefined,
            estimated_time: form.value.estimated_time || undefined,
            tags: form.value.tags.length > 0 ? form.value.tags : undefined
        };

        // Créer la tâche
        const taskStore = useTaskStore();
        const newTask = await taskStore.createTask(taskData);

        // Émettre l'événement
        emit('created', newTask);

        // Fermer le modal
        emit('close');

        // Reset form
        resetForm();

    } catch (error: any) {
        console.error('Failed to create task:', error);

        // Gérer les erreurs d'API
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            showError('Failed to create task. Please try again.');
        }
    } finally {
        loading.value = false;
    }
};

const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    // Title validation
    if (!form.value.title.trim()) {
        newErrors.title = 'Title is required';
    } else if (form.value.title.trim().length > 255) {
        newErrors.title = 'Title must be less than 255 characters';
    }

    // Project validation
    if (!form.value.project_id) {
        newErrors.project_id = 'Project is required';
    }

    // Description validation
    if (form.value.description && form.value.description.length > 1000) {
        newErrors.description = 'Description must be less than 1000 characters';
    }

    // Date validation
    if (form.value.start_date && form.value.due_date) {
        const startDate = new Date(form.value.start_date);
        const dueDate = new Date(form.value.due_date);

        if (dueDate < startDate) {
            newErrors.due_date = 'Due date must be after start date';
        }
    }

    // Estimated time validation
    if (form.value.estimated_time !== undefined && form.value.estimated_time < 0) {
        newErrors.estimated_time = 'Estimated time cannot be negative';
    }

    // SUPPRIMEZ cette section problématique :
    // if (form.value.project_id && !teamMembersLoading.value) {
    //     const currentProject = projects.value.find(p => p.id === form.value.project_id);
    //     if (currentProject && currentProject.team_id === 1) {
    //         newErrors.project_id = 'This project has limited team members.';
    //     }
        
    //     if (teamMembers.value.length === 0) {
    //         console.warn('Project has no team members available');
    //     }
    // }

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
};
const clearError = (field: string) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

const onProjectChange = () => {
    // Réinitialiser l'assignation lorsque le projet change
    form.value.assigned_to = undefined;
    clearError('assigned_to');
};

const addTag = () => {
    const tag = newTag.value.trim();
    if (tag && !form.value.tags?.includes(tag)) {
        if (!form.value.tags) {
            form.value.tags = [];
        }
        form.value.tags.push(tag);
        newTag.value = '';
    }
};

const addCommonTag = (tag: string) => {
    if (!form.value.tags?.includes(tag)) {
        if (!form.value.tags) {
            form.value.tags = [];
        }
        form.value.tags.push(tag);
    }
};

const removeTag = (index: number) => {
    if (form.value.tags) {
        form.value.tags.splice(index, 1);
    }
};

const clearDate = (field: 'start_date' | 'due_date') => {
    form.value[field] = '';
};

const updateDescriptionCount = () => {
    if (descriptionCount.value > 1000) {
        errors.value.description = 'Description must be less than 1000 characters';
    } else {
        clearError('description');
    }
};

const resetForm = () => {
    form.value = {
        title: '',
        project_id: undefined,
        description: '',
        assigned_to: undefined,
        status: 'todo',
        priority: 'medium',
        start_date: '',
        due_date: '',
        estimated_time: undefined,
        tags: [],
        parent_task_id: null,
        story_points: undefined,
        is_confidential: false,
        send_notifications: true
    };
    newTag.value = '';
    errors.value = {};
    showAdvanced.value = false;
};

const closeModal = (e: MouseEvent) => {
    if ((e.target as HTMLElement).classList.contains('modal-overlay')) {
        emit('close');
    }
};

const showError = (message: string) => {
    // Vous pourriez utiliser un système de notifications ici
    alert(message);
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
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 32px;
    border-bottom: 1px solid #e8e9eb;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius: 12px 12px 0 0;
}

.modal-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 24px;
    font-weight: 600;
}

.btn-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #95a5a6;
    cursor: pointer;
    padding: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.btn-close:hover {
    background: #f8f9fa;
    color: #e74c3c;
}

.modal-form {
    padding: 32px;
}

.form-group {
    margin-bottom: 24px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #34495e;
    font-size: 14px;
}

label.required::after {
    content: ' *';
    color: #e74c3c;
}

input[type="text"],
input[type="number"],
input[type="date"],
select,
textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e8e9eb;
    border-radius: 8px;
    font-size: 14px;
    color: #2c3e50;
    transition: all 0.2s;
    background: white;
}

input[type="text"]:focus,
input[type="number"]:focus,
input[type="date"]:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

input.error,
select.error {
    border-color: #e74c3c;
}

input.error:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.error-message {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 4px;
}

.select-wrapper {
    position: relative;
}

.select-arrow {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
    pointer-events: none;
}

.textarea-wrapper {
    position: relative;
}

.char-count {
    position: absolute;
    bottom: 8px;
    right: 8px;
    font-size: 12px;
    color: #95a5a6;
    background: rgba(255, 255, 255, 0.9);
    padding: 2px 6px;
    border-radius: 4px;
}

.char-count.limit {
    color: #e74c3c;
    font-weight: 600;
}

.hint {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 4px;
}

.assignee-selector {
    position: relative;
}

.loading-indicator {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #7f8c8d;
}

/* Status and Priority Buttons */
.status-buttons,
.priority-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.status-btn,
.priority-btn {
    flex: 1;
    min-width: 80px;
    padding: 10px 16px;
    border: 2px solid #e8e9eb;
    border-radius: 8px;
    background: white;
    color: #2c3e50;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.status-btn:hover,
.priority-btn:hover {
    border-color: #bdc3c7;
    transform: translateY(-1px);
}

.status-btn.active,
.priority-btn.active {
    border-color: transparent;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status colors */
.status-backlog.active {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
}

.status-todo.active {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.status-doing.active {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.status-done.active {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

/* Priority colors */
.priority-low.active {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.priority-medium.active {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.priority-high.active {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

/* Date Input */
.date-input {
    position: relative;
}

.btn-clear-date {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #95a5a6;
    cursor: pointer;
    font-size: 20px;
    padding: 0 8px;
    line-height: 1;
}

.btn-clear-date:hover {
    color: #e74c3c;
}

/* Time Input */
.time-input-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.time-input-group input {
    width: 120px;
}

.time-unit {
    color: #7f8c8d;
    font-size: 14px;
    white-space: nowrap;
}

.time-presets {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.time-preset-btn {
    padding: 6px 12px;
    border: 1px solid #e8e9eb;
    border-radius: 6px;
    background: white;
    color: #2c3e50;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.time-preset-btn:hover {
    background: #f8f9fa;
    border-color: #3498db;
}

/* Tags */
.tags-input {
    margin-top: 8px;
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding: 12px;
    border: 2px solid #e8e9eb;
    border-radius: 8px;
    min-height: 52px;
    background: white;
}

.tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 6px 12px 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    animation: tagAppear 0.3s ease-out;
}

@keyframes tagAppear {
    from {
        opacity: 0;
        transform: scale(0.8);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

.tag-remove {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 16px;
    padding: 0 4px;
    line-height: 1;
    opacity: 0.8;
}

.tag-remove:hover {
    opacity: 1;
    transform: scale(1.2);
}

.tag-input {
    flex: 1;
    min-width: 120px;
    border: none !important;
    padding: 8px 4px !important;
    background: transparent !important;
}

.tag-input:focus {
    box-shadow: none !important;
}

.tags-hint {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
    font-size: 12px;
    color: #7f8c8d;
}

.common-tag-btn {
    padding: 4px 8px;
    border: 1px solid #e8e9eb;
    border-radius: 4px;
    background: white;
    color: #2c3e50;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
}

.common-tag-btn:hover {
    background: #f8f9fa;
    border-color: #3498db;
}

/* Advanced Section */
.advanced-section {
    border-top: 1px solid #e8e9eb;
    padding-top: 24px;
    margin-top: 32px;
}

.btn-toggle-advanced {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: none;
    color: #3498db;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    transition: color 0.2s;
}

.btn-toggle-advanced:hover {
    color: #2980b9;
}

.toggle-icon {
    font-size: 10px;
    transition: transform 0.2s;
}

.advanced-options {
    margin-top: 16px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Story Points */
.story-points {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.story-point-btn {
    width: 36px;
    height: 36px;
    border: 2px solid #e8e9eb;
    border-radius: 50%;
    background: white;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.story-point-btn:hover {
    border-color: #3498db;
    transform: scale(1.1);
}

.story-point-btn.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

/* Checkbox */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.checkbox {
    display: none;
}

.checkbox-custom {
    width: 18px;
    height: 18px;
    border: 2px solid #e8e9eb;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.checkbox:checked+.checkbox-custom {
    background: #3498db;
    border-color: #3498db;
}

.checkbox:checked+.checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e8e9eb;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f8f9fa;
    color: #2c3e50;
}

.btn-secondary:hover:not(:disabled) {
    background: #e8e9eb;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #2980b9, #1c6ea4);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
}

.loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-height: 95vh;
        margin: 20px;
    }

    .modal-header {
        padding: 20px;
    }

    .modal-form {
        padding: 20px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .status-buttons,
    .priority-buttons {
        flex-direction: column;
    }

    .status-btn,
    .priority-btn {
        min-width: 100%;
    }

    .time-input-group {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .time-presets {
        justify-content: center;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        min-width: 100%;
    }
}

@media (max-width: 480px) {
    .modal-header h2 {
        font-size: 20px;
    }

    .form-group {
        margin-bottom: 16px;
    }
}
</style>