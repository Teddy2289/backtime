<template>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header-container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="page-title">Tableau de bord</h1>
                    <p class="page-subtitle">
                        Gérez vos utilisateurs et consultez les statistiques
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-main-grid">
            <!-- Left Column - Analytics -->
            <div class="dashboard-left-column">
                <!-- Row 1: Overview -->
                <div class="dashboard-row">
                    <div class="dashboard-card">
                        <OverviewStats />
                    </div>
                </div>

                <!-- Row 2: Task Stats + Weekly Analytics -->
                <div class="dashboard-row">
                    <div class="dashboard-card">
                        <TaskStats />
                    </div>
                    <div class="dashboard-card">
                        <WeeklyAnalytics />
                    </div>
                </div>

                <!-- Row 3: Recent Activity -->
                <div class="dashboard-row">
                    <div class="dashboard-card">
                        <RecentActivity />
                    </div>
                </div>
            </div>

            <!-- Right Column - Users & Top Performers -->
            <div class="dashboard-right-column">
                <!-- User Management -->
                <div class="dashboard-card user-management-card">
                    <div class="section-header">
                        <h2 class="section-title">Gestion des utilisateurs</h2>
                        <div class="filters-container">
                            <div class="search-wrapper">
                                <MagnifyingGlassIcon class="search-icon" />
                                <input
                                    v-model="searchQuery"
                                    @input="debouncedSearch"
                                    type="text"
                                    placeholder="Rechercher un utilisateur..."
                                    class="search-input"
                                />
                            </div>
                            <div class="filter-group">
                                <select
                                    v-model="roleFilter"
                                    @change="onRoleFilterChange"
                                    class="filter-select"
                                >
                                    <option value="">Tous les rôles</option>
                                    <option value="admin">
                                        Administrateur
                                    </option>
                                    <option value="manager">Manager</option>
                                    <option value="user">Utilisateur</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div
                        v-if="userStore.loading && !userStore.users.length"
                        class="loading-state"
                    >
                        <div class="loading-spinner"></div>
                        <p class="loading-text">
                            Chargement des utilisateurs...
                        </p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="userStore.error" class="error-state">
                        <ExclamationCircleIcon class="error-icon" />
                        <div class="error-content">
                            <h3 class="error-title">Erreur de chargement</h3>
                            <p class="error-message">{{ userStore.error }}</p>
                            <button
                                @click="userStore.fetchUsers()"
                                class="retry-button"
                            >
                                Réessayer
                            </button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else-if="
                            userStore.users.length === 0 && !userStore.loading
                        "
                        class="empty-state"
                    >
                        <FolderIcon class="empty-icon" />
                        <h3 class="empty-title">Aucun utilisateur</h3>
                        <p class="empty-message">
                            Commencez par créer votre premier utilisateur.
                        </p>
                        <button
                            v-if="authStore.isAdmin"
                            @click="openCreateModal"
                            class="empty-action-button"
                        >
                            Créer un utilisateur
                        </button>
                    </div>

                    <!-- Users Table -->
                    <div v-else class="table-responsive">
                        <table class="users-table compact">
                            <thead>
                                <tr>
                                    <th class="table-header">Utilisateur</th>
                                    <th class="table-header">Rôle</th>
                                    <th class="table-header actions-header">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="user in displayedUsers"
                                    :key="user.id"
                                    class="table-row"
                                >
                                    <td class="table-cell user-cell">
                                        <div class="user-info">
                                            <div class="avatar-container">
                                                <div
                                                    v-if="user.avatar"
                                                    class="user-avatar"
                                                    :style="{
                                                        backgroundImage: `url(${user.avatar})`,
                                                    }"
                                                ></div>
                                                <div
                                                    v-else
                                                    class="user-avatar placeholder"
                                                >
                                                    {{ user.initials }}
                                                </div>
                                            </div>
                                            <div class="user-details">
                                                <span class="user-name">{{
                                                    user.name
                                                }}</span>
                                                <span class="user-email">{{
                                                    user.email
                                                }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        <span
                                            class="role-badge"
                                            :class="roleBadgeClasses(user.role)"
                                        >
                                            {{ getRoleLabel(user.role) }}
                                        </span>
                                    </td>
                                    <td class="table-cell actions-cell">
                                        <div class="table-actions">
                                            <button
                                                @click="viewWorkStats(user)"
                                                class="table-action-button stats"
                                                title="Statistiques de travail"
                                                :disabled="
                                                    isLoadingWorkStats(user.id)
                                                "
                                            >
                                                <ChartBarIcon />
                                            </button>
                                            <button
                                                @click="editUser(user)"
                                                class="table-action-button edit"
                                                title="Modifier"
                                            >
                                                <PencilSquareIcon />
                                            </button>
                                            <button
                                                v-if="
                                                    authStore.isAdmin &&
                                                    user.id !==
                                                        authStore.user?.id
                                                "
                                                @click="deleteUser(user)"
                                                class="table-action-button delete"
                                                title="Supprimer"
                                            >
                                                <TrashIcon />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- View All Users -->
                        <div
                            v-if="userStore.users.length > 5"
                            class="view-all-container"
                        >
                            <router-link
                                to="/admin/users"
                                class="view-all-link"
                            >
                                Voir tous les
                                {{ userStore.totalUsers }} utilisateurs →
                            </router-link>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <!-- <div class="dashboard-card">
                    <TopPerformers />
                </div> -->
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="userStore.totalUsers > 0 && userStore.users.length > 5"
            class="pagination-container"
        >
            <button
                @click="goToPreviousPage"
                :disabled="userStore.currentPage === 1"
                class="pagination-button prev-button"
            >
                <ChevronLeftIcon />
                Précédent
            </button>

            <div class="pagination-info">
                Page
                <span class="current-page">{{ userStore.currentPage }}</span>
                sur {{ userStore.lastPage }}
                <br />
                Affichage de {{ userStore.from }} à {{ userStore.to }} sur
                {{ userStore.totalUsers }} utilisateurs
            </div>

            <button
                @click="goToNextPage"
                :disabled="userStore.currentPage === userStore.lastPage"
                class="pagination-button next-button"
            >
                Suivant
                <ChevronRightIcon />
            </button>
        </div>

        <!-- Work Stats Modal -->
        <div
            v-if="showWorkStatsModal"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div
                class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                @click="closeWorkStatsModal"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden"
                >
                    <!-- Header -->
                    <div
                        class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">
                                    Statistiques de travail
                                </h2>
                                <p class="text-xs text-gray-600 mt-1">
                                    {{ selectedUserForStats?.name }}
                                </p>
                            </div>
                            <button
                                @click="closeWorkStatsModal"
                                class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                <XMarkIcon class="w-5 h-5 text-gray-500" />
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <!-- Loading State -->
                        <div
                            v-if="workStatsLoading"
                            class="flex flex-col items-center justify-center py-12"
                        >
                            <div
                                class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"
                            ></div>
                            <p class="text-gray-600">
                                Chargement des statistiques...
                            </p>
                        </div>

                        <!-- Error State -->
                        <div
                            v-else-if="workStatsError"
                            class="flex flex-col items-center justify-center py-12"
                        >
                            <ExclamationTriangleIcon
                                class="w-16 h-16 text-red-500 mb-4"
                            />
                            <h3
                                class="text-sm font-semibold text-gray-900 mb-2"
                            >
                                Erreur de chargement
                            </h3>
                            <p class="text-gray-600 text-center max-w-md">
                                {{ workStatsError }}
                            </p>
                        </div>

                        <!-- Stats Content -->
                        <div v-else-if="workStatsData">
                            <!-- User Info -->
                            <div
                                class="flex flex-col sm:flex-row items-center gap-6 p-6 bg-gradient-to-r from-gray-50 to-indigo-50 rounded-2xl mb-8"
                            >
                                <div class="relative">
                                    <div
                                        v-if="selectedUserForStats?.avatar_url"
                                        class="w-20 h-20 rounded-full bg-cover bg-center border-4 border-white shadow-lg"
                                        :style="{
                                            backgroundImage: `url(${selectedUserForStats.avatar_url})`,
                                        }"
                                    ></div>
                                    <div
                                        v-else
                                        class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-2xl border-4 border-white shadow-lg"
                                    >
                                        {{ selectedUserForStats?.initials }}
                                    </div>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h3
                                        class="text-2xl font-bold text-gray-900"
                                    >
                                        {{ selectedUserForStats?.name }}
                                    </h3>
                                    <p class="text-gray-600 mt-1">
                                        {{ selectedUserForStats?.email }}
                                    </p>
                                    <div class="mt-3">
                                        <span
                                            :class="[
                                                'px-4 py-1.5 rounded-full text-xs font-semibold',
                                                selectedUserForStats?.role ===
                                                'admin'
                                                    ? 'bg-red-100 text-red-800'
                                                    : selectedUserForStats?.role ===
                                                        'manager'
                                                      ? 'bg-blue-100 text-blue-800'
                                                      : 'bg-gray-100 text-gray-800',
                                            ]"
                                        >
                                            {{
                                                getRoleLabel(
                                                    selectedUserForStats?.role ||
                                                        "user",
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"
                            >
                                <!-- Today Stats -->
                                <div
                                    class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="p-3 bg-blue-50 rounded-xl">
                                            <CalendarIcon
                                                class="w-6 h-6 text-blue-600"
                                            />
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900">
                                                Aujourd'hui
                                            </h4>
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    new Date().toLocaleDateString(
                                                        "fr-FR",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <div
                                                class="flex justify-between items-baseline mb-1"
                                            >
                                                <span
                                                    class="text-xs text-gray-600"
                                                    >Total</span
                                                >
                                                <span
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        formatSecondsToHours(
                                                            workStatsData
                                                                .work_time_stats
                                                                ?.today
                                                                ?.total_seconds ||
                                                                0,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="h-2 bg-gray-100 rounded-full overflow-hidden"
                                            >
                                                <div
                                                    class="h-full bg-blue-500 rounded-full"
                                                    :style="{ width: '100%' }"
                                                ></div>
                                            </div>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100"
                                        >
                                            <div class="text-center">
                                                <p
                                                    class="text-xs font-semibold text-gray-900"
                                                >
                                                    {{
                                                        formatSecondsToHours(
                                                            workStatsData
                                                                .work_time_stats
                                                                ?.today
                                                                ?.net_seconds ||
                                                                0,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Net
                                                </p>
                                            </div>
                                            <div class="text-center">
                                                <p
                                                    class="text-xs font-semibold text-gray-900"
                                                >
                                                    {{
                                                        workStatsData
                                                            .work_time_stats
                                                            ?.today?.status ||
                                                        "N/A"
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Statut
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Weekly Stats -->
                                <div
                                    class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-center gap-4 mb-6">
                                        <div
                                            class="p-3 bg-emerald-50 rounded-xl"
                                        >
                                            <CalendarDaysIcon
                                                class="w-6 h-6 text-emerald-600"
                                            />
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900">
                                                Hebdomadaire
                                            </h4>
                                            <p class="text-xs text-gray-500">
                                                Cette semaine
                                            </p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <div
                                                class="flex justify-between items-baseline mb-1"
                                            >
                                                <span
                                                    class="text-xs text-gray-600"
                                                    >Total</span
                                                >
                                                <span
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        formatSecondsToHours(
                                                            workStatsData
                                                                .work_time_stats
                                                                ?.weekly
                                                                ?.total_seconds ||
                                                                0,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="h-2 bg-gray-100 rounded-full overflow-hidden"
                                            >
                                                <div
                                                    class="h-full bg-emerald-500 rounded-full"
                                                    :style="{
                                                        width: `${Math.min(
                                                            (workStatsData
                                                                .work_time_stats
                                                                ?.weekly
                                                                ?.days_worked ||
                                                                0) * 20,
                                                            100,
                                                        )}%`,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100"
                                        >
                                            <div class="text-center">
                                                <p
                                                    class="text-xs font-semibold text-gray-900"
                                                >
                                                    {{
                                                        workStatsData
                                                            .work_time_stats
                                                            ?.weekly
                                                            ?.days_worked || 0
                                                    }}/5
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Jours
                                                </p>
                                            </div>
                                            <div class="text-center">
                                                <p
                                                    class="text-xs font-semibold text-gray-900"
                                                >
                                                    {{
                                                        formatHours(
                                                            workStatsData
                                                                .work_time_stats
                                                                ?.weekly
                                                                ?.average_daily_hours ||
                                                                0,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Moyenne/jour
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Monthly Stats -->
                                <div
                                    class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="p-3 bg-amber-50 rounded-xl">
                                            <CalendarIcon
                                                class="w-6 h-6 text-amber-600"
                                            />
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900">
                                                Mensuel
                                            </h4>
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    new Date().toLocaleDateString(
                                                        "fr-FR",
                                                        {
                                                            month: "long",
                                                            year: "numeric",
                                                        },
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <div
                                                class="flex justify-between items-baseline mb-1"
                                            >
                                                <span
                                                    class="text-xs text-gray-600"
                                                    >Total</span
                                                >
                                                <span
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        formatSecondsToHours(
                                                            workStatsData
                                                                .work_time_stats
                                                                ?.monthly
                                                                ?.total_seconds ||
                                                                0,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="h-2 bg-gray-100 rounded-full overflow-hidden"
                                            >
                                                <div
                                                    class="h-full bg-amber-500 rounded-full"
                                                    :style="{
                                                        width: `${Math.min(
                                                            (workStatsData
                                                                .work_time_stats
                                                                ?.monthly
                                                                ?.days_worked ||
                                                                0) * 4,
                                                            100,
                                                        )}%`,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100"
                                        >
                                            <div class="text-center">
                                                <p
                                                    class="text-xs font-semibold text-gray-900"
                                                >
                                                    {{
                                                        workStatsData
                                                            .work_time_stats
                                                            ?.monthly
                                                            ?.days_worked || 0
                                                    }}/22
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Jours
                                                </p>
                                            </div>
                                            <div class="text-center">
                                                <p
                                                    class="text-xs font-semibold text-gray-900"
                                                >
                                                    {{
                                                        formatHours(
                                                            workStatsData
                                                                .work_time_stats
                                                                ?.monthly
                                                                ?.average_daily_hours ||
                                                                0,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Moyenne/jour
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Sessions -->
                            <div
                                v-if="workStatsData.recent_sessions?.length > 0"
                                class="bg-white rounded-xl border border-gray-200 overflow-hidden"
                            >
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h3
                                        class="text-sm font-bold text-gray-900 flex items-center gap-2"
                                    >
                                        <ClockIcon
                                            class="w-5 h-5 text-gray-400"
                                        />
                                        Sessions récentes
                                    </h3>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <div
                                        v-for="(
                                            session, index
                                        ) in workStatsData.recent_sessions"
                                        :key="session.id"
                                        class="p-4 hover:bg-gray-50 transition-colors"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    :class="[
                                                        'flex-shrink-0 w-3 h-3 rounded-full',
                                                        session.type === 'work'
                                                            ? 'bg-indigo-500'
                                                            : 'bg-amber-500',
                                                    ]"
                                                ></div>
                                                <div>
                                                    <span
                                                        :class="[
                                                            'px-3 py-1 rounded-full text-xs font-semibold',
                                                            session.type ===
                                                            'work'
                                                                ? 'bg-indigo-100 text-indigo-800'
                                                                : 'bg-amber-100 text-amber-800',
                                                        ]"
                                                    >
                                                        {{
                                                            session.type ===
                                                            "work"
                                                                ? "Travail"
                                                                : "Pause"
                                                        }}
                                                    </span>
                                                    <p
                                                        class="text-xs text-gray-900 font-medium mt-1"
                                                    >
                                                        {{
                                                            session.session_start
                                                        }}
                                                        →
                                                        {{
                                                            session.session_end ||
                                                            "En cours"
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-gray-500"
                                                    >
                                                        Durée:
                                                        {{
                                                            formatSecondsToHours(
                                                                Math.abs(
                                                                    session.duration_seconds,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p
                                                    class="text-xs font-medium text-gray-900"
                                                >
                                                    {{ session.work_date }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Session
                                                    {{ Number(index) + 1 }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        workStatsData.recent_sessions.length > 3
                                    "
                                    class="px-6 py-3 border-t border-gray-200 bg-gray-50"
                                >
                                    <p
                                        class="text-xs text-gray-600 text-center"
                                    >
                                        Affichage des
                                        {{
                                            Math.min(
                                                workStatsData.recent_sessions
                                                    .length,
                                                5,
                                            )
                                        }}
                                        sessions les plus récentes
                                    </p>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div
                                v-if="workStatsData"
                                class="mt-4 bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-4 text-white mb-4"
                            >
                                <div
                                    class="flex flex-col md:flex-row md:items-center justify-between gap-6"
                                >
                                    <div>
                                        <h4 class="text-xs font-bold mb-2">
                                            Résumé des performances
                                        </h4>
                                        <p class="text-gray-300 text-xs">
                                            Données mises à jour le
                                            {{
                                                new Date().toLocaleDateString(
                                                    "fr-FR",
                                                    {
                                                        weekday: "long",
                                                        year: "numeric",
                                                        month: "long",
                                                        day: "numeric",
                                                    },
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-4 gap-4"
                                    >
                                        <div class="text-center">
                                            <p class="text-xl font-bold">
                                                {{
                                                    workStatsData
                                                        .work_time_stats?.weekly
                                                        ?.days_worked || 0
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-300">
                                                Jours/semaine
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xl font-bold">
                                                {{
                                                    formatSecondsToHours(
                                                        workStatsData
                                                            .work_time_stats
                                                            ?.weekly
                                                            ?.total_seconds ||
                                                            0,
                                                    )
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-300">
                                                Heures/semaine
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xl font-bold">
                                                {{
                                                    formatHours(
                                                        workStatsData
                                                            .work_time_stats
                                                            ?.weekly
                                                            ?.average_daily_hours ||
                                                            0,
                                                    )
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-300">
                                                Moyenne/jour
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xl font-bold">
                                                {{
                                                    workStatsData
                                                        .work_time_stats?.today
                                                        ?.status || "N/A"
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-300">
                                                Statut actuel
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="sticky bottom-0 bg-white border-t border-gray-200 px-4 py-2"
                    >
                        <div class="flex justify-between items-center">
                            <div class="text-xs text-gray-600">
                                Données en temps réel • Dernière mise à jour: il
                                y a quelques secondes
                            </div>
                            <div class="flex gap-3">
                                <button
                                    @click="closeWorkStatsModal"
                                    class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    Fermer
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
import { computed, ref, onMounted, watch, reactive } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useUserStore } from "@/stores/user.store";
import { getRoleLabel } from "@/enums/user-role";
import debounce from "lodash/debounce";
import type { User } from "@/types/User";

import {
    MagnifyingGlassIcon,
    ExclamationCircleIcon,
    FolderIcon,
    CalendarDaysIcon,
    CalendarIcon,
    PencilSquareIcon,
    TrashIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
    ClockIcon,
} from "@heroicons/vue/24/outline";

import OverviewStats from "@/components/dashboard/OverviewStats.vue";
import TaskStats from "@/components/dashboard/TaskStats.vue";
import RecentActivity from "@/components/dashboard/RecentActivity.vue";
import WeeklyAnalytics from "@/components/dashboard/WeeklyAnalytics.vue";
import { useDashboardStore } from "@/stores/dashboard.store";

const authStore = useAuthStore();
const userStore = useUserStore();
const dashboardStore = useDashboardStore();

// State
const searchQuery = ref("");
const roleFilter = ref("");

// Modal State
const showModal = ref(false);
const isEditMode = ref(false);
const modalLoading = ref(false);
const modalError = ref<string | null>(null);
const showPasswordField = ref(false);
const showPassword = ref(false);
const selectedUserId = ref<string | number | null>(null);
const showWorkStatsModal = ref(false);
const selectedUserForStats = ref<User | null>(null);
const workStatsData = ref<any>(null);
const workStatsLoading = ref(false);
const workStatsError = ref<string | null>(null);

const displayedUsers = computed(() => {
    return userStore.users.slice(0, 5);
});

const roleBadgeClasses = (role: string) => {
    switch (role) {
        case "admin":
            return "admin-badge";
        case "manager":
            return "manager-badge";
        case "user":
            return "user-badge";
        default:
            return "user-badge";
    }
};

const openCreateModal = () => {
    isEditMode.value = false;
    resetForm();
    showModal.value = true;
};

const editUser = (user: User) => {
    isEditMode.value = true;
    selectedUserId.value = user.id;
    resetForm();

    // Fill form with user data
    formData.name = user.name;
    formData.email = user.email;
    formData.role = user.role;
    formData.avatar = user.avatar || "";
    formData.email_verified = !!user.email_verified_at;

    showModal.value = true;
};

// Form Data
const formData = reactive({
    name: "",
    email: "",
    role: "user",
    password: "",
    password_confirmation: "",
    avatar: "",
    email_verified: false,
});

// Form Errors
const errors = reactive({
    name: "",
    email: "",
    role: "",
    password: "",
    password_confirmation: "",
    avatar: "",
});

// Fonction helper pour typer l'accès
const resetForm = () => {
    // Type safe reset avec casting
    const formKeys = Object.keys(formData) as Array<keyof typeof formData>;
    formKeys.forEach((key) => {
        if (key === "role") {
            formData[key] = "user";
        } else if (key === "email_verified") {
            formData[key] = false;
        } else if (typeof formData[key] === "string") {
            (formData[key] as string) = "";
        }
    });

    const errorKeys = Object.keys(errors) as Array<keyof typeof errors>;
    errorKeys.forEach((key) => {
        errors[key] = "";
    });

    modalError.value = null;
    showPasswordField.value = false;
    showPassword.value = false;
    modalLoading.value = false;
};

const deleteUser = async (user: any) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.name} ?`)) {
        await userStore.deleteUser(user.id);
    }
};

const goToPreviousPage = () => {
    if (userStore.currentPage > 1) {
        userStore.changePage(userStore.currentPage - 1);
    }
};

const goToNextPage = () => {
    if (userStore.currentPage < userStore.lastPage) {
        userStore.changePage(userStore.currentPage + 1);
    }
};

const onRoleFilterChange = () => {
    userStore.filterByRole(roleFilter.value);
};

const viewWorkStats = async (user: User) => {
    selectedUserForStats.value = user;
    workStatsLoading.value = true;
    workStatsError.value = null;
    showWorkStatsModal.value = true;

    try {
        const data = await dashboardStore.fetchUserWorkTime(user.id);
        workStatsData.value = data;
    } catch (error: any) {
        workStatsError.value =
            error.message || "Erreur lors du chargement des statistiques";
    } finally {
        workStatsLoading.value = false;
    }
};

const isLoadingWorkStats = (userId: number) => {
    return dashboardStore.isUserWorkTimeLoading(userId);
};

const closeWorkStatsModal = () => {
    showWorkStatsModal.value = false;
    selectedUserForStats.value = null;
    workStatsData.value = null;
    workStatsError.value = null;
};

const formatHours = (hours: number) => {
    return hours.toFixed(2).replace(".", ",") + "h";
};

const formatSecondsToHours = (seconds: number) => {
    const hours = seconds / 3600;
    return formatHours(hours);
};

// Debounced search
const debouncedSearch = debounce(() => {
    userStore.searchUsers(searchQuery.value);
}, 500);
// Lifecycle
onMounted(() => {
    userStore.fetchUsers();
});
// Watchers
watch(searchQuery, () => {
    debouncedSearch();
});
</script>
<style src="@/components/styles/dashboard.css" scoped></style>
