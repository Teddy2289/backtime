<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h2>
                    {{
                        isEditMode
                            ? "Modifier le Projet"
                            : "Créer un Nouveau Projet"
                    }}
                </h2>
                <button @click="$emit('close')" class="btn-close">×</button>
            </div>

            <form @submit.prevent="submitForm" class="modal-form">
                <!-- Message d'Erreur -->
                <div v-if="error" class="error-banner">
                    <div class="error-content">
                        <svg
                            class="error-icon"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>{{ error }}</span>
                    </div>
                </div>

                <!-- Sélection de l'Équipe -->
                <div class="form-group">
                    <label for="team_id" class="required">Équipe</label>
                    <div class="select-wrapper">
                        <select
                            id="team_id"
                            v-model="formData.team_id"
                            required
                            :disabled="isEditMode || teamsLoading"
                            :class="{
                                error: errors.team_id,
                                disabled: isEditMode || teamsLoading,
                            }"
                        >
                            <option value="">Sélectionner une équipe</option>
                            <option
                                v-for="team in availableTeams"
                                :key="team.id"
                                :value="team.id"
                            >
                                {{ team.name }}
                            </option>
                        </select>
                        <div class="select-arrow">▼</div>
                    </div>
                    <div v-if="teamsLoading" class="loading-indicator">
                        Chargement des équipes...
                    </div>
                    <div v-if="teamsError" class="error-message">
                        {{ teamsError }}
                    </div>
                    <div
                        v-if="
                            !availableTeams.length &&
                            !teamsLoading &&
                            !teamsError
                        "
                        class="hint"
                    >
                        Aucune équipe disponible. Créez d'abord une équipe.
                    </div>
                    <div v-if="errors.team_id" class="error-message">
                        {{ errors.team_id }}
                    </div>
                </div>

                <!-- Nom du Projet -->
                <div class="form-group">
                    <label for="name" class="required">Nom du Projet</label>
                    <input
                        id="name"
                        v-model="formData.name"
                        type="text"
                        placeholder="Entrez le nom du projet"
                        required
                        :class="{ error: errors.name }"
                        @input="clearError('name')"
                    />
                    <div v-if="errors.name" class="error-message">
                        {{ errors.name }}
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <div class="textarea-wrapper">
                        <textarea
                            id="description"
                            v-model="formData.description"
                            placeholder="Décrivez les détails du projet..."
                            rows="4"
                            @input="updateDescriptionCount"
                        ></textarea>
                        <div
                            class="char-count"
                            :class="{ limit: descriptionCount > 1000 }"
                        >
                            {{ descriptionCount }}/1000
                        </div>
                    </div>
                    <div class="hint">
                        Le Markdown est pris en charge. Utilisez **gras**,
                        *italique* et `code`.
                    </div>
                </div>

                <!-- Dates -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Date de Début</label>
                        <div class="date-input">
                            <input
                                id="start_date"
                                v-model="formData.start_date"
                                type="date"
                                :min="minStartDate"
                                :max="formData.end_date"
                            />
                            <button
                                v-if="formData.start_date"
                                @click="clearDate('start_date')"
                                type="button"
                                class="btn-clear-date"
                                title="Effacer la date"
                            >
                                ×
                            </button>
                        </div>
                        <div v-if="errors.start_date" class="error-message">
                            {{ errors.start_date }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Date de Fin</label>
                        <div class="date-input">
                            <input
                                id="end_date"
                                v-model="formData.end_date"
                                type="date"
                                :min="formData.start_date || minStartDate"
                            />
                            <button
                                v-if="formData.end_date"
                                @click="clearDate('end_date')"
                                type="button"
                                class="btn-clear-date"
                                title="Effacer la date"
                            >
                                ×
                            </button>
                        </div>
                        <div v-if="errors.end_date" class="error-message">
                            {{ errors.end_date }}
                        </div>
                    </div>
                </div>

                <!-- Statut -->
                <div class="form-group">
                    <label for="status">Statut</label>
                    <div class="status-buttons">
                        <button
                            v-for="status in statusOptions"
                            :key="status.value"
                            type="button"
                            @click="formData.status = status.value"
                            :class="[
                                'status-btn',
                                `status-${status.value}`,
                                { active: formData.status === status.value },
                            ]"
                            :title="status.description"
                        >
                            {{ status.label }}
                        </button>
                    </div>
                    <div v-if="errors.status" class="error-message">
                        {{ errors.status }}
                    </div>
                </div>

                <!-- Options Avancées -->
                <div class="form-group advanced-section">
                    <button
                        type="button"
                        @click="showAdvanced = !showAdvanced"
                        class="btn-toggle-advanced"
                    >
                        <span class="toggle-icon">{{
                            showAdvanced ? "▼" : "▶"
                        }}</span>
                        Options Avancées
                    </button>

                    <div v-if="showAdvanced" class="advanced-options">
                        <!-- Public/Privé -->
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.is_public"
                                    type="checkbox"
                                    class="checkbox"
                                />
                                <span class="checkbox-custom"></span>
                                Projet Public
                            </label>
                            <div class="hint">
                                Rendre ce projet visible à tous les membres de
                                l'équipe
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.send_notifications"
                                    type="checkbox"
                                    class="checkbox"
                                />
                                <span class="checkbox-custom"></span>
                                Envoyer les Notifications
                            </label>
                            <div class="hint">
                                Notifier les membres de l'équipe des mises à
                                jour du projet
                            </div>
                        </div>

                        <!-- Modèle -->
                        <div class="form-group">
                            <label for="template_id">Utiliser un Modèle</label>
                            <select
                                id="template_id"
                                v-model="formData.template_id"
                            >
                                <option :value="null">
                                    Aucun (Projet Vide)
                                </option>
                                <option value="software">
                                    Développement Logiciel
                                </option>
                                <option value="marketing">
                                    Campagne Marketing
                                </option>
                                <option value="research">
                                    Projet de Recherche
                                </option>
                                <option value="event">
                                    Organisation d'Événement
                                </option>
                            </select>
                            <div class="hint">
                                Sélectionnez un modèle pour pré-remplir les
                                tâches et la structure
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="btn btn-secondary"
                        :disabled="loading"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="loading"
                    >
                        <span v-if="loading" class="loading-spinner"></span>
                        {{
                            loading
                                ? isEditMode
                                    ? "Enregistrement..."
                                    : "Création..."
                                : isEditMode
                                ? "Enregistrer les Modifications"
                                : "Créer le Projet"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useProjectTeamStore } from "@/stores/projectsTeams.store";
import { useTeamStore } from "@/stores/team.store";
import type {
    ProjectTeam,
    CreateProjectTeamData,
    UpdateProjectTeamData,
} from "@/types/projectsTeams";

const props = defineProps<{
    show: boolean;
    project: ProjectTeam | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved"): void;
}>();

// Stores
const projectStore = useProjectTeamStore();
const teamStore = useTeamStore();

// State
const loading = ref(false);
const teamsLoading = ref(false);
const showAdvanced = ref(false);

// Errors
const error = ref<string | null>(null);
const teamsError = ref<string | null>(null);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref<
    CreateProjectTeamData & {
        is_public?: boolean;
        send_notifications?: boolean;
        template_id?: string | null;
    }
>({
    team_id: 0,
    name: "",
    description: "",
    start_date: "",
    end_date: "",
    status: "active",
    is_public: false,
    send_notifications: true,
    template_id: null,
});

interface StatusOption {
    value: "active" | "completed" | "on_hold" | "cancelled" | undefined;
    label: string;
    description: string;
}

const statusOptions: StatusOption[] = [
    { value: "active", label: "Active", description: "This project is active" },
    {
        value: "completed",
        label: "Completed",
        description: "This project is completed",
    },
    {
        value: "on_hold",
        label: "On Hold",
        description: "This project is on hold",
    },
    {
        value: "cancelled",
        label: "Cancelled",
        description: "This project is cancelled",
    },
];
// Computed
const isEditMode = computed(() => !!props.project);
const availableTeams = computed(() => teamStore.teams || []);
const descriptionCount = computed(
    () => formData.value.description?.length || 0
);
const minStartDate = computed(() => new Date().toISOString().split("T")[0]);

// Methods
const fetchTeams = async () => {
    if (availableTeams.value.length > 0 && !teamsLoading.value) {
        return;
    }

    try {
        teamsLoading.value = true;
        teamsError.value = null;
        await teamStore.fetchTeams({ per_page: 100 });
    } catch (err: any) {
        console.error("Error loading teams:", err);
        teamsError.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to load teams";
    } finally {
        teamsLoading.value = false;
    }
};

const resetForm = () => {
    formData.value = {
        team_id:
            availableTeams.value.length > 0
                ? Number(availableTeams.value[0].id)
                : 0,
        name: "",
        description: "",
        start_date: "",
        end_date: "",
        status: "active",
        is_public: false,
        send_notifications: true,
        template_id: null,
    };
    error.value = null;
    errors.value = {};
    showAdvanced.value = false;
};

const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    // Team validation
    if (!formData.value.team_id || formData.value.team_id === 0) {
        newErrors.team_id = "Team is required";
    }

    // Name validation
    if (!formData.value.name.trim()) {
        newErrors.name = "Project name is required";
    } else if (formData.value.name.trim().length > 255) {
        newErrors.name = "Project name must be less than 255 characters";
    }

    // Description validation
    if (
        formData.value.description &&
        formData.value.description.length > 1000
    ) {
        newErrors.description = "Description must be less than 1000 characters";
    }

    // Date validation
    if (formData.value.start_date && formData.value.end_date) {
        const startDate = new Date(formData.value.start_date);
        const endDate = new Date(formData.value.end_date);

        if (endDate < startDate) {
            newErrors.end_date = "End date must be after start date";
        }
    }

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
};

const clearError = (field: string) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

const updateDescriptionCount = () => {
    if (descriptionCount.value > 1000) {
        errors.value.description =
            "Description must be less than 1000 characters";
    } else {
        clearError("description");
    }
};

const clearDate = (field: "start_date" | "end_date") => {
    formData.value[field] = "";
    clearError(field);
};

const submitForm = async () => {
    if (!validateForm()) {
        return;
    }

    try {
        loading.value = true;
        error.value = null;
        errors.value = {};

        // Prepare data
        const formDataToSend = {
            ...formData.value,
            team_id: Number(formData.value.team_id),
        };

        console.log("Submitting project data:", formDataToSend);

        if (isEditMode.value && props.project) {
            await projectStore.updateProject(
                props.project.id,
                formDataToSend as UpdateProjectTeamData
            );
        } else {
            await projectStore.createProject(
                formDataToSend as CreateProjectTeamData
            );
        }

        emit("saved");
        closeModal();
    } catch (err: any) {
        console.error("Error submitting form:", err);

        // Handle API errors
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else {
            error.value =
                err.response?.data?.message ||
                err.message ||
                "An error occurred";
        }
    } finally {
        loading.value = false;
    }
};

const closeModal = (e?: MouseEvent) => {
    if (!e || (e.target as HTMLElement).classList.contains("modal-overlay")) {
        resetForm();
        emit("close");
    }
};

// Watch for project changes
watch(
    () => props.project,
    (project) => {
        if (project) {
            formData.value = {
                team_id: project.team_id ? Number(project.team_id) : 0,
                name: project.name,
                description: project.description || "",
                start_date: project.start_date || "",
                end_date: project.end_date || "",
                status: project.status,
                is_public: project.is_public || false,
                send_notifications: true,
                template_id: null,
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
            fetchTeams();
        } else {
            resetForm();
        }
    }
);

// Load teams on component mount
onMounted(() => {
    if (props.show) {
        fetchTeams();
    }
});
</script>

<style scoped>
/* Reuse the same styles as TaskCreateModal */
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

.error-banner {
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 24px;
    animation: slideDown 0.3s ease-out;
}

.error-content {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #c0392b;
}

.error-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
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
    content: " *";
    color: #e74c3c;
}

input[type="text"],
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

input.error:focus,
select.error:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

select.disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
    opacity: 0.7;
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

.loading-indicator {
    margin-top: 4px;
    font-size: 12px;
    color: #3498db;
    display: flex;
    align-items: center;
    gap: 6px;
}

.loading-indicator::before {
    content: "";
    width: 12px;
    height: 12px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* Status Buttons */
.status-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.status-btn {
    flex: 1;
    min-width: 100px;
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

.status-btn:hover {
    border-color: #bdc3c7;
    transform: translateY(-1px);
}

.status-btn.active {
    border-color: transparent;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status colors */
.status-active.active {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.status-planned.active {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.status-on_hold.active {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.status-completed.active {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.status-cancelled.active {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
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

.checkbox:checked + .checkbox-custom {
    background: #3498db;
    border-color: #3498db;
}

.checkbox:checked + .checkbox-custom::after {
    content: "✓";
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

    .status-buttons {
        flex-direction: column;
    }

    .status-btn {
        min-width: 100%;
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
