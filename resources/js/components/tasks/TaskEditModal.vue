<!-- src/views/tasks/EditModal.vue -->
<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-dark/50 backdrop-blur-sm"
        @click.self="$emit('close')">
        <div class="relative bg-white rounded-2xl shadow-hard w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in"
            @click.stop>
            <!-- En-tête -->
            <div
                class="sticky top-0 z-10 flex items-center justify-between p-6 border-b border-gray-100 bg-slate-custom rounded-t-2xl">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-dark">Modifier la tâche</h2>
                        <p class="text-xs text-dark-light">#{{ task.id }} - {{ task.title }}</p>
                    </div>
                </div>
                <button @click="$emit('close')"
                    class="p-2 text-dark-light hover:text-dark hover:bg-gray-100 rounded-xl transition-colors"
                    aria-label="Fermer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="saveChanges" class="p-6 space-y-6">
                <!-- Titre -->
                <div>
                    <label for="title"
                        class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                        <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        Titre *
                    </label>
                    <input id="title" v-model="form.title" type="text" required
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-dark-light"
                        placeholder="Entrez le titre de la tâche" />
                </div>

                <!-- Description -->
                <div>
                    <label for="description"
                        class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                        <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Description
                    </label>
                    <textarea id="description" v-model="form.description"
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-dark-light resize-none"
                        placeholder="Décrivez la tâche en détail..." rows="3"></textarea>
                </div>

                <!-- Grille de champs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Projet -->
                    <div>
                        <label for="project_id"
                            class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Projet *
                        </label>
                        <div class="relative">
                            <select id="project_id" v-model="form.project_id" required
                                class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none bg-white pr-10">
                                <option value="" disabled>Sélectionner un projet</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">
                                    {{ project.name }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-dark-light" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Assigné à -->
                    <div>
                        <label for="assigned_to"
                            class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Assigner à
                        </label>
                        <div class="relative">
                            <select id="assigned_to" v-model="form.assigned_to"
                                class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none bg-white pr-10">
                                <option :value="null">Non assigné</option>
                                <option v-for="user in assignableUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-dark-light" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Statut -->
                    <div>
                        <label for="status"
                            class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Statut
                        </label>
                        <div class="relative">
                            <select id="status" v-model="form.status"
                                class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none bg-white pr-10">
                                <option value="backlog">Backlog</option>
                                <option value="todo">À faire</option>
                                <option value="doing">En cours</option>
                                <option value="done">Terminée</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-dark-light" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Priorité -->
                    <div>
                        <label for="priority"
                            class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                            Priorité
                        </label>
                        <div class="relative">
                            <select id="priority" v-model="form.priority"
                                class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none bg-white pr-10">
                                <option value="low">Basse</option>
                                <option value="medium">Moyenne</option>
                                <option value="high">Haute</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-dark-light" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Date de début -->
                    <div>
                        <label for="start_date"
                            class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Date de début
                        </label>
                        <input id="start_date" v-model="form.start_date" type="date"
                            class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- Date d'échéance -->
                    <div>
                        <label for="due_date"
                            class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Date d'échéance
                        </label>
                        <input id="due_date" v-model="form.due_date" type="date"
                            class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>
                </div>

                <!-- Temps estimé -->
                <div>
                    <label for="estimated_time"
                        class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                        <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Temps estimé (heures)
                    </label>
                    <div class="relative">
                        <input id="estimated_time" v-model.number="form.estimated_time" type="number" min="0" step="0.5"
                            class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all pr-16"
                            placeholder="0" />
                        <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-sm text-dark-light">
                            heures
                        </span>
                    </div>
                </div>

                <!-- Étiquettes -->
                <div>
                    <label for="tags"
                        class="flex items-center mb-2 text-xs font-semibold text-dark uppercase tracking-wider">
                        <svg class="w-4 h-4 mr-2 text-dark-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Étiquettes
                    </label>
                    <input id="tags" v-model="tagsInput" type="text"
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-dark-light"
                        placeholder="design, frontend, urgent" @input="updateTags" />

                    <!-- Étiquettes prévisualisées -->
                    <div v-if="form.tags && form.tags.length > 0" class="flex flex-wrap gap-2 mt-3">
                        <span v-for="(tag, index) in form.tags" :key="index"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full bg-secondary/10 text-secondary border border-secondary/20">
                            {{ tag }}
                            <button type="button" @click="removeTag(index)"
                                class="ml-2 text-secondary hover:text-secondary-dark transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100">
                    <button type="button" @click="$emit('close')"
                        class="px-6 py-3 text-sm font-semibold text-dark bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        Annuler
                    </button>
                    <button type="submit" :disabled="loading"
                        class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-primary to-secondary hover:from-primary-dark hover:to-secondary-dark rounded-xl shadow-medium hover:shadow-hard transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center min-w-[200px]">
                        <svg v-if="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        {{ loading ? 'Enregistrement...' : 'Enregistrer les modifications' }}
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
    tags: props.task.tags || [],
    project_id: props.task.project_id
});

const projects = ref<any[]>([]);
const assignableUsers = ref<any[]>([]);
const loading = ref(false);
const tagsInput = ref((props.task.tags || []).join(', '));

const updateTags = () => {
    if (tagsInput.value.trim()) {
        form.value.tags = tagsInput.value
            .split(',')
            .map(tag => tag.trim())
            .filter(tag => tag.length > 0);
    } else {
        form.value.tags = [];
    }
};

const removeTag = (index: number) => {
    if (form.value.tags) {
        form.value.tags.splice(index, 1);
        tagsInput.value = form.value.tags.join(', ');
    }
};

onMounted(async () => {
    await loadAssignableUsers();
    await loadProjects();
});

watch(() => props.task.project_id, async () => {
    await loadAssignableUsers();
});

const loadAssignableUsers = async () => {
    try {
        // Remplacer par votre appel API
        assignableUsers.value = [
            { id: 1, name: 'John Doe' },
            { id: 2, name: 'Jane Smith' },
            { id: 3, name: 'Robert Johnson' }
        ];
    } catch (err) {
        console.error('Erreur lors du chargement des utilisateurs:', err);
    }
};

const loadProjects = async () => {
    try {
        // Remplacer par votre appel API
        projects.value = [
            { id: 1, name: 'Projet Alpha' },
            { id: 2, name: 'Projet Beta' },
            { id: 3, name: 'Projet Gamma' }
        ];
    } catch (err) {
        console.error('Erreur lors du chargement des projets:', err);
    }
};

const saveChanges = async () => {
    try {
        loading.value = true;

        const dataToSend = {
            ...form.value,
            assigned_to: form.value.assigned_to || null,
            estimated_time: form.value.estimated_time || 0
        };

        const updatedTask = await taskService.updateTask(props.task.id, dataToSend);
        emit('saved', updatedTask);
    } catch (err) {
        console.error('Échec de la mise à jour:', err);
        alert('Échec de la mise à jour de la tâche. Veuillez réessayer.');
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
/* Styles spécifiques au composant */
:deep(.bg-dark\/30) {
    background-color: rgba(71, 70, 101, 0.3);
}

:deep(.bg-dark\/80) {
    background-color: rgba(71, 70, 101, 0.8);
}

:deep(.from-primary) {
    --tw-gradient-from: #ab2283 var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(171 34 131 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

:deep(.to-primary-dark) {
    --tw-gradient-to: #8a1c6a var(--tw-gradient-to-position);
}

:deep(.from-primary-dark) {
    --tw-gradient-from: #8a1c6a var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(138 28 106 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

:deep(.to-secondary-dark) {
    --tw-gradient-to: #289396 var(--tw-gradient-to-position);
}

:deep(.to-secondary) {
    --tw-gradient-to: #31b6b8 var(--tw-gradient-to-position);
}

:deep(.bg-primary) {
    background-color: #ab2283;
}

:deep(.bg-secondary) {
    background-color: #31b6b8;
}

:deep(.text-primary) {
    color: #ab2283;
}

:deep(.text-secondary) {
    color: #31b6b8;
}

:deep(.text-dark) {
    color: #474665;
}

:deep(.text-dark-light) {
    color: #5c5a7a;
}

:deep(.bg-slate-custom) {
    background-color: #f8fafc;
}

:deep(.border-primary) {
    border-color: #ab2283;
}

:deep(.border-secondary) {
    border-color: #31b6b8;
}

:deep(.focus\:ring-primary\/20:focus) {
    --tw-ring-color: rgb(171 34 131 / 0.2);
}

:deep(.bg-secondary\/10) {
    background-color: rgb(49 182 184 / 0.1);
}

:deep(.border-secondary\/20) {
    border-color: rgb(49 182 184 / 0.2);
}

:deep(.hover\:text-secondary-dark:hover) {
    color: #289396;
}
</style>