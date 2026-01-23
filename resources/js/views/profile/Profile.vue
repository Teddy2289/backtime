<template>
    <div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8 sm:mb-12">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6"
                >
                    <div class="flex-1">
                        <h1
                            class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-2"
                        >
                            Profil
                        </h1>
                        <p class="text-sm sm:text-base text-gray-600">
                            Gérez vos informations personnelles et préférences
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <!-- Avatar avec image en background -->
                        <div
                            class="w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 rounded-full overflow-hidden shadow-lg border-4 border-white bg-gradient-to-br from-primary to-secondary flex items-center justify-center"
                            :style="
                                authStore.user?.avatar
                                    ? {
                                          backgroundImage: `url(${authStore.user.avatar})`,
                                          backgroundSize: 'cover',
                                          backgroundPosition: 'center',
                                      }
                                    : {}
                            "
                        >
                            <span
                                class="text-white font-bold text-lg sm:text-xl lg:text-2xl bg-black/30 backdrop-blur-sm rounded-full w-full h-full flex items-center justify-center"
                            >
                                {{ getInitials(authStore.user?.name || "") }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Personal Information Card -->
                <div class="lg:col-span-2">
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div
                            class="px-6 py-5 border-b border-gray-100 bg-gray-50"
                        >
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-lg font-semibold text-gray-900"
                                    >
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
                            <div
                                class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-2 sm:mb-0"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-500 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        Nom complet
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ authStore.user?.name || "Non défini" }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div
                                class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-2 sm:mb-0"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-500 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        Adresse email
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ authStore.user?.email || "Non défini" }}
                                </div>
                            </div>

                            <!-- Role -->
                            <div
                                class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-2 sm:mb-0"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-500 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        Rôle
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                                    >
                                        {{ formatRole(authStore.user?.role) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Member Since -->
                            <div
                                class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-2 sm:mb-0"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-500 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        Membre depuis
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 font-medium">
                                    {{ formatDate(authStore.user?.created_at) }}
                                </div>
                            </div>

                            <!-- Productivity Score -->
                            <div
                                class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-2 sm:mb-0"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-500 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        Score de productivité
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="
                                            getProductivityColorClass(
                                                profileStore.productivityScore,
                                            )
                                        "
                                    >
                                        {{
                                            profileStore.productivityScore.toFixed(
                                                1,
                                            )
                                        }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div
                            class="px-6 py-5 border-t border-gray-100 bg-gray-50"
                        >
                            <div
                                class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3"
                            >
                                <button
                                    @click="editProfile"
                                    class="flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                    Modifier le profil
                                </button>
                                <button
                                    @click="changePassword"
                                    class="flex items-center justify-center px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg border border-gray-300 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                    Changer le mot de passe
                                </button>
                                <button
                                    @click="refreshStatistics"
                                    :disabled="profileStore.isLoading"
                                    class="flex items-center justify-center px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg border border-gray-300 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        :class="{
                                            'animate-spin':
                                                profileStore.isLoading,
                                        }"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                    Actualiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="lg:col-span-1">
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full"
                    >
                        <!-- Card Header -->
                        <div
                            class="px-6 py-5 border-b border-gray-100 bg-gray-50"
                        >
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-10 h-10 bg-secondary rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-lg font-semibold text-gray-900"
                                    >
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
                            <div
                                class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-100"
                            >
                                <div
                                    class="w-12 h-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center mr-4"
                                >
                                    <svg
                                        class="w-5 h-5 text-secondary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="text-2xl font-bold text-gray-900"
                                    >
                                        {{ profileStore.totalTasksCreated }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Tâches créées
                                    </div>
                                </div>
                            </div>

                            <!-- Tasks Completed -->
                            <div
                                class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-100"
                            >
                                <div
                                    class="w-12 h-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center mr-4"
                                >
                                    <svg
                                        class="w-5 h-5 text-secondary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="text-2xl font-bold text-gray-900"
                                        :class="
                                            getCompletionColorClass(
                                                profileStore.completionRate,
                                            )
                                        "
                                    >
                                        {{ profileStore.completedTasks }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Tâches terminées
                                        <span
                                            class="ml-1"
                                            :class="
                                                getCompletionColorClass(
                                                    profileStore.completionRate,
                                                )
                                            "
                                        >
                                            ({{
                                                profileStore.completionRate.toFixed(
                                                    1,
                                                )
                                            }}%)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Time Worked -->
                            <div
                                class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-100"
                            >
                                <div
                                    class="w-12 h-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center mr-4"
                                >
                                    <svg
                                        class="w-5 h-5 text-secondary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="text-2xl font-bold text-gray-900"
                                    >
                                        {{
                                            formatHours(
                                                profileStore.totalWorkHours,
                                            )
                                        }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Temps travaillé
                                    </div>
                                </div>
                            </div>

                            <!-- Period Info -->
                            <div
                                v-if="profileStore.workStatistics"
                                class="mt-6 pt-6 border-t border-gray-200"
                            >
                                <div class="text-sm text-gray-600 mb-2">
                                    Période :
                                    {{
                                        formatPeriod(
                                            profileStore.workStatistics.period,
                                        )
                                    }}
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center">
                                        <div
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            {{
                                                profileStore.workStatistics
                                                    .work_statistics.total_days
                                            }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Jours travaillés
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            {{
                                                formatHours(
                                                    profileStore.workStatistics
                                                        .work_statistics
                                                        .average_daily_hours,
                                                )
                                            }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Moyenne par jour
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Overview -->
            <!-- Tasks Overview -->
            <div class="mb-8">
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                >
                    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Vue d'ensemble des tâches
                        </h2>
                        <p class="text-sm text-gray-600">
                            Vos tâches assignées et leur statut
                        </p>
                    </div>
                    <div class="p-6">
                        <!-- Status Distribution -->
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">
                                Répartition par statut
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="(
                                        count, status
                                    ) in profileStore.taskByStatus"
                                    :key="status"
                                    class="flex items-center px-3 py-1.5 rounded-full text-xs font-medium"
                                    :class="getStatusColor(status)"
                                >
                                    <span class="mr-1.5"
                                        >{{ getStatusLabel(status) }}:</span
                                    >
                                    <span class="font-bold">{{ count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Priority Distribution -->
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">
                                Répartition par priorité
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="(
                                        count, priority
                                    ) in profileStore.taskByPriority"
                                    :key="priority"
                                    class="flex items-center px-3 py-1.5 rounded-full text-xs font-medium"
                                    :class="getPriorityColor(priority)"
                                >
                                    <span class="mr-1.5"
                                        >{{ getPriorityLabel(priority) }}:</span
                                    >
                                    <span class="font-bold">{{ count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Task List -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-3">
                                Liste des tâches assignées
                                <span class="text-gray-500 font-normal ml-1">
                                    ({{ profileStore.assignedTasks.length }})
                                </span>
                            </h3>

                            <!-- Empty State -->
                            <div
                                v-if="profileStore.assignedTasks.length === 0"
                                class="text-center py-8"
                            >
                                <div class="mx-auto w-12 h-12 mb-4">
                                    <svg
                                        class="w-full h-full text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                        />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">
                                    Aucune tâche assignée pour cette période
                                </p>
                            </div>

                            <!-- Task Items -->
                            <div
                                v-else
                                class="space-y-3 max-h-96 overflow-y-auto pr-2"
                            >
                                <div
                                    v-for="task in profileStore.assignedTasks"
                                    :key="task.id"
                                    class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors duration-150"
                                >
                                    <!-- Status Indicator -->
                                    <div
                                        class="flex-shrink-0 w-3 h-3 rounded-full mt-1.5 mr-3"
                                        :class="getStatusDotColor(task.status)"
                                    ></div>

                                    <!-- Task Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Task Title -->
                                        <div
                                            class="flex items-start justify-between mb-1"
                                        >
                                            <h4
                                                class="text-sm font-medium text-gray-900 truncate"
                                                :title="task.title"
                                            >
                                                {{ task.title }}
                                            </h4>
                                            <span
                                                class="ml-2 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                :class="
                                                    getPriorityBadgeColor(
                                                        task.priority,
                                                    )
                                                "
                                            >
                                                {{
                                                    getPriorityLabel(
                                                        task.priority,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <!-- Task Details -->
                                        <div
                                            class="flex flex-wrap items-center gap-3 text-xs text-gray-500"
                                        >
                                            <!-- Status -->
                                            <div class="flex items-center">
                                                <svg
                                                    class="w-3 h-3 mr-1"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                                    />
                                                </svg>
                                                <span>{{
                                                    getStatusLabel(task.status)
                                                }}</span>
                                            </div>

                                            <!-- Progress -->
                                            <div class="flex items-center">
                                                <svg
                                                    class="w-3 h-3 mr-1"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                                    />
                                                </svg>
                                                <span
                                                    >Progrès :
                                                    {{ task.progress }}%</span
                                                >
                                            </div>

                                            <!-- Due Date -->
                                            <div
                                                v-if="task.due_date"
                                                class="flex items-center"
                                                :class="
                                                    task.is_overdue
                                                        ? 'text-red-500'
                                                        : ''
                                                "
                                            >
                                                <svg
                                                    class="w-3 h-3 mr-1"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                    />
                                                </svg>
                                                <span>
                                                    Échéance :
                                                    {{
                                                        formatShortDate(
                                                            task.due_date,
                                                        )
                                                    }}
                                                    <span
                                                        v-if="task.is_overdue"
                                                        class="ml-1 font-semibold"
                                                    >
                                                        (En retard)
                                                    </span>
                                                </span>
                                            </div>

                                            <!-- Created Date -->
                                            <div class="flex items-center">
                                                <svg
                                                    class="w-3 h-3 mr-1"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                <span
                                                    >Créée :
                                                    {{
                                                        formatShortDate(
                                                            task.created_at,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="mt-2">
                                            <div
                                                class="w-full bg-gray-200 rounded-full h-1.5"
                                            >
                                                <div
                                                    class="h-1.5 rounded-full transition-all duration-300"
                                                    :class="
                                                        getProgressBarColor(
                                                            task.progress,
                                                        )
                                                    "
                                                    :style="{
                                                        width: `${task.progress}%`,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div
                v-if="profileStore.isLoading"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            >
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="flex items-center space-x-4">
                        <svg
                            class="animate-spin h-8 w-8 text-primary"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span class="text-gray-700"
                            >Chargement des données...</span
                        >
                    </div>
                </div>
            </div>
        </div>
        <ChangePasswordModal
            :isOpen="showChangePasswordModal"
            @close="closeChangePasswordModal"
            @success="handlePasswordChangeSuccess"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useProfileStore } from "@/stores/profile";
import ProfileService from "@/services/profile.service";
import ChangePasswordModal from "../../components/profile/ChangePasswordModal.vue";

const authStore = useAuthStore();
const profileStore = useProfileStore();

// State pour le modal
const showChangePasswordModal = ref(false);

// Fonctions utilitaires existantes
const getInitials = ProfileService.getInitials;
const formatDate = ProfileService.formatDate;
const formatRole = ProfileService.formatRole;
const formatHours = ProfileService.formatHours;

// Nouvelle fonction pour formater les dates courtes
const formatShortDate = (dateString: string): string => {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

const getProductivityColorClass = (score: number): string => {
    if (score >= 80) return "bg-green-100 text-green-800";
    if (score >= 60) return "bg-yellow-100 text-yellow-800";
    if (score >= 40) return "bg-orange-100 text-orange-800";
    return "bg-red-100 text-red-800";
};

const getCompletionColorClass = (rate: number): string => {
    if (rate >= 80) return "text-green-600";
    if (rate >= 60) return "text-yellow-600";
    if (rate >= 40) return "text-orange-600";
    return "text-red-600";
};

const getStatusColor = (status: string): string => {
    const statusColors: Record<string, string> = {
        todo: "bg-blue-100 text-blue-800",
        doing: "bg-yellow-100 text-yellow-800",
        in_progress: "bg-orange-100 text-orange-800",
        done: "bg-green-100 text-green-800",
        to_validate: "bg-purple-100 text-purple-800",
        backlog: "bg-gray-100 text-gray-800",
    };
    return statusColors[status] || "bg-gray-100 text-gray-800";
};

// Nouvelle fonction pour les points de statut
const getStatusDotColor = (status: string): string => {
    const statusColors: Record<string, string> = {
        todo: "bg-blue-500",
        doing: "bg-yellow-500",
        in_progress: "bg-orange-500",
        done: "bg-green-500",
        to_validate: "bg-purple-500",
        backlog: "bg-gray-500",
    };
    return statusColors[status] || "bg-gray-500";
};

const getStatusLabel = (status: string): string => {
    const statusLabels: Record<string, string> = {
        todo: "À faire",
        doing: "En cours",
        in_progress: "En progression",
        done: "Terminé",
        to_validate: "À valider",
        backlog: "Backlog",
    };
    return statusLabels[status] || status;
};

const getPriorityColor = (priority: string): string => {
    const priorityColors: Record<string, string> = {
        low: "bg-green-100 text-green-800",
        medium: "bg-yellow-100 text-yellow-800",
        high: "bg-red-100 text-red-800",
    };
    return priorityColors[priority] || "bg-gray-100 text-gray-800";
};

// Nouvelle fonction pour les badges de priorité
const getPriorityBadgeColor = (priority: string): string => {
    const priorityColors: Record<string, string> = {
        low: "bg-green-100 text-green-800",
        medium: "bg-yellow-100 text-yellow-800",
        high: "bg-red-100 text-red-800",
    };
    return priorityColors[priority] || "bg-gray-100 text-gray-800";
};

const getPriorityLabel = (priority: string): string => {
    const priorityLabels: Record<string, string> = {
        low: "Basse",
        medium: "Moyenne",
        high: "Haute",
    };
    return priorityLabels[priority] || priority;
};

// Nouvelle fonction pour les barres de progression
const getProgressBarColor = (progress: number): string => {
    if (progress >= 100) return "bg-green-500";
    if (progress >= 80) return "bg-green-400";
    if (progress >= 60) return "bg-yellow-500";
    if (progress >= 40) return "bg-orange-500";
    if (progress >= 20) return "bg-yellow-400";
    return "bg-red-500";
};

const formatPeriod = (period: {
    type: string;
    start_date: string;
    end_date: string;
}): string => {
    const periodLabels: Record<string, string> = {
        month: "Ce mois",
        week: "Cette semaine",
        year: "Cette année",
        all: "Tout le temps",
        today: "Aujourd'hui",
    };

    const label = periodLabels[period.type] || period.type;
    return `${label} (${formatDate(period.start_date)} - ${formatDate(period.end_date)})`;
};

// Actions
const editProfile = () => {
    console.log("Edit profile");
    // Implémentez la logique d'édition
};

const changePassword = () => {
    showChangePasswordModal.value = true;
};

const closeChangePasswordModal = () => {
    showChangePasswordModal.value = false;
};

const handlePasswordChangeSuccess = () => {
    // Optionnel : Afficher une notification de succès
    console.log("Password changed successfully");
    // Vous pouvez ajouter une notification toast ici
};

const refreshStatistics = async () => {
    try {
        await profileStore.refreshStatistics();
    } catch (error) {
        console.error("Error refreshing statistics:", error);
    }
};

// Charger les données au montage du composant
onMounted(async () => {
    try {
        await profileStore.fetchWorkStatistics();
    } catch (error) {
        console.error("Error loading work statistics:", error);
    }
});
</script>

<style src="./profile.css" scoped></style>
