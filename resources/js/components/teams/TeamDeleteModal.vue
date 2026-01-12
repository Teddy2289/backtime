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
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-medium text-gray-900">
                                Supprimer l'équipe
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Êtes-vous sûr de vouloir supprimer l'équipe
                                    <span class="font-semibold text-gray-900">"{{ team.name }}"</span> ?
                                </p>
                                <p class="mt-2 text-sm text-gray-500">
                                    Cette action est irréversible. Toutes les données associées à cette équipe seront
                                    définitivement supprimées.
                                </p>

                                <!-- Avertissements -->
                                <div v-if="team.members_count && team.members_count > 0"
                                    class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                    <p class="text-sm text-yellow-700">
                                        ⚠️ Cette équipe contient {{ team.members_count }} membre(s). Ils seront retirés
                                        de l'équipe.
                                    </p>
                                </div>
                            </div>

                            <!-- Confirmation avec saisie -->
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 mb-2">
                                    Tapez le nom de l'équipe pour confirmer :
                                </p>
                                <input v-model="confirmationText" type="text" :placeholder="team.name"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-red-500 focus:border-red-500 text-sm"
                                    @keyup="validateConfirmation" />
                            </div>

                            <!-- Messages d'erreur -->
                            <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded p-3">
                                <p class="text-sm text-red-700">{{ error }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="handleDelete" :disabled="!isConfirmed || isSubmitting" :class="[
                        'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm',
                        !isConfirmed || isSubmitting
                            ? 'bg-red-300 cursor-not-allowed'
                            : 'bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500'
                    ]">
                        <span v-if="isSubmitting">Suppression...</span>
                        <span v-else>Supprimer définitivement</span>
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
import { ref, computed } from 'vue'
import { useTeamStore } from '@/stores/team.store'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import type { Team } from '@/services/team.service'

const props = defineProps<{
    team: Team
}>()

const emit = defineEmits<{
    close: []
    deleted: []
}>()

const teamStore = useTeamStore()

const confirmationText = ref('')
const error = ref('')
const isSubmitting = ref(false)

const isConfirmed = computed(() => {
    return confirmationText.value === props.team.name
})

const validateConfirmation = () => {
    if (!isConfirmed.value) {
        error.value = 'Le nom saisi ne correspond pas au nom de l\'équipe'
    } else {
        error.value = ''
    }
}

const handleDelete = async () => {
    if (!isConfirmed.value) return

    error.value = ''
    isSubmitting.value = true

    try {
        await teamStore.deleteTeam(props.team.id)
        emit('deleted')
        emit('close')
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Erreur lors de la suppression'
    } finally {
        isSubmitting.value = false
    }
}
</script>