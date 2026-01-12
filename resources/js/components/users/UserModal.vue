<template>
    <div class="tasks-container">
        <!-- Header -->
        <div class="header-container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="page-title">Tableau de bord</h1>
                    <p class="page-subtitle">Bienvenue, {{ authStore.user?.name }} !</p>
                </div>
                <button v-if="authStore.isAdmin" @click="openCreateModal" class="create-button">
                    <svg class="button-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nouvel utilisateur
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="statistics-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #f0fdf4;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#31b6b8" stroke="#31b6b8" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ roleLabel }}</p>
                    <p class="stat-label">Votre rôle</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #eff6ff;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#3b82f6" stroke="#3b82f6" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0c-.83-.63-1.875-1-3-1s-2.17.37-3 1" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ userStore.totalUsers }}</p>
                    <p class="stat-label">Utilisateurs totaux</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #fef3c7;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ userStore.users.length }}</p>
                    <p class="stat-label">Utilisateurs affichés</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-container">
            <div class="search-wrapper">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="searchQuery" @input="debouncedSearch" type="text"
                    placeholder="Rechercher un utilisateur..." class="search-input" />
            </div>

            <div class="filter-group">
                <select v-model="roleFilter" @change="onRoleFilterChange" class="filter-select">
                    <option value="">Tous les rôles</option>
                    <option value="admin">Administrateur</option>
                    <option value="manager">Manager</option>
                    <option value="user">Utilisateur</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="userStore.loading && !userStore.users.length" class="loading-state">
            <div class="loading-spinner"></div>
            <p class="loading-text">Chargement des utilisateurs...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="userStore.error" class="error-state">
            <svg class="error-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="error-content">
                <h3 class="error-title">Erreur de chargement</h3>
                <p class="error-message">{{ userStore.error }}</p>
                <button @click="userStore.fetchUsers()" class="retry-button">
                    Réessayer
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="userStore.users.length === 0 && !userStore.loading" class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            <h3 class="empty-title">Aucun utilisateur</h3>
            <p class="empty-message">Commencez par créer votre premier utilisateur.</p>
            <button v-if="authStore.isAdmin" @click="openCreateModal" class="empty-action-button">
                Créer un utilisateur
            </button>
        </div>

        <!-- Users Grid -->
        <div v-else class="tasks-grid">
            <div v-for="user in userStore.users" :key="user.id" class="task-card">
                <div class="task-header">
                    <div class="task-title-section">
                        <h3 class="task-title">{{ user.name }}</h3>
                        <p class="task-id">ID: {{ user.id }}</p>
                    </div>
                    <span class="task-status-badge" :class="roleBadgeClasses(user.role)">
                        {{ getRoleLabel(user.role) }}
                    </span>
                </div>

                <p v-if="user.email" class="task-description">{{ user.email }}</p>
                <p v-else class="task-description-placeholder">Aucun email fourni</p>

                <div class="task-meta">
                    <div class="meta-item">
                        <svg class="meta-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="meta-text">{{ formatDate(user.created_at) }}</span>
                    </div>

                    <div v-if="user.email_verified_at" class="meta-item">
                        <svg class="meta-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="meta-text">Vérifié</span>
                    </div>
                </div>

                <div class="task-footer">
                    <div class="assigned-to">
                        <div v-if="user.avatar" class="avatar"
                            :style="{ backgroundImage: `url(${user.avatar})`, backgroundSize: 'cover' }"></div>
                        <div v-else class="avatar">{{ user.initials }}</div>
                        <span class="assigned-name">{{ user.name }}</span>
                    </div>

                    <div class="task-actions">
                        <button @click="editUser(user)" class="action-button edit-button" title="Modifier">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button v-if="authStore.isAdmin && user.id !== authStore.user?.id" @click="deleteUser(user)"
                            class="action-button delete-button" title="Supprimer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="userStore.totalUsers > 0" class="pagination-container">
            <button @click="goToPreviousPage" :disabled="userStore.currentPage === 1"
                class="pagination-button prev-button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Précédent
            </button>

            <div class="pagination-info">
                Page <span class="current-page">{{ userStore.currentPage }}</span> sur {{ userStore.lastPage }}
                <br>
                Affichage de {{ userStore.from }} à {{ userStore.to }} sur {{ userStore.totalUsers }} utilisateurs
            </div>

            <button @click="goToNextPage" :disabled="userStore.currentPage === userStore.lastPage"
                class="pagination-button next-button">
                Suivant
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Admin Actions -->
        <div v-if="authStore.isAdmin" class="mt-8">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Actions rapides administrateur
                    </h3>
                    <div class="mt-5 space-x-3">
                        <router-link :to="{ name: 'admin.users' }"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Gérer les utilisateurs
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <div v-if="showModal" class="modal-overlay" @click="closeModal">
            <div class="modal-container" @click.stop>
                <!-- Modal Header -->
                <div class="modal-header">
                    <h2 class="modal-title">
                        {{ isEditMode ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur' }}
                    </h2>
                    <button @click="closeModal" class="modal-close-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="modal-content">
                    <form @submit.prevent="submitForm">
                        <!-- Loading State -->
                        <div v-if="modalLoading" class="loading-state">
                            <div class="loading-spinner"></div>
                            <p class="loading-text">
                                {{ isEditMode ? 'Modification en cours...' : 'Création en cours...' }}
                            </p>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="modalError" class="error-state">
                            <svg class="error-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="error-content">
                                <h3 class="error-title">Erreur</h3>
                                <p class="error-message">{{ modalError }}</p>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div v-else class="form-grid">
                            <!-- Name -->
                            <div class="form-group">
                                <label for="name" class="form-label">
                                    Nom complet <span class="required">*</span>
                                </label>
                                <input id="name" v-model="formData.name" type="text" class="form-input"
                                    :class="{ 'error': errors.name }" placeholder="Jean Dupont" required />
                                <div v-if="errors.name" class="form-error">
                                    {{ errors.name }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    Email <span class="required">*</span>
                                </label>
                                <input id="email" v-model="formData.email" type="email" class="form-input"
                                    :class="{ 'error': errors.email }" placeholder="jean.dupont@example.com" required />
                                <div v-if="errors.email" class="form-error">
                                    {{ errors.email }}
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="form-group">
                                <label for="role" class="form-label">
                                    Rôle <span class="required">*</span>
                                </label>
                                <select id="role" v-model="formData.role" class="form-select"
                                    :class="{ 'error': errors.role }" required>
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="user">Utilisateur</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                                <div v-if="errors.role" class="form-error">
                                    {{ errors.role }}
                                </div>
                            </div>

                            <!-- Password (only for create or if changing password) -->
                            <div class="form-group" v-if="!isEditMode || showPasswordField">
                                <label for="password" class="form-label">
                                    {{ isEditMode ? 'Nouveau mot de passe' : 'Mot de passe' }}
                                    <span v-if="!isEditMode" class="required">*</span>
                                </label>
                                <div class="password-input-wrapper">
                                    <input id="password" v-model="formData.password"
                                        :type="showPassword ? 'text' : 'password'" class="form-input"
                                        :class="{ 'error': errors.password }"
                                        :placeholder="isEditMode ? 'Laisser vide pour conserver l\'actuel' : 'Mot de passe'"
                                        :required="!isEditMode" />
                                    <button type="button" @click="showPassword = !showPassword" class="password-toggle">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path v-if="showPassword" stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="errors.password" class="form-error">
                                    {{ errors.password }}
                                </div>
                                <div v-if="isEditMode" class="form-hint">
                                    <button type="button" @click="togglePasswordField" class="hint-button">
                                        {{ showPasswordField ? 'Annuler le changement' : 'Changer le mot de passe' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Password Confirmation -->
                            <div class="form-group" v-if="(!isEditMode || showPasswordField) && formData.password">
                                <label for="password_confirmation" class="form-label">
                                    Confirmer le mot de passe
                                    <span v-if="!isEditMode" class="required">*</span>
                                </label>
                                <input id="password_confirmation" v-model="formData.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'" class="form-input"
                                    :class="{ 'error': errors.password_confirmation }"
                                    placeholder="Confirmer le mot de passe" :required="!isEditMode" />
                                <div v-if="errors.password_confirmation" class="form-error">
                                    {{ errors.password_confirmation }}
                                </div>
                            </div>

                            <!-- Avatar URL -->
                            <div class="form-group">
                                <label for="avatar" class="form-label">
                                    URL de l'avatar
                                </label>
                                <input id="avatar" v-model="formData.avatar" type="url" class="form-input"
                                    :class="{ 'error': errors.avatar }" placeholder="https://example.com/avatar.jpg" />
                                <div v-if="errors.avatar" class="form-error">
                                    {{ errors.avatar }}
                                </div>
                            </div>

                            <!-- Email Verification -->
                            <div v-if="authStore.isAdmin" class="form-group">
                                <label class="form-checkbox-label">
                                    <input v-model="formData.email_verified" type="checkbox" class="form-checkbox" />
                                    <span class="checkbox-text">Email vérifié</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" @click="closeModal" class="modal-button secondary" :disabled="modalLoading">
                        Annuler
                    </button>
                    <button type="button" @click="submitForm" class="modal-button primary" :disabled="modalLoading">
                        <span v-if="modalLoading" class="button-loading">
                            <svg class="spinner" width="16" height="16" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"
                                    opacity="0.25" />
                                <path
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    fill="currentColor" />
                            </svg>
                        </span>
                        <span v-else>
                            {{ isEditMode ? 'Modifier' : 'Créer' }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user.store'
import { getRoleLabel } from '@/enums/user-role'
import debounce from 'lodash/debounce'
import type { User } from '@/types/user'

const authStore = useAuthStore()
const userStore = useUserStore()

// State
const searchQuery = ref('')
const roleFilter = ref('')

// Modal State
const showModal = ref(false)
const isEditMode = ref(false)
const modalLoading = ref(false)
const modalError = ref<string | null>(null)
const showPasswordField = ref(false)
const showPassword = ref(false)
const selectedUserId = ref<string | number | null>(null)

// Form Data
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
const roleLabel = computed(() => {
    if (authStore.user?.role) {
        return getRoleLabel(authStore.user.role)
    }
    return 'Utilisateur'
})

// Methods
const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const roleBadgeClasses = (role: string) => {
    switch (role) {
        case 'admin':
            return 'status-active'
        case 'manager':
            return 'status-on_hold'
        case 'user':
            return 'status-completed'
        default:
            return 'status-cancelled'
    }
}

const openCreateModal = () => {
    isEditMode.value = false
    resetForm()
    showModal.value = true
}

const editUser = (user: User) => {
    isEditMode.value = true
    selectedUserId.value = user.id
    resetForm()

    // Fill form with user data
    formData.name = user.name
    formData.email = user.email
    formData.role = user.role
    formData.avatar = user.avatar || ''
    formData.email_verified = !!user.email_verified_at

    showModal.value = true
}

const resetForm = () => {
    Object.keys(formData).forEach(key => {
        if (key === 'role') {
            formData[key] = 'user'
        } else if (key === 'email_verified') {
            formData[key] = false
        } else {
            formData[key] = ''
        }
    })

    Object.keys(errors).forEach(key => {
        errors[key] = ''
    })

    modalError.value = null
    showPasswordField.value = false
    showPassword.value = false
    modalLoading.value = false
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const togglePasswordField = () => {
    showPasswordField.value = !showPasswordField.value
    if (!showPasswordField.value) {
        formData.password = ''
        formData.password_confirmation = ''
    }
}

const validateForm = (): boolean => {
    let isValid = true

    // Clear errors
    Object.keys(errors).forEach(key => {
        errors[key] = ''
    })

    // Name validation
    if (!formData.name.trim()) {
        errors.name = 'Le nom est requis'
        isValid = false
    } else if (formData.name.length < 2) {
        errors.name = 'Le nom doit contenir au moins 2 caractères'
        isValid = false
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!formData.email.trim()) {
        errors.email = 'L\'email est requis'
        isValid = false
    } else if (!emailRegex.test(formData.email)) {
        errors.email = 'Format d\'email invalide'
        isValid = false
    }

    // Role validation
    if (!formData.role) {
        errors.role = 'Le rôle est requis'
        isValid = false
    }

    // Password validation for create
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

    // Password validation for edit (if changing password)
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
    if (!validateForm()) {
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

        // Add avatar if provided
        if (formData.avatar.trim()) {
            userData.avatar = formData.avatar
        }

        // Add email_verified_at if checked
        if (formData.email_verified) {
            userData.email_verified_at = new Date().toISOString()
        } else if (isEditMode.value) {
            userData.email_verified_at = null
        }

        // Add password if provided
        if (formData.password) {
            userData.password = formData.password
            userData.password_confirmation = formData.password_confirmation
        }

        if (isEditMode.value && selectedUserId.value) {
            await userStore.updateUser(selectedUserId.value, userData)
        } else {
            await userStore.createUser(userData)
        }

        closeModal()
    } catch (err: any) {
        modalError.value = err.response?.data?.message || 'Une erreur est survenue'

        // Handle validation errors from API
        if (err.response?.data?.errors) {
            const apiErrors = err.response.data.errors
            Object.keys(apiErrors).forEach(key => {
                if (key in errors) {
                    errors[key] = apiErrors[key][0]
                }
            })
        }
    } finally {
        modalLoading.value = false
    }
}

const deleteUser = async (user: any) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.name} ?`)) {
        await userStore.deleteUser(user.id)
    }
}

const goToPreviousPage = () => {
    if (userStore.currentPage > 1) {
        userStore.changePage(userStore.currentPage - 1)
    }
}

const goToNextPage = () => {
    if (userStore.currentPage < userStore.lastPage) {
        userStore.changePage(userStore.currentPage + 1)
    }
}

const onRoleFilterChange = () => {
    userStore.filterByRole(roleFilter.value)
}

// Debounced search
const debouncedSearch = debounce(() => {
    userStore.searchUsers(searchQuery.value)
}, 500)

// Lifecycle
onMounted(() => {
    userStore.fetchUsers()
})

// Watchers
watch(searchQuery, () => {
    debouncedSearch()
})
</script>

<style scoped>
.tasks-container {
    padding: 24px;
    max-width: 1440px;
    margin: 0 auto;
}

/* Header */
.header-container {
    margin-bottom: 32px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
}

.header-left {
    flex: 1;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 4px 0;
}

.page-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.create-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background-color: #31b6b8;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.create-button:hover {
    background-color: #289396;
    transform: translateY(-1px);
}

.create-button:active {
    transform: translateY(0);
}

.button-icon {
    flex-shrink: 0;
}

/* Filters */
.filters-container {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.search-wrapper {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 12px 12px 12px 42px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1a1a;
    background-color: white;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #31b6b8;
    box-shadow: 0 0 0 3px rgba(49, 182, 184, 0.1);
}

.search-input::placeholder {
    color: #999;
}

.filter-group {
    flex: 0 1 auto;
}

.filter-select {
    padding: 12px 40px 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1a1a;
    background-color: white;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 160px;
    appearance: none;
}

.filter-select:focus {
    outline: none;
    border-color: #31b6b8;
    box-shadow: 0 0 0 3px rgba(49, 182, 184, 0.1);
}

/* Statistics */
.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f0f0f0;
    border-top-color: #31b6b8;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

.loading-text {
    font-size: 14px;
    color: #666;
    margin: 0;
}

/* Error State */
.error-state {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    margin-bottom: 24px;
}

.error-icon {
    flex-shrink: 0;
    color: #dc2626;
}

.error-content {
    flex: 1;
}

.error-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 4px 0;
}

.error-message {
    font-size: 14px;
    color: #666;
    margin: 0 0 12px 0;
}

.retry-button {
    padding: 8px 16px;
    background-color: #dc2626;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.retry-button:hover {
    background-color: #b91c1c;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    text-align: center;
}

.empty-icon {
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 8px 0;
}

.empty-message {
    font-size: 14px;
    color: #666;
    margin: 0 0 20px 0;
    max-width: 400px;
}

.empty-action-button {
    padding: 10px 20px;
    background-color: #31b6b8;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.empty-action-button:hover {
    background-color: #289396;
}

/* Tasks Grid */
.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.task-card {
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.task-card:hover {
    border-color: #31b6b8;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

/* Task Header */
.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.task-title-section {
    flex: 1;
    min-width: 0;
}

.task-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 4px 0;
    line-height: 1.4;
    word-break: break-word;
}

.task-id {
    font-size: 12px;
    color: #999;
    font-family: 'Monaco', 'Consolas', monospace;
}

.task-status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    flex-shrink: 0;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-completed {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-on_hold {
    background-color: #fef3c7;
    color: #92400e;
}

.status-cancelled {
    background-color: #fef2f2;
    color: #991b1b;
}

/* Task Description */
.task-description {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
    margin: 0 0 16px 0;
    word-break: break-word;
}

.task-description-placeholder {
    font-size: 14px;
    color: #999;
    font-style: italic;
    margin: 0 0 16px 0;
}

/* Task Meta */
.task-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.meta-icon {
    flex-shrink: 0;
    color: #999;
}

.meta-text {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
}

.meta-text.overdue-text {
    color: #dc2626;
    font-weight: 500;
}

/* Task Footer */
.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.assigned-to {
    display: flex;
    align-items: center;
    gap: 8px;
}

.avatar {
    width: 28px;
    height: 28px;
    background-color: #31b6b8;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
}

.assigned-name {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

.unassigned {
    font-size: 13px;
    color: #999;
    font-style: italic;
}

.task-actions {
    display: flex;
    gap: 8px;
}

.action-button {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: white;
    color: #666;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}

.action-button:hover {
    border-color: #d1d5db;
    background-color: #f9fafb;
}

.edit-button:hover {
    color: #3b82f6;
    border-color: #bfdbfe;
    background-color: #eff6ff;
}

.delete-button:hover {
    color: #dc2626;
    border-color: #fecaca;
    background-color: #fef2f2;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 0;
    border-top: 1px solid #e5e7eb;
}

.pagination-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: white;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-button:not(:disabled):hover {
    border-color: #31b6b8;
    color: #31b6b8;
    background-color: #f0fdf4;
}

.prev-button {
    justify-content: flex-start;
}

.next-button {
    justify-content: flex-end;
}

.pagination-info {
    font-size: 14px;
    color: #666;
    text-align: center;
    line-height: 1.4;
}

.current-page {
    font-weight: 600;
    color: #1a1a1a;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
    animation: fadeIn 0.2s ease;
}

.modal-container {
    background-color: white;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: slideIn 0.3s ease;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 24px 24px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.modal-close-button {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    color: #999;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.modal-close-button:hover {
    background-color: #f3f4f6;
    color: #666;
}

.modal-content {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 20px 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid #e5e7eb;
}

.modal-button {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    min-width: 100px;
}

.modal-button.secondary {
    background-color: white;
    border-color: #e5e7eb;
    color: #666;
}

.modal-button.secondary:hover {
    background-color: #f9fafb;
    border-color: #d1d5db;
}

.modal-button.primary {
    background-color: #31b6b8;
    color: white;
    border-color: #31b6b8;
}

.modal-button.primary:hover {
    background-color: #289396;
    border-color: #289396;
}

.modal-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.modal-button.primary:disabled:hover {
    background-color: #31b6b8;
    border-color: #31b6b8;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 4px;
}

.required {
    color: #dc2626;
}

.form-input,
.form-select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1a1a;
    background-color: white;
    transition: all 0.2s ease;
    width: 100%;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #31b6b8;
    box-shadow: 0 0 0 3px rgba(49, 182, 184, 0.1);
}

.form-input.error,
.form-select.error {
    border-color: #dc2626;
}

.form-input.error:focus,
.form-select.error:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.form-error {
    font-size: 12px;
    color: #dc2626;
    margin-top: 2px;
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #666;
    background-color: #f3f4f6;
}

.form-hint {
    margin-top: 4px;
}

.hint-button {
    background: none;
    border: none;
    color: #31b6b8;
    font-size: 12px;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
}

.hint-button:hover {
    color: #289396;
}

.form-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.form-checkbox {
    width: 16px;
    height: 16px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
}

.checkbox-text {
    font-size: 14px;
    color: #374151;
}

.button-loading {
    display: flex;
    align-items: center;
    justify-content: center;
}

.spinner {
    animation: spin 1s linear infinite;
}

/* Animations */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .tasks-container {
        padding: 16px;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
    }

    .create-button {
        width: 100%;
        justify-content: center;
    }

    .search-wrapper {
        min-width: 100%;
    }

    .filter-select {
        min-width: 100%;
    }

    .filters-container {
        flex-direction: column;
        gap: 12px;
    }

    .statistics-grid {
        grid-template-columns: 1fr;
    }

    .tasks-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .pagination-container {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }

    .pagination-button {
        width: 100%;
        justify-content: center;
    }

    .modal-container {
        max-height: 80vh;
        margin: 0;
    }

    .modal-header,
    .modal-content,
    .modal-footer {
        padding: 16px;
    }

    .modal-footer {
        flex-direction: column;
    }

    .modal-button {
        width: 100%;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .tasks-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .form-group:last-child:nth-child(odd) {
        grid-column: span 2;
    }
}
</style>