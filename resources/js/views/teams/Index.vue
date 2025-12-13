<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Équipes</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Gérez vos équipes et collaborez efficacement
                    </p>
                </div>

                <button @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Nouvelle équipe
                </button>
            </div>

            <!-- Filtres -->
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:space-x-4 space-y-4 md:space-y-0">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                            </div>
                            <input v-model="searchQuery" @input="handleSearch" type="text"
                                placeholder="Rechercher une équipe..."
                                class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <select v-model="filters.is_public" @change="loadTeams"
                            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option :value="undefined">Tous les statuts</option>
                            <option :value="true">Public</option>
                            <option :value="false">Privé</option>
                        </select>

                        <select v-model="filters.per_page" @change="loadTeams"
                            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="10">10 par page</option>
                            <option value="15">15 par page</option>
                            <option value="20">20 par page</option>
                            <option value="50">50 par page</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- État de chargement -->
            <div v-if="teamStore.isLoading && !teamStore.teams.length" class="text-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-sm text-gray-500">Chargement des équipes...</p>
            </div>

            <!-- État d'erreur -->
            <div v-else-if="teamStore.error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <ExclamationCircleIcon class="h-5 w-5 text-red-400 mr-3" />
                    <div class="text-sm text-red-700">
                        <p>{{ teamStore.error }}</p>
                        <button @click="loadTeams" class="mt-2 text-red-600 hover:text-red-800 font-medium">
                            Réessayer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Liste des équipes -->
            <div v-else>
                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ teamStore.pagination.total }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Mes équipes</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ teamStore.myTeams.length }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Équipes publiques</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ teamStore.publicTeams.length }}</p>
                    </div>
                </div>

                <!-- Grille des équipes -->
                <div v-if="teamStore.teams.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <TeamCard v-for="team in teamStore.teams" :key="team.id" :team="team"
                            :can-edit="team.owner_id === authStore.user?.id"
                            :can-delete="team.owner_id === authStore.user?.id" @view="handleViewTeam"
                            @edit="handleEditTeam" @delete="handleDeleteTeam" />
                    </div>

                    <!-- Pagination -->
                    <div v-if="teamStore.pagination.last_page > 1" class="mt-8 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <button @click="previousPage" :disabled="teamStore.pagination.current_page === 1" :class="[
                                'px-3 py-1.5 border border-gray-300 rounded-md text-sm',
                                teamStore.pagination.current_page === 1
                                    ? 'text-gray-400 cursor-not-allowed'
                                    : 'text-gray-700 hover:bg-gray-50'
                            ]">
                                Précédent
                            </button>

                            <span class="px-3 py-1.5 text-sm text-gray-700">
                                Page {{ teamStore.pagination.current_page }} sur {{ teamStore.pagination.last_page }}
                            </span>

                            <button @click="nextPage"
                                :disabled="teamStore.pagination.current_page === teamStore.pagination.last_page" :class="[
                                    'px-3 py-1.5 border border-gray-300 rounded-md text-sm',
                                    teamStore.pagination.current_page === teamStore.pagination.last_page
                                        ? 'text-gray-400 cursor-not-allowed'
                                        : 'text-gray-700 hover:bg-gray-50'
                                ]">
                                Suivant
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="text-center py-12 bg-white rounded-lg border border-gray-200">
                    <UsersIcon class="h-12 w-12 text-gray-400 mx-auto" />
                    <h3 class="mt-4 text-sm font-medium text-gray-900">Aucune équipe</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Commencez par créer votre première équipe.
                    </p>
                    <button @click="showCreateModal = true"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Créer une équipe
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de création -->
        <TeamCreateModal v-if="showCreateModal" @close="showCreateModal = false" @created="handleTeamCreated" />

        <!-- Modal d'édition -->
        <TeamEditModal v-if="selectedTeam" :team="selectedTeam" @close="selectedTeam = null"
            @updated="handleTeamUpdated" />

        <!-- Modal de suppression -->
        <TeamDeleteModal v-if="teamToDelete" :team="teamToDelete" @close="teamToDelete = null"
            @deleted="handleTeamDeleted" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTeamStore } from '@/stores/team.store'
import TeamCard from '@/components/teams/TeamCard.vue'
import TeamCreateModal from '@/components/teams/TeamCreateModal.vue'
import TeamEditModal from '@/components/teams/TeamEditModal.vue'
import TeamDeleteModal from '@/components/teams/TeamDeleteModal.vue'
import {
    PlusIcon,
    MagnifyingGlassIcon,
    ExclamationCircleIcon,
    UsersIcon
} from '@heroicons/vue/24/outline'
import type { Team } from '@/services/team.service'

const router = useRouter()
const teamStore = useTeamStore()
const authStore = useAuthStore()

// State
const showCreateModal = ref(false)
const selectedTeam = ref<Team | null>(null)
const teamToDelete = ref<Team | null>(null)
const searchQuery = ref('')
const filters = ref({
    search: '',
    is_public: undefined as boolean | undefined,
    per_page: 15
})

// Search avec debounce
let searchTimeout: NodeJS.Timeout
const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        filters.value.search = searchQuery.value
        loadTeams()
    }, 300)
}

// Charger les équipes
const loadTeams = async () => {
    await teamStore.fetchTeams({
        page: teamStore.pagination.current_page,
        per_page: filters.value.per_page,
        search: filters.value.search,
        is_public: filters.value.is_public
    })
}

// Navigation de pagination
const previousPage = () => {
    if (teamStore.pagination.current_page > 1) {
        teamStore.pagination.current_page--
        loadTeams()
    }
}

const nextPage = () => {
    if (teamStore.pagination.current_page < teamStore.pagination.last_page) {
        teamStore.pagination.current_page++
        loadTeams()
    }
}

// Gestion des événements
const handleViewTeam = (team: Team) => {
    router.push({ name: 'teams.detail', params: { id: team.id } })
}

const handleEditTeam = (team: Team) => {
    selectedTeam.value = team
}

const handleDeleteTeam = (team: Team) => {
    teamToDelete.value = team
}

const handleTeamCreated = () => {
    showCreateModal.value = false
    loadTeams()
}

const handleTeamUpdated = () => {
    selectedTeam.value = null
    loadTeams()
}

const handleTeamDeleted = () => {
    teamToDelete.value = null
    loadTeams()
}

// Charger au montage
onMounted(() => {
    loadTeams()
})

// Nettoyer le store quand on quitte la page
import { onBeforeUnmount } from 'vue'
import { useAuthStore } from '@/stores/auth'
onBeforeUnmount(() => {
    teamStore.reset()
})
</script>