<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div
            class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
        >
            <!-- Overlay -->
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="$emit('close')"
            ></div>

            <!-- Modal -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full"
                        >
                            <h3 class="text-lg font-medium text-gray-900">
                                Créer une nouvelle équipe
                            </h3>

                            <form
                                @submit.prevent="handleSubmit"
                                class="mt-4 space-y-4"
                            >
                                <div>
                                    <label
                                        for="name"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Nom de l'équipe *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        id="name"
                                        required
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                                    />
                                    <p
                                        v-if="errors.name"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="description"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        id="description"
                                        rows="3"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_public"
                                            type="checkbox"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <span
                                            class="ml-2 text-sm text-gray-700"
                                        >
                                            Rendre cette équipe publique
                                        </span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Les équipes publiques sont visibles par
                                        tous les utilisateurs
                                    </p>
                                </div>

                                <!-- Messages d'erreur/succès -->
                                <div
                                    v-if="error"
                                    class="bg-red-50 border border-red-200 rounded p-3"
                                >
                                    <p class="text-sm text-red-700">
                                        {{ error }}
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
                >
                    <button
                        v-if="canCreateTeam"
                        type="button"
                        @click="handleSubmit"
                        :disabled="isSubmitting"
                        :class="[
                            'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm',
                            isSubmitting
                                ? 'bg-blue-400 cursor-not-allowed'
                                : 'bg-blue-600 hover:bg-blue-700',
                        ]"
                    >
                        <span v-if="isSubmitting">Création...</span>
                        <span v-else>Créer l'équipe</span>
                    </button>
                    <div v-else class="w-full sm:w-auto">
                        <p class="text-sm text-red-600 p-2 bg-red-50 rounded">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Seuls les administrateurs et managers peuvent créer
                            des équipes
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        :disabled="isSubmitting"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useTeamStore } from '@/stores/team.store'
import { useAuthStore } from '@/stores/auth'

const emit = defineEmits<{
    close: []
    created: []
}>()

const teamStore = useTeamStore()
const authStore = useAuthStore()

// Vérifier les permissions
const canCreateTeam = computed(() => {
    return teamStore.canCreateTeam && !!authStore.user?.id
})

const form = ref({
    name: '',
    description: '',
    is_public: false
})

const errors = ref<Record<string, string>>({})
const error = ref('')
const isSubmitting = ref(false)

const handleSubmit = async () => {
    // Vérification supplémentaire
    if (!canCreateTeam.value) {
        error.value = "Seuls les administrateurs et managers peuvent créer des équipes"
        return
    }

    errors.value = {}
    error.value = ''
    isSubmitting.value = true

    try {
        // Le store s'occupera d'ajouter owner_id automatiquement
        await teamStore.createTeam(form.value)
        emit('created')
        emit('close')
    } catch (err: any) {
        console.error("Erreur création équipe:", err)
        
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors
        } else if (err.response?.data?.message) {
            error.value = err.response.data.message
        } else {
            error.value = err.message || 'Erreur lors de la création'
        }
    } finally {
        isSubmitting.value = false
    }
}
</script>
