<!-- components/teams/TeamDetailModal.vue -->
<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <!-- En-tête -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        Détails de l'équipe
                                    </h3>
                                    <div class="mt-1">
                                        <span v-if="team.is_public"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Public
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Privé
                                        </span>
                                    </div>
                                </div>

                                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500">
                                    <XMarkIcon class="h-6 w-6" />
                                </button>
                            </div>

                            <!-- Loading state -->
                            <div v-if="isLoading" class="py-4 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                <p class="mt-2 text-sm text-gray-500">Chargement des détails...</p>
                            </div>

                            <!-- Contenu -->
                            <div v-else class="space-y-6">
                                <!-- Informations de base -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Informations</h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Nom</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ team.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Propriétaire</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ team.owner?.name || 'Non spécifié' }}</dd>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <dt class="text-xs font-medium text-gray-500">Description</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ team.description || 'Aucune description' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Créée le</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ formatDate(team.created_at) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Membres</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ members.length }} membre(s)
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Liste des membres -->
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="text-sm font-medium text-gray-900">Membres de l'équipe</h4>
                                        <span class="text-xs text-gray-500">{{ members.length }} membre(s)</span>
                                    </div>
                                    
                                    <div v-if="members.length > 0" class="border border-gray-200 rounded-md overflow-hidden">
                                        <ul class="divide-y divide-gray-200">
                                            <li v-for="member in members" :key="member.id"
                                                class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                                                <div class="flex items-center">
                                                    <div
                                                        class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-semibold">
                                                        {{ getInitials(member.name) }}
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                                                        <p class="text-xs text-gray-500">{{ member.email }}</p>
                                                        <p class="text-xs text-gray-400 mt-1">
                                                            Membre depuis {{ formatDate(member.joined_at) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <span v-if="String(team.owner_id) === String(member.id)"
                                                    class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                                                    Propriétaire
                                                </span>
                                                <span v-else
                                                    class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                                    Membre
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div v-else class="text-center py-8 border border-gray-200 rounded-md bg-gray-50">
                                        <p class="text-sm text-gray-500">Aucun membre dans cette équipe</p>
                                    </div>
                                </div>

                                <!-- Statistiques (optionnel si l'API existe) -->
                                <div v-if="statistics">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Statistiques</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Projets</p>
                                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ statistics.total_projects || 0 }}</p>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Membres</p>
                                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ statistics.total_members || members.length }}</p>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Actifs</p>
                                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ statistics.active_projects || 0 }}</p>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Terminés</p>
                                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ statistics.completed_projects || 0 }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button v-if="canEdit" @click="handleEdit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                        Modifier
                    </button>
                    <button @click="$emit('close')"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useTeamStore } from '@/stores/team.store'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { Team, TeamMember } from '@/services/team.service'

const props = defineProps<{
    team: Team
    canEdit?: boolean
}>()

const emit = defineEmits<{
    close: []
    edit: []
}>()

const teamStore = useTeamStore()
const members = ref<TeamMember[]>([])
const statistics = ref<any>(null)
const isLoading = ref(false)

// Charger les membres et statistiques
const loadDetails = async () => {
    isLoading.value = true
    try {
        console.log('Chargement des membres pour l\'équipe:', props.team.id)
        
        // Chargez les membres
        members.value = await teamStore.fetchTeamMembers(props.team.id)
        console.log('Membres chargés:', members.value)
        
        // Essayez de charger les statistiques (si la route existe)
        try {
            const stats = await teamStore.fetchTeamStatistics(props.team.id)
            statistics.value = stats
            console.log('Statistiques chargées:', stats)
        } catch (error) {
            console.warn('Statistiques non disponibles:', error)
            statistics.value = null
        }
    } catch (error) {
        console.error('Erreur lors du chargement des détails:', error)
        members.value = []
        statistics.value = null
    } finally {
        isLoading.value = false
    }
}

onMounted(async () => {
    await loadDetails()
})

// Fonction pour gérer l'édition depuis le modal
const handleEdit = () => {
    emit('edit')
    emit('close')
}

// Fonctions utilitaires
const formatDate = (dateString: string) => {
    if (!dateString) return 'Non spécifié'
    try {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        })
    } catch {
        return dateString
    }
}

const getInitials = (name: string) => {
    if (!name) return '??'
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
}
</script>