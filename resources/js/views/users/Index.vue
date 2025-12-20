<template>
    <div class="min-h-screen bg-white p-6">
        <!-- Header -->
        <div class="mb-8">
            <!-- En-tête avec dégradé -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50 to-blue-50 p-6 mb-8 border border-blue-100">
                <div class="relative">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-white rounded-xl shadow-sm">
                                    <UserGroupIcon class="w-6 h-6 text-blue-600" />
                                </div>
                                <div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Gestion des utilisateurs</h1>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Gérez tous les utilisateurs de l'application
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button @click="openCreateModal"
                            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-blue-600 rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transform transition-all duration-200 min-w-[180px] hover:bg-blue-700">
                            <PlusIcon class="w-5 h-5 mr-2" />
                            <span>Nouvel utilisateur</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors" />
                        </div>
                        <input v-model="searchQuery" @input="handleSearch" type="text" 
                            placeholder="Nom, email, rôle..." 
                            class="pl-10 pr-4 py-2.5 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm" />
                    </div>
                </div>

                <!-- Role Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                    <select v-model="roleFilter" @change="onRoleFilterChange"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm">
                        <option value="">Tous les rôles</option>
                        <option value="admin">Administrateur</option>
                        <option value="manager">Manager</option>
                        <option value="user">Utilisateur</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select v-model="statusFilter" @change="onStatusFilterChange"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                        <option value="verified">Vérifié</option>
                        <option value="unverified">Non vérifié</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'inscription</label>
                    <input v-model="dateFilter" type="date" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm" />
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex justify-end gap-3">
                <button @click="resetFilters" 
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <ArrowPathIcon class="w-4 h-4" />
                    Réinitialiser
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <UserGroupIcon class="w-6 h-6 text-yellow-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ userStore.users.length }}</p>
                        <p class="text-sm text-gray-600">Total utilisateurs</p>
                    </div>
                </div>
            </div>

            <!-- Admins -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <ShieldCheckIcon class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ userStore.users.filter(user => user.role === 'admin').length }}</p>
                        <p class="text-sm text-gray-600">Administrateurs</p>
                    </div>
                </div>
            </div>

            <!-- Managers -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <UserIcon class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ userStore.users.filter(user => user.role === 'manager').length }}</p>
                        <p class="text-sm text-gray-600">Managers</p>
                    </div>
                </div>
            </div>

            <!-- New Today -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <CalendarIcon class="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ newTodayUsers }}</p>
                        <p class="text-sm text-gray-600">Nouveaux aujourd'hui</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Table Header -->
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Utilisateurs ({{ userStore.users.length }})</h3>
                <div class="flex items-center gap-3">
                    <button @click="toggleSelectAll" 
                        class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        {{ selectAll ? 'Désélectionner tout' : 'Sélectionner tout' }}
                    </button>
                    <button v-if="selectedUsers.length > 0" @click="bulkActions"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        <EllipsisVerticalIcon class="w-5 h-5" />
                        Actions ({{ selectedUsers.length }})
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="userStore.loading && !userStore.users.length" class="p-12 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-600">Chargement des utilisateurs...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="userStore.error" class="p-12 text-center">
                <ExclamationCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Erreur de chargement</h3>
                <p class="text-gray-600 mb-4">{{ userStore.error }}</p>
                <button @click="userStore.fetchUsers()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Réessayer
                </button>
            </div>

            <!-- Empty State -->
            <div v-else-if="userStore.users.length === 0 && !userStore.loading" class="p-12 text-center">
                <UserGroupIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun utilisateur trouvé</h3>
                <p class="text-gray-600 mb-4">Aucun utilisateur ne correspond à vos critères.</p>
                <button @click="resetFilters" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Réinitialiser les filtres
                </button>
            </div>

            <!-- Users Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                <input type="checkbox" v-model="selectAll" @change="handleSelectAll" 
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière connexion</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in filteredUsers" :key="user.id" 
                            :class="['hover:bg-gray-50', { 'bg-blue-50': selectedUsers.includes(user.id) }]">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" :value="user.id" v-model="selectedUsers" 
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div v-if="user.avatar_url" 
                                            class="h-10 w-10 rounded-full bg-cover bg-center"
                                            :style="{ backgroundImage: `url(${user.avatar_url})` }"></div>
                                        <div v-else 
                                            class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                            {{ getInitials(user.name) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    user.role === 'admin' ? 'bg-red-100 text-red-800' :
                                    user.role === 'manager' ? 'bg-blue-100 text-blue-800' :
                                    'bg-gray-100 text-gray-800'
                                ]">
                                    {{ getRoleLabel(user.role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    user.email_verified_at ? 'bg-green-100 text-green-800' :
                                    user.last_login_at ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-gray-100 text-gray-800'
                                ]">
                                    {{ getUserStatus(user) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatLastLogin(user.last_login_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button @click="viewUser(user)" 
                                        class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50">
                                        <EyeIcon class="w-5 h-5" />
                                    </button>
                                    <button @click="editUser(user)" 
                                        class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50">
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </button>
                                    <button v-if="authStore.isAdmin && user.id !== authStore.user?.id" @click="deleteUser(user)" 
                                        class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50">
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="userStore.totalUsers > 0" class="px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-700 mb-4 sm:mb-0">
                        Affichage de {{ userStore.from }} à {{ userStore.to }} sur {{ userStore.totalUsers }} utilisateurs
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="goToPreviousPage" :disabled="userStore.currentPage === 1"
                            :class="['px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium', 
                            userStore.currentPage === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50']">
                            <ChevronLeftIcon class="w-4 h-4 inline mr-1" />
                            Précédent
                        </button>
                        
                        <div class="flex items-center space-x-1">
                            <button v-for="page in visiblePages" :key="page"
                                @click="goToPage(page)"
                                :class="['px-3 py-1.5 rounded-lg text-sm font-medium',
                                page === userStore.currentPage ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100']">
                                {{ page }}
                            </button>
                            <span v-if="showEllipsis" class="px-2 text-gray-500">...</span>
                        </div>
                        
                        <button @click="goToNextPage" :disabled="userStore.currentPage === userStore.lastPage"
                            :class="['px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium', 
                            userStore.currentPage === userStore.lastPage ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50']">
                            Suivant
                            <ChevronRightIcon class="w-4 h-4 inline ml-1" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">
                                {{ isEditMode ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur' }}
                            </h2>
                            <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-lg">
                                <XMarkIcon class="w-5 h-5 text-gray-500" />
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4">
                        <form @submit.prevent="submitForm">
                            <!-- Loading State -->
                            <div v-if="modalLoading" class="py-8 text-center">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                                <p class="text-gray-600">
                                    {{ isEditMode ? 'Modification en cours...' : 'Création en cours...' }}
                                </p>
                            </div>

                            <!-- Error State -->
                            <div v-else-if="modalError" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <ExclamationCircleIcon class="w-5 h-5 text-red-500 mr-2" />
                                    <p class="text-sm text-red-700">{{ modalError }}</p>
                                </div>
                            </div>

                            <!-- Form Fields -->
                            <div v-else class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Nom complet <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="formData.name" type="text" 
                                        :class="['w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500', 
                                        errors.name ? 'border-red-300' : 'border-gray-300']"
                                        placeholder="Jean Dupont" />
                                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="formData.email" type="email" 
                                        :class="['w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500', 
                                        errors.email ? 'border-red-300' : 'border-gray-300']"
                                        placeholder="jean.dupont@example.com" />
                                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                                </div>

                                <!-- Role -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Rôle <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="formData.role" 
                                        :class="['w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500', 
                                        errors.role ? 'border-red-300' : 'border-gray-300']">
                                        <option value="">Sélectionner un rôle</option>
                                        <option value="user">Utilisateur</option>
                                        <option value="manager">Manager</option>
                                        <option value="admin">Administrateur</option>
                                    </select>
                                    <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role }}</p>
                                </div>

                                <!-- Password (Create or Edit) -->
                                <div v-if="!isEditMode || showPasswordField">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ isEditMode ? 'Nouveau mot de passe' : 'Mot de passe' }}
                                        <span v-if="!isEditMode" class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input v-model="formData.password" 
                                            :type="showPassword ? 'text' : 'password'"
                                            :class="['w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10', 
                                            errors.password ? 'border-red-300' : 'border-gray-300']"
                                            :placeholder="isEditMode ? 'Laisser vide pour conserver l\'actuel' : 'Mot de passe'" />
                                        <button type="button" @click="showPassword = !showPassword" 
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                                            <EyeSlashIcon v-else class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                                    <div v-if="isEditMode" class="mt-2">
                                        <button type="button" @click="togglePasswordField" 
                                            class="text-sm text-blue-600 hover:text-blue-800">
                                            {{ showPasswordField ? 'Annuler le changement' : 'Changer le mot de passe' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Password Confirmation -->
                                <div v-if="(!isEditMode || showPasswordField) && formData.password">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Confirmer le mot de passe
                                        <span v-if="!isEditMode" class="text-red-500">*</span>
                                    </label>
                                    <input v-model="formData.password_confirmation" 
                                        :type="showPassword ? 'text' : 'password'"
                                        :class="['w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500', 
                                        errors.password_confirmation ? 'border-red-300' : 'border-gray-300']"
                                        placeholder="Confirmer le mot de passe" />
                                    <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ errors.password_confirmation }}</p>
                                </div>

                                <!-- Avatar URL -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        URL de l'avatar
                                    </label>
                                    <input v-model="formData.avatar" type="url" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://example.com/avatar.jpg" />
                                </div>

                                <!-- Email Verified -->
                                <div v-if="authStore.isAdmin" class="flex items-center">
                                    <input v-model="formData.email_verified" type="checkbox" 
                                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" />
                                    <label class="ml-2 block text-sm text-gray-700">
                                        Email vérifié
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button @click="closeModal" :disabled="modalLoading"
                            class="px-4 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 disabled:opacity-50">
                            Annuler
                        </button>
                        <button @click="submitForm" :disabled="modalLoading"
                            class="px-4 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            {{ isEditMode ? 'Modifier' : 'Créer' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Detail Modal -->
        <UserDetailModal 
            v-if="showDetailModal"
            :user="selectedUser"
            @close="closeDetailModal"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user.store'
import { getRoleLabel } from '@/enums/user-role'
import debounce from 'lodash/debounce'
import UserDetailModal from '@/components/users/UserDetailModal.vue'
import type { User } from '@/types/User'

// Import icons
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
    ArrowDownTrayIcon,
    ArrowPathIcon,
    EyeSlashIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const userStore = useUserStore()

// State
const searchQuery = ref('')
const roleFilter = ref('')
const statusFilter = ref('')
const dateFilter = ref('')
const selectedUsers = ref<(string | number)[]>([])
const selectAll = ref(false)
const showDetailModal = ref(false)
const selectedUser = ref<User | null>(null)
const showModal = ref(false)
const isEditMode = ref(false)
const modalLoading = ref(false)
const modalError = ref<string | null>(null)
const showPassword = ref(false)
const showPasswordField = ref(false)

// Form Data - CHANGÉ DE ref À reactive POUR LES BINDINGS
const formData = reactive({
    name: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: '',
    avatar: '',
    email_verified: false
})

// Form Errors
const errors = reactive({
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: '',
    avatar: ''
})

// Computed
const visiblePages = computed(() => {
    const pages = []
    const current = userStore.currentPage || 1
    const last = userStore.lastPage || 1
    
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i)
    }
    
    return pages
})

const showEllipsis = computed(() => {
    return (userStore.lastPage || 1) > 5 && (userStore.currentPage || 1) < (userStore.lastPage || 1) - 2
})

const newTodayUsers = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return userStore.users.filter(user => {
        if (!user.created_at) return false
        const createdDate = new Date(user.created_at).toISOString().split('T')[0]
        return createdDate === today
    }).length
})

const filteredUsers = computed(() => {
    let users = userStore.users

    // Filtre par recherche
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        users = users.filter(user => 
            user.name?.toLowerCase().includes(query) || 
            user.email?.toLowerCase().includes(query) ||
            user.role?.toLowerCase().includes(query)
        )
    }

    // Filtre par rôle
    if (roleFilter.value) {
        users = users.filter(user => user.role === roleFilter.value)
    }

    // Filtre par statut
    if (statusFilter.value) {
        users = users.filter(user => {
            switch (statusFilter.value) {
                case 'active':
                    return user.last_login_at
                case 'inactive':
                    return !user.last_login_at
                case 'verified':
                    return user.email_verified_at
                case 'unverified':
                    return !user.email_verified_at
                default:
                    return true
            }
        })
    }

    return users
})

// Methods
const formatDate = (dateString: string) => {
    if (!dateString) return '-'
    try {
        const date = new Date(dateString)
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        })
    } catch {
        return '-'
    }
}

const formatLastLogin = (dateString: string | null) => {
    if (!dateString) return 'Jamais'
    try {
        const date = new Date(dateString)
        const now = new Date()
        const diffHours = Math.abs(now.getTime() - date.getTime()) / (1000 * 60 * 60)
        
        if (diffHours < 24) {
            return 'Aujourd\'hui'
        } else if (diffHours < 48) {
            return 'Hier'
        } else {
            return formatDate(dateString)
        }
    } catch {
        return 'Jamais'
    }
}

const getInitials = (name: string) => {
    if (!name) return '??'
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
}

const getUserStatus = (user: User) => {
    if (user.email_verified_at) {
        return 'Vérifié'
    } else if (user.last_login_at) {
        return 'Actif'
    } else {
        return 'Inactif'
    }
}

// CORRECTION : Cette fonction doit être définie
const openCreateModal = () => {
    console.log('Opening create modal')
    isEditMode.value = false
    resetForm()
    showModal.value = true
}

const editUser = (user: User) => {
    console.log('Editing user:', user)
    isEditMode.value = true
    selectedUser.value = user
    resetForm()

    // Fill form with user data
    formData.name = user.name || ''
    formData.email = user.email || ''
    formData.role = user.role || 'user'
    formData.password = ''
    formData.password_confirmation = ''
    formData.avatar = user.avatar || ''
    formData.email_verified = !!user.email_verified_at

    showModal.value = true
}

const resetForm = () => {
    formData.name = ''
    formData.email = ''
    formData.role = 'user'
    formData.password = ''
    formData.password_confirmation = ''
    formData.avatar = ''
    formData.email_verified = false

    // Réinitialiser les erreurs
    Object.keys(errors).forEach(key => {
        errors[key as keyof typeof errors] = ''
    })

    modalError.value = null
    showPasswordField.value = false
    showPassword.value = false
    modalLoading.value = false
}

const togglePasswordField = () => {
    showPasswordField.value = !showPasswordField.value
    if (!showPasswordField.value) {
        formData.password = ''
        formData.password_confirmation = ''
    }
}

const validateForm = (): boolean => {
    // Clear errors
    Object.keys(errors).forEach(key => {
        errors[key as keyof typeof errors] = ''
    })

    let isValid = true

    if (!formData.name.trim()) {
        errors.name = 'Le nom est requis'
        isValid = false
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!formData.email.trim()) {
        errors.email = 'L\'email est requis'
        isValid = false
    } else if (!emailRegex.test(formData.email)) {
        errors.email = 'Format d\'email invalide'
        isValid = false
    }

    if (!formData.role) {
        errors.role = 'Le rôle est requis'
        isValid = false
    }

    if (!isEditMode.value) {
        if (!formData.password) {
            errors.password = 'Le mot de passe est requis'
            isValid = false
        } else if (formData.password.length < 8) {
            errors.password = 'Le mot de passe doit contenir au moins 8 caractères'
            isValid = false
        } else if (formData.password !== formData.password_confirmation) {
            errors.password_confirmation = 'Les mots de passe ne correspondent pas'
            isValid = false
        }
    }

    if (isEditMode.value && showPasswordField.value && formData.password) {
        if (formData.password.length < 8) {
            errors.password = 'Le mot de passe doit contenir au moins 8 caractères'
            isValid = false
        } else if (formData.password !== formData.password_confirmation) {
            errors.password_confirmation = 'Les mots de passe ne correspondent pas'
            isValid = false
        }
    }

    return isValid
}

const submitForm = async () => {
    console.log('Submitting form')
    if (!validateForm()) {
        console.log('Form validation failed')
        return
    }

    modalLoading.value = true
    modalError.value = null

    try {
        const userData: any = {
            name: formData.name,
            email: formData.email,
            role: formData.role
        }

        if (formData.avatar.trim()) {
            userData.avatar = formData.avatar
        }

        if (formData.email_verified) {
            userData.email_verified_at = new Date().toISOString()
        } else if (isEditMode.value) {
            userData.email_verified_at = null
        }

        if (formData.password) {
            userData.password = formData.password
            userData.password_confirmation = formData.password_confirmation
        }

        if (isEditMode.value && selectedUser.value) {
            console.log('Updating user:', selectedUser.value.id, userData)
            await userStore.updateUser(selectedUser.value.id, userData)
        } else {
            console.log('Creating user:', userData)
            await userStore.createUser(userData)
        }

        closeModal()
    } catch (err: any) {
        console.error('Error submitting form:', err)
        modalError.value = err.response?.data?.message || 'Une erreur est survenue'

        if (err.response?.data?.errors) {
            const apiErrors = err.response.data.errors
            Object.keys(apiErrors).forEach(key => {
                if (key in errors) {
                    errors[key as keyof typeof errors] = apiErrors[key][0]
                }
            })
        }
    } finally {
        modalLoading.value = false
    }
}

const deleteUser = async (user: User) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.name} ?`)) {
        await userStore.deleteUser(user.id)
    }
}

const viewUser = (user: User) => {
    selectedUser.value = user
    showDetailModal.value = true
}

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedUsers.value = filteredUsers.value.map(user => user.id)
    } else {
        selectedUsers.value = []
    }
}

const handleSelectAll = () => {
    if (selectAll.value) {
        selectedUsers.value = filteredUsers.value.map(user => user.id)
    } else {
        selectedUsers.value = []
    }
}

const bulkActions = () => {
    console.log('Bulk actions for:', selectedUsers.value)
}

const exportUsers = async () => {
    try {
        await userStore.exportUsers()
    } catch (error) {
        console.error('Export error:', error)
    }
}

const resetFilters = () => {
    searchQuery.value = ''
    roleFilter.value = ''
    statusFilter.value = ''
    dateFilter.value = ''
    // Si votre store a une méthode resetFilters, utilisez-la
    if (userStore.resetFilters) {
        userStore.resetFilters()
    }
}

const onRoleFilterChange = () => {
    if (userStore.filterByRole) {
        userStore.filterByRole(roleFilter.value)
    }
}

const onStatusFilterChange = () => {
    if (userStore.filterByStatus) {
        userStore.filterByStatus(statusFilter.value)
    }
}

const goToPage = (page: number) => {
    if (userStore.changePage) {
        userStore.changePage(page)
    }
}

const goToPreviousPage = () => {
    const currentPage = userStore.currentPage || 1
    if (currentPage > 1 && userStore.changePage) {
        userStore.changePage(currentPage - 1)
    }
}

const goToNextPage = () => {
    const currentPage = userStore.currentPage || 1
    const lastPage = userStore.lastPage || 1
    if (currentPage < lastPage && userStore.changePage) {
        userStore.changePage(currentPage + 1)
    }
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const closeDetailModal = () => {
    showDetailModal.value = false
    selectedUser.value = null
}

// Gestion de la recherche avec debounce
const handleSearch = debounce(() => {
    if (userStore.searchUsers) {
        userStore.searchUsers(searchQuery.value)
    }
}, 500)

// Lifecycle
onMounted(() => {
    userStore.fetchUsers()
})

// Watchers
watch(selectedUsers, (newVal) => {
    selectAll.value = newVal.length === filteredUsers.value.length
})
</script>