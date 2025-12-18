<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="submitForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt:0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    {{ isEditMode ? 'Modifier le projet' : 'Créer un nouveau projet' }}
                                </h3>

                                <!-- Error Message -->
                                <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                    <p class="text-sm text-red-600">{{ error }}</p>
                                </div>

                                <!-- Loading State for Teams -->
                                <div v-if="teamsLoading" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p class="text-sm text-blue-600">Chargement des équipes...</p>
                                </div>

                                <!-- Error State for Teams -->
                                <div v-if="teamsError && !teamsLoading"
                                    class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                    <p class="text-sm text-red-600">{{ teamsError }}</p>
                                </div>

                                <!-- Form Fields -->
                                <div class="space-y-4">
                                    <!-- Team -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Équipe *
                                        </label>
                                        <select v-model="formData.team_id" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="isEditMode || teamsLoading">
                                            <option value="">Sélectionner une équipe</option>
                                            <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                                                {{ team.name }}
                                            </option>
                                        </select>
                                        <p v-if="!availableTeams.length && !teamsLoading && !teamsError"
                                            class="mt-1 text-sm text-gray-500">
                                            Aucune équipe disponible. Créez d'abord une équipe.
                                        </p>
                                    </div>

                                    <!-- Project Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Nom du projet *
                                        </label>
                                        <input v-model="formData.name" type="text" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Entrez le nom du projet" />
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Description
                                        </label>
                                        <textarea v-model="formData.description" rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Description du projet..."></textarea>
                                    </div>

                                    <!-- Dates -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Date de début
                                            </label>
                                            <input v-model="formData.start_date" type="date"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Date de fin
                                            </label>
                                            <input v-model="formData.end_date" type="date" :min="formData.start_date"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Statut
                                        </label>
                                        <select v-model="formData.status"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option v-for="status in statusOptions" :key="status.value"
                                                :value="status.value">
                                                {{ status.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="loading || teamsLoading"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="loading">Enregistrement...</span>
                            <span v-else>{{ isEditMode ? 'Modifier' : 'Créer' }}</span>
                        </button>
                        <button type="button" @click="closeModal" :disabled="loading"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useProjectTeamStore } from '@/stores/projectsTeams.store';
import { useTeamStore } from '@/stores/team.store';
import type { ProjectTeam, CreateProjectTeamData, UpdateProjectTeamData } from '@/types/projectsTeams';

const props = defineProps<{
    show: boolean;
    project: ProjectTeam | null;
}>();

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

// Stores
const projectStore = useProjectTeamStore();
const teamStore = useTeamStore();

// State
const loading = ref(false);
const error = ref<string | null>(null);
const teamsLoading = ref(false);
const teamsError = ref<string | null>(null);

// Form data
const formData = ref<CreateProjectTeamData | UpdateProjectTeamData>({
    team_id: 0,
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    status: 'active',
});

const statusOptions = [
    { value: 'active', label: 'Actif' },
    { value: 'completed', label: 'Terminé' },
    { value: 'on_hold', label: 'En attente' },
    { value: 'cancelled', label: 'Annulé' },
];

// Computed
const isEditMode = computed(() => !!props.project);
const availableTeams = computed(() => teamStore.teams || []);
console.log('📦 Équipes disponibles:', availableTeams.value);
// Methods
const fetchTeams = async () => {
    if (availableTeams.value.length > 0 && !teamsLoading.value) {
        return; // Équipes déjà chargées
    }

    try {
        teamsLoading.value = true;
        teamsError.value = null;
        console.log('🔄 Chargement des équipes...');

        await teamStore.fetchTeams({ per_page: 100 });

        console.log('✅ Équipes chargées:', availableTeams.value);
    } catch (err: any) {
        console.error('❌ Erreur lors du chargement des équipes:', err);
        teamsError.value = err.response?.data?.message || err.message || 'Erreur lors du chargement des équipes';
    } finally {
        teamsLoading.value = false;
    }
};

const resetForm = () => {
    formData.value = {
        team_id: availableTeams.value.length > 0 ? Number(availableTeams.value[0].id) : 0,
        name: '',
        description: '',
        start_date: '',
        end_date: '',
        status: 'active',
    };
    error.value = null;
};

const submitForm = async () => {
    try {
        // Validation
        if (formData.value.team_id === 0 || !formData.value.name.trim()) {
            error.value = 'Veuillez remplir tous les champs obligatoires';
            return;
        }

        loading.value = true;
        error.value = null;

        // S'assurer que team_id est un nombre
        const formDataToSend = {
            ...formData.value,
            team_id: Number(formData.value.team_id)
        };

        console.log('📤 Données envoyées:', formDataToSend);

        if (isEditMode.value && props.project) {
            await projectStore.updateProject(props.project.id, formDataToSend);
        } else {
            await projectStore.createProject(formDataToSend as CreateProjectTeamData);
        }

        emit('saved');
        closeModal();
    } catch (err: any) {
        console.error('❌ Erreur lors de la soumission du formulaire:', err);
        error.value = err.response?.data?.message || err.message || 'Une erreur est survenue';
    } finally {
        loading.value = false;
    }
};

const closeModal = () => {
    resetForm();
    emit('close');
};

// Watch for project changes
watch(
    () => props.project,
    (project) => {
        if (project) {
            console.log('🔄 Chargement des données du projet:', project);
            formData.value = {
                team_id: project.team_id ? Number(project.team_id) : 0,
                name: project.name,
                description: project.description || '',
                start_date: project.start_date || '',
                end_date: project.end_date || '',
                status: project.status,
            };
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// Watch for modal show/hide
watch(
    () => props.show,
    (show) => {
        if (show) {
            // Charger les équipes quand la modal s'ouvre
            fetchTeams();
        } else {
            resetForm();
        }
    }
);

// Charger les équipes au montage du composant
onMounted(() => {
    if (props.show) {
        fetchTeams();
    }
});
</script>