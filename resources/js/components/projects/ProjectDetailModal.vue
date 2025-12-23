<template>
    <!-- Modal Overlay -->
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                @click="closeModal"></div>

            <!-- Modal panel -->
            <div
                class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl w-full">
                <!-- Loading State -->
                <div v-if="loading"
                    class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary mx-auto"></div>
                        <p class="mt-4 text-gray-600 font-medium">Chargement des détails du projet...</p>
                    </div>
                </div>

                <!-- Content -->
                <div v-else>
                    <!-- Header -->
                    <div class="sticky top-0 bg-gray-100 px-8 py-6 border-b border-gray-100 z-10">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4 px-2">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center shadow-lg">
                                            <span class="text-2xl font-bold text-gray-800">{{
                                                getProjectInitials(project?.name)
                                                }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h1 class="text-2xl font-bold text-gray-900">{{ project?.name }}</h1>
                                            <span
                                                :class="`inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold ${getStatusColorClasses(project?.status)} shadow-sm`">
                                                {{ getStatusLabel(project?.status) }}
                                            </span>
                                        </div>
                                        <p v-if="project?.description" class="text-gray-600 max-w-3xl">
                                            {{ project.description }}
                                        </p>
                                        <div class="flex items-center gap-4 mt-3">
                                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>Créé {{ formatRelativeDate(project?.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Dernière mise à jour {{ formatRelativeDate(project?.updated_at)
                                                    }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="relative group">
                                    <button @click="toggleTeamMembers"
                                        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="font-medium text-gray-700">{{ teamMembersCount }}</span>
                                        <span class="text-gray-500">Members</span>
                                    </button>
                                    <div v-if="showTeamMembersTooltip"
                                        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 p-4 z-50">
                                        <div class="space-y-3">
                                            <div v-for="member in displayedTeamMembers" :key="member.id"
                                                class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg">
                                                <div class="relative">
                                                    <div v-if="member.avatar_url"
                                                        class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-white">
                                                        <img :src="member.avatar_url" :alt="member.name"
                                                            class="w-full h-full object-cover" />
                                                    </div>
                                                    <div v-else
                                                        class="w-10 h-10 bg-gradient-to-br from-primary/20 to-secondary/20 rounded-full flex items-center justify-center ring-2 ring-white">
                                                        <span class="font-semibold text-gray-700">{{ member.initials
                                                            }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{
                                                        member.name }}</p>
                                                    <p class="text-xs text-gray-500 truncate">{{ member.email }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button @click="closeModal"
                                    class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-xl transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Left Column: Project Overview -->
                            <div class="lg:col-span-2 space-y-8">
                                <!-- Team Section -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                                    <div class="flex items-center justify-between mb-6">
                                        <div>
                                            <h2 class="text-lg font-bold text-gray-900">Équipe</h2>
                                            <p class="text-sm text-gray-500">Équipe assignée et membres

                                            </p>
                                        </div>
                                        <button @click="handleAssignUsers"
                                            class="px-4 py-2 text-sm font-medium text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Gérer l'équipe
                                        </button>
                                    </div>

                                    <!-- Team Card -->
                                    <div v-if="project?.team"
                                        class="bg-gradient-to-r from-primary/5 to-secondary/5 rounded-xl p-4 mb-6">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center border border-gray-100">
                                                <span class="text-xl font-bold text-primary">{{
                                                    getInitials(project.team.name)
                                                    }}</span>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-900">{{ project.team.name }}</h3>
                                                <p v-if="project.team.description" class="text-sm text-gray-600 mt-1">
                                                    {{ project.team.description }}
                                                </p>
                                                <div class="flex items-center gap-4 mt-2">
                                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        </svg>
                                                        <span>{{ teamMembersCount }} membres</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                        </svg>
                                                        <span v-if="project.team.is_public">Public</span>
                                                        <span v-else>Privé</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Team Members Grid -->
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wider">
                                            Membres d'équipe
                                        </h3>
                                        <div v-if="displayedTeamMembers.length > 0"
                                            class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div v-for="member in displayedTeamMembers" :key="member.id"
                                                class="group relative bg-white border border-gray-100 rounded-xl p-4 hover:shadow-lg transition-all hover:border-primary/20">
                                                <div class="flex items-center gap-4">
                                                    <div class="relative">
                                                        <div v-if="member.avatar_url"
                                                            class="w-14 h-14 rounded-xl overflow-hidden ring-2 ring-white shadow-sm">
                                                            <img :src="member.avatar" :alt="member.name"
                                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
                                                        </div>
                                                        <div v-else
                                                            class="w-14 h-14 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-xl flex items-center justify-center ring-2 ring-white shadow-sm">
                                                            <span class="text-lg font-bold text-gray-700">{{
                                                                member.initials
                                                                }}</span>
                                                        </div>
                                                        <div
                                                            class="absolute -bottom-1 -right-1 w-6 h-6 bg-primary rounded-full border-2 border-white flex items-center justify-center">
                                                            <span class="text-xs font-semibold text-white">{{
                                                                member.role?.charAt(0) || 'M'
                                                                }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="font-semibold text-gray-900 truncate">{{ member.name
                                                            }}</h4>
                                                        <p class="text-sm text-gray-500 truncate">{{ member.email }}</p>
                                                        <div class="flex items-center gap-2 mt-2">
                                                            <span
                                                                :class="`px-2 py-1 text-xs font-medium rounded-full ${getRoleColorClasses(member.role)}`">
                                                                {{ member.role || 'Member' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-8">
                                            <div
                                                class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0c-.66 0-1.293.1-1.879.277M17 10h.01M7 10h.01M12 10h.01" />
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Pas de membres d’équipe
                                            </h3>
                                            <p class="text-gray-500 mb-4">Ajouter des membres à ce projet pour commencer
                                            </p>
                                            <button @click="handleAssignUsers"
                                                class="px-4 py-2 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors">
                                                Ajouter des membres d'équipe
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline Section -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                                    <h2 class="text-lg font-bold text-gray-900 mb-6">échéancier</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-gray-500">Date de début</div>
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-lg font-semibold text-gray-900">
                                                        {{ formatDate(project?.start_date) }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ getDayOfWeek(project?.start_date) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-gray-500">Date de fin</div>
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-green-500" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-lg font-semibold text-gray-900">
                                                        {{ formatDate(project?.end_date) }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ getDayOfWeek(project?.end_date) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-gray-500">Durée</div>
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-purple-500" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-lg font-semibold text-gray-900">
                                                        {{ calculateDuration(project?.start_date, project?.end_date) }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ getProgressStatus() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mt-8">
                                        <div class="flex justify-between text-sm font-medium text-gray-700 mb-2">
                                            <span>Progression du projet</span>
                                            <span>{{ calculateProgressPercentage() }}%</span>
                                        </div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-secondary rounded-full transition-all duration-500"
                                                :style="{ width: calculateProgressPercentage() + '%' }"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                                            <span>Début: {{ formatDate(project?.start_date) }}</span>
                                            <span>Fin: {{ formatDate(project?.end_date) }}</span>
                                        </div>
                                    </div>

                                    <!-- Timeline Alert -->
                                    <div v-if="isEndDateApproaching"
                                        class="mt-6 p-4 bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-xl">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.75 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-amber-800">Projet terminé bientôt</h4>
                                                <p class="text-sm text-amber-700 mt-1">
                                                    Ce projet est programmé pour se terminer dans {{ daysUntilEnd }}
                                                    jours.
                                                    Pensez à prolonger le calendrier si nécessaire.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Actions & Stats -->
                            <div class="space-y-8">
                                <!-- Quick Actions -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                                    <h2 class="text-lg font-bold text-gray-900 mb-6">Actions rapides</h2>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button v-if="project?.status !== 'completed'" @click="completeProject"
                                            class="flex flex-col items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-xl transition-colors group">
                                            <div
                                                class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <span class="font-semibold text-green-800">Terminer</span>
                                            <span class="text-xs text-green-600 mt-1">Marquer comme terminé</span>
                                        </button>

                                        <button v-if="project?.status !== 'on_hold' && project?.status !== 'completed'"
                                            @click="putOnHold"
                                            class="flex flex-col items-center justify-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-xl transition-colors group">
                                            <div
                                                class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-semibold text-yellow-800">Pause</span>
                                            <span class="text-xs text-yellow-600 mt-1">Mettre en pause</span>
                                        </button>

                                        <button v-if="project?.status !== 'cancelled'" @click="cancelProject"
                                            class="flex flex-col items-center justify-center p-4 bg-red-50 hover:bg-red-100 rounded-xl transition-colors group">
                                            <div
                                                class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </div>
                                            <span class="font-semibold text-red-800">Annuler</span>
                                            <span class="text-xs text-red-600 mt-1">Arrêter le projet</span>
                                        </button>

                                        <button v-if="project?.status !== 'active'" @click="reactivateProject"
                                            class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors group">
                                            <div
                                                class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-semibold text-blue-800">Réactiver</span>
                                            <span class="text-xs text-blue-600 mt-1">Redémarrer le projet</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Project Stats -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                                    <h2 class="text-lg font-bold text-gray-900 mb-6">Statistiques du projet</h2>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-500">Tâches totales</div>
                                                    <div class="text-lg font-bold text-gray-900">{{ project?.tasks_count
                                                        || 0 }}</div>
                                                </div>
                                            </div>
                                            <button @click="handleViewTasks"
                                                class="px-3 py-1.5 text-xs font-medium text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors">
                                                Afficher tous
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-green-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-500">Terminées</div>
                                                    <div class="text-lg font-bold text-gray-900">0</div>
                                                </div>
                                            </div>
                                            <span class="text-xs font-medium text-green-600">0%</span>
                                        </div>

                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-500">En cours</div>
                                                    <div class="text-lg font-bold text-gray-900">0</div>
                                                </div>
                                            </div>
                                            <span class="text-xs font-medium text-blue-600">0%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Actions -->
                                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                                    <h2 class="text-lg font-bold text-gray-900 mb-6">Actions du projet</h2>
                                    <div class="space-y-3">
                                        <button @click="handleEdit"
                                            class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <div class="font-semibold text-gray-900">Modifier le projet</div>
                                                    <div class="text-xs text-gray-500">Mettre à jour les détails du
                                                        projet</div>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <button @click="handleDelete"
                                            class="w-full flex items-center justify-between p-4 bg-red-50 hover:bg-red-100 rounded-xl transition-colors group">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <div class="font-semibold text-red-900">Supprimer le projet</div>
                                                    <div class="text-xs text-red-600">Supprimer définitivement</div>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400 group-hover:text-red-600 transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-gray-100 px-8 py-4">
                        <div class="flex justify-between items-center px-4">
                            <div class="text-sm text-gray-500">
                                ID du projet: <span class="font-mono font-medium text-gray-700">{{ project?.id }}</span>
                                • Créé par: <span class="font-medium text-gray-700">Admin</span>
                            </div>
                            <div class="flex gap-3">
                                <button @click="closeModal"
                                    class="px-6 py-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-colors">
                                    Fermer
                                </button>
                                <button @click="handleViewTasks"
                                    class="px-6 py-2.5 text-sm font-medium text-white bg-secondary hover:bg-secondary-dark rounded-xl shadow-sm hover:shadow transition-all">
                                    Voir les tâches du projet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useProjectTeamStore } from '@/stores/projectsTeams.store';
import { projectsTeamsService } from '@/services/projectsTeams.service';
import { useRouter } from 'vue-router';
import type { ProjectTeam } from '@/types/projectsTeams';

const props = defineProps<{
    show: boolean;
    project: ProjectTeam | null;
}>();

const emit = defineEmits<{
    close: [];
    edit: [project: ProjectTeam];
    deleted: [];
}>();

// Store and Router
const projectStore = useProjectTeamStore();
const router = useRouter();

// State
const loading = ref(false);
const showTeamMembersTooltip = ref(false);
const teamMembers = ref<any[]>([]);

// Computed Properties
const displayedTeamMembers = computed(() => {
    // Priorité 1 : Utilisez les membres chargés séparément
    if (teamMembers.value.length > 0) {
        return teamMembers.value;
    }

    // Priorité 2 : Utilisez les membres du projet
    if (props.project?.team_members && props.project.team_members.length > 0) {
        return props.project.team_members;
    }

    // Fallback
    return [];
});

const teamMembersCount = computed(() => {
    return displayedTeamMembers.value.length;
});

const isEndDateApproaching = computed(() => {
    if (!props.project?.end_date || !props.project?.start_date) return false;

    const endDate = new Date(props.project.end_date);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return diffDays <= 7 && diffDays > 0 && props.project.status === 'active';
});

const daysUntilEnd = computed(() => {
    if (!props.project?.end_date) return 0;
    const endDate = new Date(props.project.end_date);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

// Methods
const getInitials = (name?: string) => {
    if (!name) return '??';
    return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
};

const getProjectInitials = (name?: string) => {
    if (!name) return 'P';
    return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
};

const getStatusLabel = (status?: string) => {
    if (!status) return 'Unknown';
    const statusMap: Record<string, string> = {
        active: 'Active',
        completed: 'Completed',
        on_hold: 'On Hold',
        cancelled: 'Cancelled',
        draft: 'Draft',
        archived: 'Archived'
    };
    return statusMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
};

const getStatusColorClasses = (status?: string) => {
    if (!status) return 'bg-gray-100 text-gray-800';
    const colorMap: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        completed: 'bg-blue-100 text-blue-800',
        on_hold: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        draft: 'bg-gray-100 text-gray-800',
        archived: 'bg-purple-100 text-purple-800'
    };
    return colorMap[status] || 'bg-gray-100 text-gray-800';
};

const getRoleColorClasses = (role?: string) => {
    if (!role) return 'bg-gray-100 text-gray-800';
    const colorMap: Record<string, string> = {
        admin: 'bg-red-100 text-red-800',
        manager: 'bg-blue-100 text-blue-800',
        lead: 'bg-purple-100 text-purple-800',
        developer: 'bg-green-100 text-green-800',
        designer: 'bg-pink-100 text-pink-800',
        member: 'bg-gray-100 text-gray-800'
    };
    return colorMap[role.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date?: string | null) => {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatRelativeDate = (date?: string | null) => {
    if (!date) return '';
    const now = new Date();
    const then = new Date(date);
    const diffTime = Math.abs(now.getTime() - then.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "aujourd'hui";
    if (diffDays === 1) return 'hier';
    if (diffDays < 7) return `${diffDays} jours`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} semaines`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} mois`;
    return `${Math.floor(diffDays / 365)} ans`;
};

const getDayOfWeek = (date?: string | null) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', { weekday: 'long' });
};

const calculateDuration = (startDate?: string | null, endDate?: string | null) => {
    if (!startDate || !endDate) return 'N/A';

    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'même jour';
    if (diffDays === 1) return '1 jour';
    if (diffDays < 30) return `${diffDays} jours`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} mois`;
    return `${Math.floor(diffDays / 365)} ans`;
};

const calculateProgressPercentage = () => {
    if (!props.project?.start_date || !props.project?.end_date) return 0;

    const start = new Date(props.project.start_date).getTime();
    const end = new Date(props.project.end_date).getTime();
    const now = new Date().getTime();

    if (now < start) return 0;
    if (now > end) return 100;

    const totalDuration = end - start;
    const elapsed = now - start;
    return Math.round((elapsed / totalDuration) * 100);
};

const getProgressStatus = () => {
    const progress = calculateProgressPercentage();
    if (progress === 0) return 'Non commencé';
    if (progress < 25) return 'Début';
    if (progress < 50) return 'En cours';
    if (progress < 75) return 'Avancé';
    if (progress < 100) return 'Presque terminé';
    return 'Terminé';
};

const toggleTeamMembers = () => {
    showTeamMembersTooltip.value = !showTeamMembersTooltip.value;
};

const closeModal = () => {
    showTeamMembersTooltip.value = false;
    emit('close');
};

const handleEdit = () => {
    if (props.project) {
        emit('edit', props.project);
        closeModal();
    }
};

const handleDelete = () => {
    if (props.project) {
        emit('deleted');
        closeModal();
    }
};

const handleAssignUsers = () => {
    if (props.project) {
        console.log('Assign users to project:', props.project.id);
        // Ici, vous pouvez ouvrir un modal d'assignation
        // Après l'assignation, rechargez les membres
        fetchTeamMembers(props.project.id);
    }
};

const handleViewTasks = () => {
    if (props.project) {
        router.push({ name: 'tasks', query: { project_id: props.project.id } });
        closeModal();
    }
};

// Status Management Actions
const completeProject = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.completeProject(props.project.id);
        // Recharger les données du projet
        await loadProjectData();
    } catch (error) {
        console.error('Failed to complete project:', error);
    } finally {
        loading.value = false;
    }
};

const putOnHold = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.putProjectOnHold(props.project.id);
        await loadProjectData();
    } catch (error) {
        console.error('Failed to put project on hold:', error);
    } finally {
        loading.value = false;
    }
};

const cancelProject = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.cancelProject(props.project.id);
        await loadProjectData();
    } catch (error) {
        console.error('Failed to cancel project:', error);
    } finally {
        loading.value = false;
    }
};

const reactivateProject = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.reactivateProject(props.project.id);
        await loadProjectData();
    } catch (error) {
        console.error('Failed to reactivate project:', error);
    } finally {
        loading.value = false;
    }
};

// Méthode pour charger les membres d'équipe
const fetchTeamMembers = async (projectId: number) => {
    try {
        console.log(`Fetching team members for project ${projectId}...`);
        const members = await projectsTeamsService.getProjectTeamUsers(projectId);
        console.log(`Loaded ${members.length} team members:`, members);
        teamMembers.value = members;
    } catch (error) {
        console.error('Failed to fetch team members:', error);
        teamMembers.value = [];
    }
};

// Méthode pour charger toutes les données du projet
const loadProjectData = async () => {
    if (!props.project?.id) return;

    try {
        loading.value = true;

        // 1. Charger les données du projet avec l'équipe
        await projectStore.fetchProjectWithTeam(props.project.id);

        // 2. Charger les membres d'équipe séparément
        await fetchTeamMembers(props.project.id);

    } catch (error) {
        console.error('Failed to load project data:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for project changes
watch(
    () => props.project,
    async (project) => {
        if (project && props.show) {
            await loadProjectData();
        }
    },
    { immediate: true }
);

// Close tooltip when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.group')) {
            showTeamMembersTooltip.value = false;
        }
    });
});
</script>

<style scoped>
/* Custom animations */
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