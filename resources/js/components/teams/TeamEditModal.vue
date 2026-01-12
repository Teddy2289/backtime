<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-medium text-gray-900">
                                Modifier l'équipe
                            </h3>

                            <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">
                                        Nom de l'équipe *
                                    </label>
                                    <input v-model="form.name" type="text" id="name" required
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" />
                                    <p v-if="errors.name" class="mt-1 text-xs text-red-600">
                                        {{ errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        Description
                                    </label>
                                    <textarea v-model="form.description" id="description" rows="3"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input v-model="form.is_public" type="checkbox"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                                        <span class="ml-2 text-sm text-gray-700">
                                            Équipe publique
                                        </span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Les équipes publiques sont visibles par tous les utilisateurs
                                    </p>
                                </div>

                                <!-- Messages d'erreur/succès -->
                                <div v-if="error" class="bg-red-50 border border-red-200 rounded p-3">
                                    <p class="text-sm text-red-700">{{ error }}</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="handleSubmit" :disabled="isSubmitting" :class="[
                        'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm',
                        isSubmitting ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'
                    ]">
                        <span v-if="isSubmitting">Enregistrement...</span>
                        <span v-else>Enregistrer</span>
                    </button>
                    <button type="button" @click="$emit('close')" :disabled="isSubmitting"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useTeamStore } from '@/stores/team.store'
import type { Team } from '@/services/team.service'

const props = defineProps<{
    team: Team
}>()

const emit = defineEmits<{
    close: []
    updated: []
}>()

const teamStore = useTeamStore()

// Initialiser le formulaire avec les données de l'équipe
const form = ref({
    name: props.team.name,
    description: props.team.description || '',
    is_public: props.team.is_public
})

const errors = ref<Record<string, string>>({})
const error = ref('')
const isSubmitting = ref(false)

// Mettre à jour le formulaire si l'équipe change
watch(() => props.team, (newTeam) => {
    form.value = {
        name: newTeam.name,
        description: newTeam.description || '',
        is_public: newTeam.is_public
    }
}, { immediate: true })

const handleSubmit = async () => {
    errors.value = {}
    error.value = ''
    isSubmitting.value = true

    try {
        await teamStore.updateTeam(props.team.id, form.value)
        emit('updated')
        emit('close')
    } catch (err: any) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors
        } else {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour'
        }
    } finally {
        isSubmitting.value = false
    }
}
</script>