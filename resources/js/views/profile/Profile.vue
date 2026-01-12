<template>
    <div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
         <div class="mb-8 sm:mb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6">
        <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-2">
                Profil
            </h1>
            <p class="text-sm sm:text-base text-gray-600">
                Gérez vos informations personnelles et préférences
            </p>
        </div>
        <div class="flex-shrink-0">
            <!-- Avatar avec image en background -->
            <div class="w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 rounded-full overflow-hidden shadow-lg border-4 border-white 
                        bg-gradient-to-br from-primary to-secondary flex items-center justify-center"
                 :style="authStore.user?.avatar 
                         ? { backgroundImage: `url(${authStore.user.avatar})`, backgroundSize: 'cover', backgroundPosition: 'center' } 
                         : {}">
                <span class="text-white font-bold text-lg sm:text-xl lg:text-2xl 
                             bg-black/30 backdrop-blur-sm rounded-full w-full h-full flex items-center justify-center">
                    {{ getInitials(authStore.user?.name || '') }}
                </span>
            </div>
        </div>
    </div>
</div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Personal Information Card -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <!-- Card Header -->
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">
                                        Informations personnelles
                                    </h2>
                                    <p class="text-sm text-gray-600">
                                        Détails de votre compte
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Information List -->
                        <div class="divide-y divide-gray-100">
                            <!-- Name -->
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between">
                                <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600">
                                        Nom complet
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ authStore.user?.name || 'Non défini' }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between">
                                <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600">
                                        Adresse email
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ authStore.user?.email || 'Non défini' }}
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between">
                                <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600">
                                        Rôle
                                    </span>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                               bg-green-100 text-green-800">
                                        {{ formatRole(authStore.user?.role) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Member Since -->
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between">
                                <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600">
                                        Membre depuis
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ formatDate(authStore.user?.created_at) }}
                                </div>
                            </div>

                            <!-- Last Login -->
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between">
                                <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600">
                                        Dernière connexion
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ formatLastLogin(authStore.user?.last_login) }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-6 py-5 border-t border-gray-100 bg-gray-50">
                            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                                <button @click="editProfile" class="flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-dark 
                                               text-white text-sm font-medium rounded-lg transition-colors duration-200 
                                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Modifier le profil
                                </button>
                                <button @click="changePassword" class="flex items-center justify-center px-4 py-2.5 bg-white hover:bg-gray-50 
                                               text-gray-700 text-sm font-medium rounded-lg border border-gray-300 
                                               transition-colors duration-200 focus:outline-none focus:ring-2 
                                               focus:ring-offset-2 focus:ring-primary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Changer le mot de passe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full">
                        <!-- Card Header -->
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-secondary rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">
                                        Statistiques
                                    </h2>
                                    <p class="text-sm text-gray-600">
                                        Votre activité sur la plateforme
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="p-6 space-y-4">
                            <!-- Tasks Created -->
                            <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div
                                    class="w-12 h-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">0</div>
                                    <div class="text-sm text-gray-600">Tâches créées</div>
                                </div>
                            </div>

                            <!-- Tasks Completed -->
                            <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div
                                    class="w-12 h-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">0</div>
                                    <div class="text-sm text-gray-600">Tâches terminées</div>
                                </div>
                            </div>

                            <!-- Time Worked -->
                            <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div
                                    class="w-12 h-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">0h</div>
                                    <div class="text-sm text-gray-600">Temps travaillé</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const getInitials = (name: string) => {
    if (!name) return '??'
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
}

const formatDate = (dateString?: string) => {
    if (!dateString) return 'Non disponible'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatLastLogin = (dateString?: string) => {
    if (!dateString) return 'Jamais'

    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))

    if (diffHours < 1) {
        return 'Il y a moins d\'une heure'
    } else if (diffHours < 24) {
        return `Il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`
    } else {
        return formatDate(dateString)
    }
}

const formatRole = (role?: string) => {
    if (!role) return 'Utilisateur'

    const roleMap: Record<string, string> = {
        'admin': 'Administrateur',
        'user': 'Utilisateur',
        'manager': 'Gestionnaire',
        'editor': 'Éditeur'
    }

    return roleMap[role] || role
}

const editProfile = () => {
    console.log('Edit profile')
    // Implémentez la logique d'édition
}

const changePassword = () => {
    console.log('Change password')
    // Implémentez la logique de changement de mot de passe
}

const viewSessions = () => {
    console.log('View active sessions')
    // Implémentez la logique pour voir les sessions actives
}
</script>

<style scoped>
/* Classes Tailwind personnalisées si nécessaire */
:deep(.bg-primary) {
    background-color: #31b6b8;
}

:deep(.bg-primary-dark) {
    background-color: #289396;
}

:deep(.hover\:bg-primary-dark:hover) {
    background-color: #289396;
}

:deep(.bg-secondary) {
    background-color: #ab2283;
}

:deep(.text-primary) {
    color: #31b6b8;
}

:deep(.text-secondary) {
    color: #ab2283;
}

:deep(.focus\:ring-primary:focus) {
    --tw-ring-color: rgba(49, 182, 184, 0.5);
}
</style>