<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête avec dégradé -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-primary/5 p-6 mb-8">
                <div class="relative">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-white rounded-xl shadow-sm">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Équipes</h1>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Collaborez efficacement avec vos équipes et gérez vos projets ensemble
                                    </p>
                                </div>
                            </div>

                            <!-- Statistiques rapides -->
                            <div class="flex items-center gap-6 mt-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-primary rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium text-gray-700">{{ pagination.total || 0 }}</span>
                                    <span class="text-sm text-gray-500">Équipes totales</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-sm font-medium text-gray-700">{{ mesEquipes.length || 0 }}</span>
                                    <span class="text-sm text-gray-500">Mes équipes</span>
                                </div>
                            </div>
                        </div>

                        <button @click="showCreateModal = true"
                            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-secondary rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transform transition-all duration-200 min-w-[180px]">
                            <div
                                class="absolute inset-0 bg-secondary-darkrounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            </div>
                            <svg class="w-5 h-5 mr-2 relative z-10" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="relative z-10">Nouvelle équipe</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recherche et filtres -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Recherche -->
                    <div class="flex-1">
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input v-model="recherche" @input="gererRecherche" type="text"
                                placeholder="Rechercher des équipes par nom, description..."
                                class="pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white" />
                            <div v-if="recherche" @click="effacerRecherche"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer group/clear">
                                <svg class="w-4 h-4 text-gray-400 group-hover/clear:text-gray-600 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="flex items-center gap-3">
                        <div class="relative group">
                            <select v-model="filtres.is_public" @change="chargerEquipes"
                                class="appearance-none pl-4 pr-10 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white min-w-[140px]">
                                <option :value="undefined">Tous les statuts</option>
                                <option :value="true">Public</option>
                                <option :value="false">Privé</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <div class="relative group">
                            <select v-model="filtres.per_page" @change="chargerEquipes"
                                class="appearance-none pl-4 pr-10 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white">
                                <option value="10">10 par page</option>
                                <option value="15">15 par page</option>
                                <option value="20">20 par page</option>
                                <option value="50">50 par page</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- État de chargement -->
            <div v-if="enChargement && equipes.length === 0"
                class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-primary/20 rounded-full"></div>
                        <div
                            class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin absolute top-0 left-0">
                        </div>
                    </div>
                    <p class="mt-4 text-sm font-medium text-gray-600">Chargement des équipes...</p>
                    <p class="mt-1 text-xs text-gray-400">Veuillez patienter un moment</p>
                </div>
            </div>

            <!-- État d'erreur -->
            <div v-else-if="erreur"
                class="bg-gradient-to-r from-red-50 to-red-100 border border-red-200 rounded-2xl p-6 mb-6 animate-fade-in">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.75 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-1">Impossible de charger les équipes</h3>
                        <p class="text-sm text-red-700">{{ erreur }}</p>
                        <button @click="chargerEquipes"
                            class="mt-3 inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Réessayer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contenu des équipes -->
            <div v-else>
                <!-- Cartes de statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div
                        class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Équipes totales
                                </p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ pagination.total || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                <span>{{ pagination.total - mesEquipes.length }} autres équipes</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mes équipes</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ mesEquipes.length || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ mesEquipes.length > 0 ? 'Vous possédez celles-ci' : 'Aucune équipe possédée'
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Équipes actives
                                </p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ equipesActivesCount }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1 text-purple-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                <span>{{ equipesActivesPourcentage }}% du total</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Cette page</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ pagination.per_page || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1 text-amber-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ equipes.length }} éléments affichés</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grille des équipes -->
                <div v-if="equipes && equipes.length > 0">
                    <!-- En-tête -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Toutes les équipes</h2>
                            <p class="text-sm text-gray-500 mt-1">Gérez et collaborez avec vos équipes</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-gray-500">
                                Affichage {{ pagination.from }}-{{ pagination.to }} sur {{ pagination.total }}
                            </span>
                        </div>
                    </div>

                    <!-- Grille des équipes -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <TeamCard v-for="equipe in equipes" :key="equipe.id" :team="equipe"
                            :can-edit="equipe.owner_id === authStore.user?.id"
                            :can-delete="equipe.owner_id === authStore.user?.id" @view="afficherDetailsEquipe"
                            @edit="gererEditionEquipe" @delete="gererSuppressionEquipe" />
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="mt-8">
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">{{ pagination.total }}</span> équipes au total • Page
                                    <span class="font-medium">{{ pagination.current_page }}</span> sur
                                    <span class="font-medium">{{ pagination.last_page }}</span>
                                </div>

                                <nav class="flex items-center space-x-2">
                                    <button @click="pagePrecedente" :disabled="pagination.current_page === 1" :class="[
                                        'px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium transition-all',
                                        pagination.current_page === 1
                                            ? 'text-gray-400 bg-gray-50 cursor-not-allowed'
                                            : 'text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm'
                                    ]">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 19l-7-7 7-7" />
                                            </svg>
                                            Précédent
                                        </div>
                                    </button>

                                    <!-- Numéros de page -->
                                    <div class="flex items-center space-x-1">
                                        <button v-for="page in pagesVisibles" :key="page" @click="allerAPage(page)"
                                            :class="[
                                                'w-10 h-10 rounded-xl text-sm font-medium transition-all',
                                                page === pagination.current_page
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'text-gray-700 hover:bg-gray-100'
                                            ]">
                                            {{ page }}
                                        </button>
                                        <span v-if="afficherEllipse" class="px-2 text-gray-400">...</span>
                                    </div>

                                    <button @click="pageSuivante"
                                        :disabled="pagination.current_page === pagination.last_page" :class="[
                                            'px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium transition-all',
                                            pagination.current_page === pagination.last_page
                                                ? 'text-gray-400 bg-gray-50 cursor-not-allowed'
                                                : 'text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm'
                                        ]">
                                        <div class="flex items-center gap-2">
                                            Suivant
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else
                    class="bg-gradient-to-b from-white to-gray-50 rounded-2xl border border-gray-100 p-12 text-center shadow-sm">
                    <div class="max-w-md mx-auto">
                        <div
                            class="w-20 h-20 mx-auto bg-gradient-to-br from-primary/10 to-secondary/10 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune équipe trouvée</h3>
                        <p class="text-gray-500 mb-6">
                            {{ recherche || filtres.is_public !== undefined ?
                                'Essayez d\'ajuster votre recherche ou vos filtres' :
                                'Créez votre première équipe pour commencer à collaborer' }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button @click="showCreateModal = true"
                                class="inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-secondary rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transform transition-all duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Créer première équipe
                            </button>
                            <button v-if="recherche || filtres.is_public !== undefined" @click="effacerFiltres"
                                class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Effacer les filtres
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <TeamCreateModal v-if="showCreateModal" @close="showCreateModal = false" @created="gererEquipeCreee" />
        <TeamDetailModal v-if="equipeSelectionneePourDetails" :team="equipeSelectionneePourDetails"
            :can-edit="equipeSelectionneePourDetails.owner_id === authStore.user?.id"
            @close="equipeSelectionneePourDetails = null" @edit="gererEditionDepuisDetails" />
        <TeamEditModal v-if="equipeSelectionnee" :team="equipeSelectionnee" @close="equipeSelectionnee = null"
            @updated="gererEquipeMiseAJour" />
        <TeamDeleteModal v-if="equipeASupprimer" :team="equipeASupprimer" @close="equipeASupprimer = null"
            @deleted="gererEquipeSupprimee" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import { useTeamStore } from '@/stores/team.store'
import { useAuthStore } from '@/stores/auth'
import TeamCard from '@/components/teams/TeamCard.vue'
import TeamCreateModal from '@/components/teams/TeamCreateModal.vue'
import TeamDetailModal from '@/components/teams/TeamDetailModal.vue'
import TeamEditModal from '@/components/teams/TeamEditModal.vue'
import TeamDeleteModal from '@/components/teams/TeamDeleteModal.vue'

const teamStore = useTeamStore()
const authStore = useAuthStore()

// Utilisez storeToRefs pour la réactivité
const {
    teams: storeTeams,
    safeTeams: storeSafeTeams,
    myTeams: storeMyTeams,
    pagination: storePagination,
    isLoading: storeIsLoading,
    error: storeError
} = storeToRefs(teamStore)

// Computed pour le template
const equipes = computed(() => storeTeams.value || [])
const equipesSecurisees = computed(() => storeSafeTeams.value || [])
const mesEquipes = computed(() => storeMyTeams.value || [])
const pagination = computed(() => storePagination.value || {
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
})
const enChargement = computed(() => storeIsLoading.value)
const erreur = computed(() => storeError.value)

// Propriétés computed supplémentaires
const equipesActivesCount = computed(() => {
    return equipes.value.filter(equipe => equipe.is_active !== false).length
})

const equipesActivesPourcentage = computed(() => {
    if (equipes.value.length === 0) return 0
    return Math.round((equipesActivesCount.value / equipes.value.length) * 100)
})

const pagesVisibles = computed(() => {
    const pages = []
    const courant = pagination.value.current_page
    const dernier = pagination.value.last_page

    if (dernier <= 5) {
        for (let i = 1; i <= dernier; i++) {
            pages.push(i)
        }
    } else {
        if (courant <= 3) {
            pages.push(1, 2, 3, 4, '...', dernier)
        } else if (courant >= dernier - 2) {
            pages.push(1, '...', dernier - 3, dernier - 2, dernier - 1, dernier)
        } else {
            pages.push(1, '...', courant - 1, courant, courant + 1, '...', dernier)
        }
    }
    return pages.filter((page, index, array) => page !== '...' || array[index - 1] !== '...')
})

const afficherEllipse = computed(() => {
    return pagesVisibles.value.includes('...')
})

// État
const showCreateModal = ref(false)
const equipeSelectionnee = ref<any>(null)
const equipeSelectionneePourDetails = ref<any>(null)
const equipeASupprimer = ref<any>(null)
const recherche = ref('')
const filtres = ref({
    search: '',
    is_public: undefined as boolean | undefined,
    per_page: 15
})

// Recherche avec debounce
let rechercheTimeout: NodeJS.Timeout
const gererRecherche = () => {
    clearTimeout(rechercheTimeout)
    rechercheTimeout = setTimeout(() => {
        filtres.value.search = recherche.value
        chargerEquipes()
    }, 300)
}

const effacerRecherche = () => {
    recherche.value = ''
    filtres.value.search = ''
    chargerEquipes()
}

const effacerFiltres = () => {
    recherche.value = ''
    filtres.value = {
        search: '',
        is_public: undefined,
        per_page: 15
    }
    chargerEquipes()
}

// Charger les équipes
const chargerEquipes = async () => {
    try {
        await teamStore.fetchTeams({
            page: pagination.value.current_page,
            per_page: Number(filtres.value.per_page) || 15,
            search: filtres.value.search,
            is_public: filtres.value.is_public
        })
    } catch (err) {
        console.error('Erreur lors du chargement des équipes:', err)
    }
}

// Navigation de pagination
const pagePrecedente = () => {
    if (pagination.value.current_page > 1) {
        teamStore.pagination.current_page--
        chargerEquipes()
    }
}

const pageSuivante = () => {
    if (pagination.value.current_page < pagination.value.last_page) {
        teamStore.pagination.current_page++
        chargerEquipes()
    }
}

const allerAPage = (page: number | string) => {
    if (typeof page === 'number' && page >= 1 && page <= pagination.value.last_page) {
        teamStore.pagination.current_page = page
        chargerEquipes()
    }
}

// Gestion des événements
const afficherDetailsEquipe = (equipe: any) => {
    equipeSelectionneePourDetails.value = equipe
}

const gererEditionDepuisDetails = () => {
    if (equipeSelectionneePourDetails.value) {
        equipeSelectionnee.value = equipeSelectionneePourDetails.value
        equipeSelectionneePourDetails.value = null
    }
}

const gererEditionEquipe = (equipe: any) => {
    equipeSelectionnee.value = equipe
}

const gererSuppressionEquipe = (equipe: any) => {
    equipeASupprimer.value = equipe
}

const gererEquipeCreee = () => {
    showCreateModal.value = false
    chargerEquipes()
}

const gererEquipeMiseAJour = () => {
    equipeSelectionnee.value = null
    chargerEquipes()
}

const gererEquipeSupprimee = () => {
    equipeASupprimer.value = null
    chargerEquipes()
}

// Charger au montage
onMounted(() => {
    chargerEquipes()
})

// Nettoyer le store quand on quitte la page
onBeforeUnmount(() => {
    teamStore.reset()
})
</script>

<style scoped>
/* Animations personnalisées */
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Barre de défilement personnalisée pour la page */
:deep(::-webkit-scrollbar) {
    width: 8px;
    height: 8px;
}

:deep(::-webkit-scrollbar-track) {
    background: #f1f1f1;
    border-radius: 4px;
}

:deep(::-webkit-scrollbar-thumb) {
    background: #c1c1c1;
    border-radius: 4px;
}

:deep(::-webkit-scrollbar-thumb:hover) {
    background: #a1a1a1;
}

/* Transitions fluides */
* {
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* Couleurs personnalisées */
:deep(.bg-primary) {
    background-color: #ab2283;
}

:deep(.bg-secondary) {
    background-color: #31b6b8;
}

:deep(.from-primary) {
    --tw-gradient-from: #ab2283 var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(171 34 131 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

:deep(.to-secondary) {
    --tw-gradient-to: #31b6b8 var(--tw-gradient-to-position);
}

:deep(.from-primary-dark) {
    --tw-gradient-from: #8a1c6a var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(138 28 106 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

:deep(.to-secondary-dark) {
    --tw-gradient-to: #289396 var(--tw-gradient-to-position);
}

:deep(.text-primary) {
    color: #ab2283;
}

:deep(.text-secondary) {
    color: #31b6b8;
}
</style>