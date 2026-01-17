<template>
    <div class="min-h-screen bg-white p-6">
        <!-- Header -->
        <div class="mb-8">
            <!-- En-tête avec dégradé -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-primary/5 p-6 mb-8"
            >
                <div class="relative">
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between gap-6"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-white rounded-xl shadow-sm">
                                    <UserGroupIcon
                                        class="w-6 h-6 text-primary"
                                    />
                                </div>
                                <div>
                                    <h1
                                        class="text-2xl md:text-3xl font-bold text-gray-900"
                                    >
                                        Gestion des utilisateurs
                                    </h1>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Gérez tous les utilisateurs de
                                        l'application
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="openCreateModal"
                            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-secondary rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transform transition-all duration-200 min-w-[180px] hover:bg-secondary-dark"
                        >
                            <PlusIcon class="w-5 h-5 mr-2" />
                            <span>Nouvel utilisateur</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div
            class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow mb-6"
        >
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-4"
            >
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Rechercher</label
                    >
                    <div class="relative group">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <MagnifyingGlassIcon
                                class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors"
                            />
                        </div>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Nom, email, rôle..."
                            class="pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white"
                        />
                    </div>
                </div>

                <!-- Role Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Rôle</label
                    >
                    <select
                        v-model="roleFilter"
                        @change="onRoleFilterChange"
                        class="pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white"
                    >
                        <option value="">Tous les rôles</option>
                        <option value="admin">Administrateur</option>
                        <option value="manager">Manager</option>
                        <option value="user">Utilisateur</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Statut</label
                    >
                    <select
                        v-model="statusFilter"
                        @change="onStatusFilterChange"
                        class="pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                        <option value="verified">Vérifié</option>
                        <option value="unverified">Non vérifié</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Date d'inscription</label
                    >
                    <input
                        v-model="dateFilter"
                        type="date"
                        class="pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white"
                    />
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex justify-end gap-3">
                <button
                    @click="resetFilters"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    <ArrowPathIcon class="w-4 h-4" />
                    Réinitialiser
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div
                class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center"
                    >
                        <UserGroupIcon class="w-6 h-6 text-yellow-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ userStore.users.length }}
                        </p>
                        <p class="text-sm text-gray-600">Total utilisateurs</p>
                    </div>
                </div>
            </div>

            <!-- Admins -->
            <div
                class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center"
                    >
                        <ShieldCheckIcon class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">
                            {{
                                userStore.users.filter(
                                    (user) => user.role === "admin",
                                ).length
                            }}
                        </p>
                        <p class="text-sm text-gray-600">Administrateurs</p>
                    </div>
                </div>
            </div>

            <!-- Managers -->
            <div
                class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center"
                    >
                        <UserIcon class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">
                            {{
                                userStore.users.filter(
                                    (user) => user.role === "manager",
                                ).length
                            }}
                        </p>
                        <p class="text-sm text-gray-600">Managers</p>
                    </div>
                </div>
            </div>

            <!-- New Today -->
            <div
                class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center"
                    >
                        <CalendarIcon class="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ newTodayUsers }}
                        </p>
                        <p class="text-sm text-gray-600">
                            Nouveaux aujourd'hui
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Table Header -->
            <div
                class="p-6 border-b border-gray-200 flex justify-between items-center"
            >
                <h3 class="text-lg font-semibold text-gray-900">
                    Utilisateurs ({{ userStore.users.length }})
                </h3>
                <div class="flex items-center gap-3">
                    <button
                        @click="toggleSelectAll"
                        class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        {{
                            selectAll
                                ? "Désélectionner tout"
                                : "Sélectionner tout"
                        }}
                    </button>
                    <button
                        v-if="selectedUsers.length > 0"
                        @click="bulkActions"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        <EllipsisVerticalIcon class="w-5 h-5" />
                        Actions ({{ selectedUsers.length }})
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div
                v-if="userStore.loading && !userStore.users.length"
                class="p-12 text-center"
            >
                <div
                    class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"
                ></div>
                <p class="text-gray-600">Chargement des utilisateurs...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="userStore.error" class="p-12 text-center">
                <ExclamationCircleIcon
                    class="w-16 h-16 text-red-500 mx-auto mb-4"
                />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Erreur de chargement
                </h3>
                <p class="text-gray-600 mb-4">{{ userStore.error }}</p>
                <button
                    @click="userStore.fetchUsers()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Réessayer
                </button>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="userStore.users.length === 0 && !userStore.loading"
                class="p-12 text-center"
            >
                <UserGroupIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Aucun utilisateur trouvé
                </h3>
                <p class="text-gray-600 mb-4">
                    Aucun utilisateur ne correspond à vos critères.
                </p>
                <button
                    @click="resetFilters"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Réinitialiser les filtres
                </button>
            </div>

            <!-- Users Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12"
                            >
                                <input
                                    type="checkbox"
                                    v-model="selectAll"
                                    @change="handleSelectAll"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Utilisateur
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Rôle
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Statut
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Date d'inscription
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Dernière connexion
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="user in filteredUsers"
                            :key="user.id"
                            :class="[
                                'hover:bg-gray-50',
                                {
                                    'bg-blue-50': selectedUsers.includes(
                                        user.id,
                                    ),
                                },
                            ]"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :value="user.id"
                                    v-model="selectedUsers"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div
                                            v-if="user.avatar"
                                            class="h-10 w-10 rounded-full bg-cover bg-center"
                                            :style="{
                                                backgroundImage: `url(${user.avatar})`,
                                            }"
                                        ></div>
                                        <div
                                            v-else
                                            class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold"
                                        >
                                            {{ getInitials(user.name) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        user.role === 'admin'
                                            ? 'bg-red-100 text-red-800'
                                            : user.role === 'manager'
                                              ? 'bg-blue-100 text-blue-800'
                                              : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ getRoleLabel(user.role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        user.email_verified_at
                                            ? 'bg-green-100 text-green-800'
                                            : user.last_login_at
                                              ? 'bg-yellow-100 text-yellow-800'
                                              : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ getUserStatus(user) }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                            >
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                            >
                                {{ formatLastLogin(user.last_login_at) }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                            >
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="viewUser(user)"
                                        class="action-button-table view-button-table"
                                    >
                                        <EyeIcon class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="editUser(user)"
                                        class="action-button-table edit-button-table"
                                    >
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </button>
                                    <button
                                        v-if="
                                            authStore.isAdmin &&
                                            user.id !== authStore.user?.id
                                        "
                                        @click="deleteUser(user)"
                                        class="action-button-table delete-button-table"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="userStore.totalUsers > 0"
                class="px-6 py-4 border-t border-gray-200"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="text-sm text-gray-700 mb-4 sm:mb-0">
                        Affichage de {{ userStore.from }} à
                        {{ userStore.to }} sur
                        {{ userStore.totalUsers }} utilisateurs
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="goToPreviousPage"
                            :disabled="userStore.currentPage === 1"
                            :class="[
                                'px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium',
                                userStore.currentPage === 1
                                    ? 'text-gray-400 cursor-not-allowed'
                                    : 'text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            <ChevronLeftIcon class="w-4 h-4 inline mr-1" />
                            Précédent
                        </button>

                        <div class="flex items-center space-x-1">
                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm font-medium',
                                    page === userStore.currentPage
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                {{ page }}
                            </button>
                            <span v-if="showEllipsis" class="px-2 text-gray-500"
                                >...</span
                            >
                        </div>

                        <button
                            @click="goToNextPage"
                            :disabled="
                                userStore.currentPage === userStore.lastPage
                            "
                            :class="[
                                'px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium',
                                userStore.currentPage === userStore.lastPage
                                    ? 'text-gray-400 cursor-not-allowed'
                                    : 'text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            Suivant
                            <ChevronRightIcon class="w-4 h-4 inline ml-1" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <!-- Modal amélioré dans Users.vue -->
        <!-- User Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="closeModal"
            ></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden"
                >
                    <!-- Header avec gradient -->
                    <div
                        class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary/10 via-secondary/5 to-primary/5"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-primary">
                                    {{
                                        isEditMode
                                            ? "Modifier l'utilisateur"
                                            : "Nouvel utilisateur"
                                    }}
                                </h2>
                                <p class="text-sm text-dark mt-1">
                                    {{
                                        isEditMode
                                            ? "Mettez à jour les informations"
                                            : "Ajoutez un nouvel utilisateur"
                                    }}
                                </p>
                            </div>
                            <button
                                @click="closeModal"
                                class="text-dark hover:text-secondary transition-colors"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                        <!-- Loading State -->
                        <div v-if="modalLoading" class="py-10 text-center">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-4"
                            >
                                <div
                                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
                                ></div>
                            </div>
                            <p class="text-gray-600 font-medium">
                                {{
                                    isEditMode
                                        ? "Mise à jour..."
                                        : "Création..."
                                }}
                            </p>
                        </div>

                        <!-- Error State -->
                        <div
                            v-else-if="modalError"
                            class="mb-5 p-4 bg-red-50 border border-red-100 rounded-lg"
                        >
                            <div class="flex items-start">
                                <ExclamationCircleIcon
                                    class="w-5 h-5 text-red-500 mt-0.5 mr-2 flex-shrink-0"
                                />
                                <p class="text-sm text-red-700">
                                    {{ modalError }}
                                </p>
                            </div>
                        </div>

                        <!-- Form -->
                        <form
                            v-else
                            @submit.prevent="submitForm"
                            class="space-y-4"
                        >
                            <!-- Avatar Upload -->
                            <div class="flex flex-col items-center mb-4">
                                <div class="relative mb-3">
                                    <div
                                        v-if="
                                            avatarPreview ||
                                            (isEditMode &&
                                                selectedUser?.avatar_url)
                                        "
                                        class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-lg"
                                    >
                                        <img
                                            :src="
                                                (avatarPreview ||
                                                    selectedUser?.avatar) as string
                                            "
                                            class="w-full h-full object-cover"
                                            alt="Avatar preview"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="w-24 h-24 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center border-4 border-white shadow-lg"
                                    >
                                        <UserIcon
                                            class="w-10 h-10 text-gray-400"
                                        />
                                    </div>

                                    <label
                                        for="avatar-upload"
                                        class="absolute -bottom-2 -right-2 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-blue-700 transition-colors shadow-lg"
                                    >
                                        <CameraIcon class="w-5 h-5" />
                                        <input
                                            id="avatar-upload"
                                            @change="handleAvatarUpload"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                        />
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 text-center">
                                    Cliquez sur l'icône pour changer la photo
                                </p>
                            </div>

                            <!-- Name -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Nom complet *
                                </label>
                                <div class="relative">
                                    <UserIcon
                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                    />
                                    <input
                                        v-model="formData.name"
                                        type="text"
                                        :class="[
                                            'pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white',
                                            errors.name
                                                ? 'border-red-300 bg-red-50'
                                                : 'border-gray-300',
                                        ]"
                                        placeholder="Jean Dupont"
                                    />
                                </div>
                                <p
                                    v-if="errors.name"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ errors.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Email *
                                </label>
                                <div class="relative">
                                    <EnvelopeIcon
                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                    />
                                    <input
                                        v-model="formData.email"
                                        type="email"
                                        :class="[
                                            'pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white',
                                            errors.email
                                                ? 'border-red-300 bg-red-50'
                                                : 'border-gray-300',
                                        ]"
                                        placeholder="jean.dupont@example.com"
                                    />
                                </div>
                                <p
                                    v-if="errors.email"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ errors.email }}
                                </p>
                            </div>

                            <!-- Role -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Rôle *
                                </label>
                                <select
                                    v-model="formData.role"
                                    :class="[
                                        'pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white',
                                        errors.role
                                            ? 'border-red-300 bg-red-50'
                                            : 'border-gray-300',
                                    ]"
                                >
                                    <option value="">
                                        Sélectionner un rôle
                                    </option>
                                    <option value="user">Utilisateur</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">
                                        Administrateur
                                    </option>
                                </select>
                                <p
                                    v-if="errors.role"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ errors.role }}
                                </p>
                            </div>

                            <!-- Password Fields -->
                            <div
                                v-if="!isEditMode || showPasswordField"
                                class="space-y-3"
                            >
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        {{
                                            isEditMode
                                                ? "Nouveau mot de passe"
                                                : "Mot de passe"
                                        }}
                                        <span v-if="!isEditMode">*</span>
                                    </label>
                                    <div class="relative">
                                        <LockClosedIcon
                                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                        />
                                        <input
                                            v-model="formData.password"
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            :class="[
                                                'pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white',
                                                errors.password
                                                    ? 'border-red-300 bg-red-50'
                                                    : 'border-gray-300',
                                            ]"
                                            :placeholder="
                                                isEditMode
                                                    ? 'Laisser vide pour ne pas changer'
                                                    : '●●●●●●●●'
                                            "
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showPassword = !showPassword
                                            "
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <EyeIcon
                                                v-if="!showPassword"
                                                class="w-5 h-5"
                                            />
                                            <EyeSlashIcon
                                                v-else
                                                class="w-5 h-5"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="errors.password"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ errors.password }}
                                    </p>
                                </div>

                                <div v-if="formData.password">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Confirmation du mot de passe
                                        <span v-if="!isEditMode">*</span>
                                    </label>
                                    <div class="relative">
                                        <LockClosedIcon
                                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                        />
                                        <input
                                            v-model="
                                                formData.password_confirmation
                                            "
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            :class="[
                                                'pl-10 pr-4 py-3 w-full border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm bg-gray-50/50 hover:bg-white',
                                                errors.password_confirmation
                                                    ? 'border-red-300 bg-red-50'
                                                    : 'border-gray-300',
                                            ]"
                                            placeholder="Confirmez le mot de passe"
                                        />
                                    </div>
                                    <p
                                        v-if="errors.password_confirmation"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ errors.password_confirmation }}
                                    </p>
                                </div>
                            </div>

                            <!-- Password Toggle (Edit mode only) -->
                            <div v-if="isEditMode" class="pt-2">
                                <button
                                    type="button"
                                    @click="togglePasswordField"
                                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium"
                                >
                                    <KeyIcon class="w-4 h-4 mr-1" />
                                    {{
                                        showPasswordField
                                            ? "Annuler le changement"
                                            : "Changer le mot de passe"
                                    }}
                                </button>
                            </div>

                            <!-- Email Verified -->
                            <div
                                v-if="authStore.isAdmin"
                                class="flex items-center pt-2"
                            >
                                <div class="flex items-center h-5">
                                    <input
                                        v-model="formData.email_verified"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                    />
                                </div>
                                <label class="ml-2 text-sm text-gray-700">
                                    <span class="font-medium"
                                        >Email vérifié</span
                                    >
                                    <p class="text-xs text-gray-500">
                                        L'utilisateur pourra se connecter
                                        immédiatement
                                    </p>
                                </label>
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-xs text-gray-500">
                                    * Champs obligatoires
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button
                                    @click="closeModal"
                                    :disabled="modalLoading"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="submitForm"
                                    :disabled="modalLoading"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50',
                                        isEditMode
                                            ? 'bg-secondary hover:bg-secondary-dark'
                                            : 'bg-secondary hover:bg-secondary-dark',
                                    ]"
                                >
                                    {{ isEditMode ? "Mettre à jour" : "Créer" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Detail Modal -->
        <UserDetailModal
            v-if="showDetailModal && selectedUser"
            :user="selectedUser"
            @close="closeDetailModal"
        />
    </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, watch, reactive } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useUserStore } from "@/stores/user.store";
import { getRoleLabel } from "@/enums/user-role";
import debounce from "lodash/debounce";
import UserDetailModal from "@/components/users/UserDetailModal.vue";
import type { User } from "@/types/User";

// Import icons (ajoutez ces icônes)
import {
    PlusIcon,
    MagnifyingGlassIcon,
    ExclamationCircleIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    UserIcon,
    CalendarIcon,
    PencilSquareIcon,
    TrashIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    XMarkIcon,
    EyeIcon,
    EllipsisVerticalIcon,
    ArrowPathIcon,
    EyeSlashIcon,
    CameraIcon, // Ajouté
    LockClosedIcon, // Ajouté
    KeyIcon, // Ajouté
    EnvelopeIcon, // Ajouté
} from "@heroicons/vue/24/outline";

const authStore = useAuthStore();
const userStore = useUserStore();

// State
const searchQuery = ref("");
const roleFilter = ref("");
const statusFilter = ref("");
const dateFilter = ref("");
const selectedUsers = ref<(string | number)[]>([]);
const selectAll = ref(false);
const showDetailModal = ref(false);
const selectedUser = ref<User | null>(null);
const showModal = ref(false);
const isEditMode = ref(false);
const modalLoading = ref(false);
const modalError = ref<string | null>(null);
const showPassword = ref(false);
const showPasswordField = ref(false);
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string>("");

// Form Data
const formData = reactive({
    name: "",
    email: "",
    role: "user",
    password: "",
    password_confirmation: "",
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

// Computed
const visiblePages = computed(() => {
    const pages = [];
    const current = userStore.currentPage || 1;
    const last = userStore.lastPage || 1;

    for (
        let i = Math.max(1, current - 2);
        i <= Math.min(last, current + 2);
        i++
    ) {
        pages.push(i);
    }

    return pages;
});

const showEllipsis = computed(() => {
    return (
        (userStore.lastPage || 1) > 5 &&
        (userStore.currentPage || 1) < (userStore.lastPage || 1) - 2
    );
});

const newTodayUsers = computed(() => {
    const today = new Date().toISOString().split("T")[0];
    return userStore.users.filter((user) => {
        if (!user.created_at) return false;
        const createdDate = new Date(user.created_at)
            .toISOString()
            .split("T")[0];
        return createdDate === today;
    }).length;
});

const filteredUsers = computed(() => {
    return userStore.users.filter((user) => {
        let match = true;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            match =
                match &&
                (user.name?.toLowerCase().includes(query) ||
                    user.email?.toLowerCase().includes(query) ||
                    user.role?.toLowerCase().includes(query));
        }

        if (roleFilter.value) {
            match = match && user.role === roleFilter.value;
        }

        if (statusFilter.value) {
            switch (statusFilter.value) {
                case "active":
                    match = match && !!user.last_login_at;
                    break;
                case "inactive":
                    match = match && !user.last_login_at;
                    break;
                case "verified":
                    match = match && !!user.email_verified_at;
                    break;
                case "unverified":
                    match = match && !user.email_verified_at;
                    break;
            }
        }

        return match;
    });
});

// Methods
const formatDate = (dateString?: string) => {
    if (!dateString) return "-";
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString("fr-FR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        });
    } catch {
        return "-";
    }
};

const formatLastLogin = (dateString?: string | null) => {
    if (!dateString) return "Jamais";
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffHours =
            Math.abs(now.getTime() - date.getTime()) / (1000 * 60 * 60);

        if (diffHours < 24) {
            return "Aujourd'hui";
        } else if (diffHours < 48) {
            return "Hier";
        } else {
            return formatDate(dateString);
        }
    } catch {
        return "Jamais";
    }
};

const getInitials = (name: string) => {
    if (!name) return "??";
    return name
        .split(" ")
        .map((part) => part.charAt(0))
        .join("")
        .toUpperCase()
        .substring(0, 2);
};

const getUserStatus = (user: User) => {
    if (user.email_verified_at) {
        return "Vérifié";
    } else if (user.last_login_at) {
        return "Actif";
    } else {
        return "Inactif";
    }
};

const handleAvatarUpload = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Validation de la taille (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            errors.avatar = "L'image ne doit pas dépasser 2MB";
            return;
        }

        // Validation du type
        if (!file.type.startsWith("image/")) {
            errors.avatar = "Veuillez sélectionner une image valide";
            return;
        }

        avatarFile.value = file;
        errors.avatar = "";

        // Créer l'aperçu
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const openCreateModal = () => {
    isEditMode.value = false;
    resetForm();
    showModal.value = true;
};

const editUser = (user: User) => {
    isEditMode.value = true;
    selectedUser.value = user;
    resetForm();

    // Fill form with user data
    formData.name = user.name || "";
    formData.email = user.email || "";
    formData.role = user.role || "user";
    formData.email_verified = !!user.email_verified_at;

    // Réinitialiser l'aperçu de l'avatar
    avatarPreview.value = "";
    avatarFile.value = null;

    showModal.value = true;
};

const resetForm = () => {
    formData.name = "";
    formData.email = "";
    formData.role = "user";
    formData.password = "";
    formData.password_confirmation = "";
    formData.email_verified = false;

    // Réinitialiser les erreurs
    Object.keys(errors).forEach((key) => {
        errors[key as keyof typeof errors] = "";
    });

    modalError.value = null;
    avatarPreview.value = "";
    avatarFile.value = null;
    showPasswordField.value = false;
    showPassword.value = false;
    modalLoading.value = false;
};

const togglePasswordField = () => {
    showPasswordField.value = !showPasswordField.value;
    if (!showPasswordField.value) {
        formData.password = "";
        formData.password_confirmation = "";
    }
};

const validateForm = (): boolean => {
    // Clear errors
    Object.keys(errors).forEach((key) => {
        errors[key as keyof typeof errors] = "";
    });

    let isValid = true;

    if (!formData.name.trim()) {
        errors.name = "Le nom est requis";
        isValid = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!formData.email.trim()) {
        errors.email = "L'email est requis";
        isValid = false;
    } else if (!emailRegex.test(formData.email)) {
        errors.email = "Format d'email invalide";
        isValid = false;
    }

    if (!formData.role) {
        errors.role = "Le rôle est requis";
        isValid = false;
    }

    if (!isEditMode.value) {
        if (!formData.password) {
            errors.password = "Le mot de passe est requis";
            isValid = false;
        } else if (formData.password.length < 8) {
            errors.password =
                "Le mot de passe doit contenir au moins 8 caractères";
            isValid = false;
        } else if (formData.password !== formData.password_confirmation) {
            errors.password_confirmation =
                "Les mots de passe ne correspondent pas";
            isValid = false;
        }
    }

    if (isEditMode.value && showPasswordField.value && formData.password) {
        if (formData.password.length < 8) {
            errors.password =
                "Le mot de passe doit contenir au moins 8 caractères";
            isValid = false;
        } else if (formData.password !== formData.password_confirmation) {
            errors.password_confirmation =
                "Les mots de passe ne correspondent pas";
            isValid = false;
        }
    }

    return isValid;
};

const submitForm = async () => {
    if (!validateForm()) {
        return;
    }

    modalLoading.value = true;
    modalError.value = null;

    try {
        const userData: any = {
            name: formData.name,
            email: formData.email,
            role: formData.role,
        };

        // Ajouter l'avatar si un fichier est sélectionné
        if (avatarFile.value) {
            userData.avatar = avatarFile.value;
        }

        // Gérer l'email vérifié
        if (formData.email_verified) {
            userData.email_verified_at = new Date().toISOString();
        } else if (isEditMode.value) {
            userData.email_verified_at = null;
        }

        // Ajouter le mot de passe si nécessaire
        if (formData.password) {
            userData.password = formData.password;
            userData.password_confirmation = formData.password_confirmation;
        }

        if (isEditMode.value && selectedUser.value) {
            await userStore.updateUser(selectedUser.value.id, userData);
        } else {
            await userStore.createUser(userData);
        }

        closeModal();
    } catch (err: any) {
        console.error("Error submitting form:", err);
        modalError.value =
            err.response?.data?.message || "Une erreur est survenue";

        if (err.response?.data?.errors) {
            const apiErrors = err.response.data.errors;
            Object.keys(apiErrors).forEach((key) => {
                if (key in errors) {
                    errors[key as keyof typeof errors] = apiErrors[key][0];
                }
            });
        }
    } finally {
        modalLoading.value = false;
    }
};

const deleteUser = async (user: User) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.name} ?`)) {
        await userStore.deleteUser(user.id);
    }
};

const viewUser = (user: User) => {
    selectedUser.value = user;
    showDetailModal.value = true;
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedUsers.value = filteredUsers.value.map((user) => user.id);
    } else {
        selectedUsers.value = [];
    }
};

const handleSelectAll = () => {
    if (selectAll.value) {
        selectedUsers.value = filteredUsers.value.map((user) => user.id);
    } else {
        selectedUsers.value = [];
    }
};

const bulkActions = () => {
    console.log("Bulk actions for:", selectedUsers.value);
};

const resetFilters = () => {
    searchQuery.value = "";
    roleFilter.value = "";
    statusFilter.value = "";
    dateFilter.value = "";
    selectedUsers.value = [];
    selectAll.value = false;
};

const onRoleFilterChange = debounce(() => {
    userStore.filterByRole(roleFilter.value);
}, 300);

const onStatusFilterChange = debounce(() => {
    // Implémentez la logique de filtrage par statut si nécessaire
    console.log("Status filter:", statusFilter.value);
}, 300);

const goToPage = (page: number) => {
    userStore.changePage(page);
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

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedUser.value = null;
};

const handleSearch = debounce(() => {
    userStore.searchUsers(searchQuery.value);
}, 500);

// Lifecycle
onMounted(() => {
    userStore.fetchUsers();
});

// Watchers
watch(selectedUsers, (newVal) => {
    selectAll.value = newVal.length === filteredUsers.value.length;
});
</script>

<style scoped>
.action-button-table {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}

.action-button-table:hover {
    transform: translateY(-1px);
}

.edit-button-table:hover {
    color: #3b82f6;
    border-color: #bfdbfe;
    background-color: #eff6ff;
}

.delete-button-table:hover {
    color: #dc2626;
    border-color: #fecaca;
    background-color: #fef2f2;
}

.view-button-table:hover {
    color: #10b981;
    border-color: #bbf7d0;
    background-color: #ecfdf5;
}
</style>
