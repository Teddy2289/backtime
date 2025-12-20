<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>
            <!-- Modal Header -->
            <div class="modal-header">
                <div class="header-left">
                    <h2 class="modal-title">Détails de l'utilisateur</h2>
                    <div class="user-breadcrumb">
                        <span class="user-id">ID: #{{ user.id }}</span>
                        <span class="user-email">{{ user.email }}</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="user-status-indicator">
                        <div class="status-dot" :class="statusClass"></div>
                        <span class="status-text">{{ userStatus }}</span>
                    </div>
                    <button @click="closeModal" class="close-button">
                        <XMarkIcon class="close-icon" />
                    </button>
                </div>
            </div>

            <!-- Modal Content -->
            <div class="modal-content">
                <!-- Loading State -->
                <div v-if="loading" class="loading-state">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">Chargement des détails...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="error-state">
                    <ExclamationCircleIcon class="error-icon" />
                    <div class="error-content">
                        <h3 class="error-title">Erreur de chargement</h3>
                        <p class="error-message">{{ error }}</p>
                        <button @click="loadUserDetails" class="retry-button">
                            Réessayer
                        </button>
                    </div>
                </div>

                <!-- User Details -->
                <div v-else class="user-details-grid">
                    <!-- Left Column: Profile & Info -->
                    <div class="left-column">
                        <!-- Profile Card -->
                        <div class="detail-card profile-card">
                            <div class="profile-header">
                                <div class="avatar-section">
                                    <div v-if="user.avatar_url" class="user-avatar large"
                                        :style="{ backgroundImage: `url(${user.avatar_url})` }">
                                    </div>
                                    <div v-else class="user-avatar large placeholder">
                                        {{ user.initials }}
                                    </div>
                                    <div class="avatar-actions">
                                        <button @click="changeAvatar" class="avatar-action-button">
                                            <PhotoIcon class="action-icon" />
                                            Changer
                                        </button>
                                    </div>
                                </div>
                                <div class="profile-info">
                                    <h3 class="user-name">{{ user.name }}</h3>
                                    <p class="user-email">{{ user.email }}</p>
                                    <div class="profile-meta">
                                        <div class="meta-item">
                                            <CalendarIcon class="meta-icon" />
                                            <span>Inscrit le {{ formatDate(user.created_at) }}</span>
                                        </div>
                                        <div class="meta-item" v-if="user.last_login_at">
                                            <ClockIcon class="meta-icon" />
                                            <span>Dernière connexion: {{ formatLastLogin(user.last_login_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Role & Status -->
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Rôle</span>
                                    <span class="role-badge" :class="roleBadgeClass">
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Statut</span>
                                    <span class="status-badge" :class="statusBadgeClass">
                                        {{ userStatus }}
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Email vérifié</span>
                                    <span class="verification-badge" :class="{ verified: user.email_verified_at }">
                                        {{ user.email_verified_at ? 'Oui' : 'Non' }}
                                        <CheckCircleIcon v-if="user.email_verified_at" class="verified-icon" />
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="detail-card">
                            <h3 class="card-title">
                                <EnvelopeIcon class="card-icon" />
                                Informations de contact
                            </h3>
                            <div class="contact-list">
                                <div class="contact-item">
                                    <span class="contact-label">Email:</span>
                                    <span class="contact-value">{{ user.email }}</span>
                                </div>
                                <div class="contact-item" v-if="user.phone">
                                    <span class="contact-label">Téléphone:</span>
                                    <span class="contact-value">{{ user.phone }}</span>
                                </div>
                                <div class="contact-item">
                                    <span class="contact-label">Notifications:</span>
                                    <span class="contact-value">
                                        <label class="toggle-switch">
                                            <input type="checkbox" v-model="notificationSettings.email"
                                                @change="updateNotificationSettings" />
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">Email</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="detail-card">
                            <h3 class="card-title">
                                <CogIcon class="card-icon" />
                                Paramètres du compte
                            </h3>
                            <div class="settings-list">
                                <div class="setting-item">
                                    <span class="setting-label">Type de compte</span>
                                    <span class="setting-value">{{ accountType }}</span>
                                </div>
                                <div class="setting-item">
                                    <span class="setting-label">MFA activé</span>
                                    <span class="setting-value">
                                        {{ user.mfa_enabled ? 'Oui' : 'Non' }}
                                        <button v-if="!user.mfa_enabled" @click="enableMFA" class="enable-button">
                                            Activer
                                        </button>
                                    </span>
                                </div>
                                <div class="setting-item">
                                    <span class="setting-label">Sessions actives</span>
                                    <span class="setting-value">
                                        {{ user.active_sessions || 0 }}
                                        <button @click="viewSessions" class="view-button">
                                            Voir
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Stats & Activity -->
                    <div class="right-column">
                        <!-- Quick Actions -->
                        <div class="detail-card actions-card">
                            <h3 class="card-title">
                                <BoltIcon class="card-icon" />
                                Actions rapides
                            </h3>
                            <div class="actions-grid">
                                <button @click="editUser" class="action-button edit">
                                    <PencilSquareIcon class="action-button-icon" />
                                    Modifier
                                </button>
                                <button @click="sendResetPassword" class="action-button reset">
                                    <KeyIcon class="action-button-icon" />
                                    Réinitialiser MDP
                                </button>
                                <button @click="toggleUserStatus" class="action-button" :class="statusActionClass">
                                    <UserIcon class="action-button-icon" />
                                    {{ statusActionText }}
                                </button>
                                <button v-if="authStore.isAdmin && user.id !== authStore.user?.id" @click="deleteUser"
                                    class="action-button delete">
                                    <TrashIcon class="action-button-icon" />
                                    Supprimer
                                </button>
                                <button @click="impersonateUser" v-if="authStore.isAdmin && user.id !== authStore.user?.id"
                                    class="action-button impersonate">
                                    <ArrowRightOnRectangleIcon class="action-button-icon" />
                                    Impersonate
                                </button>
                                <button @click="exportUserData" class="action-button export">
                                    <ArrowDownTrayIcon class="action-button-icon" />
                                    Exporter données
                                </button>
                            </div>
                        </div>

                        <!-- User Statistics -->
                        <div class="detail-card">
                            <h3 class="card-title">
                                <ChartBarIcon class="card-icon" />
                                Statistiques
                            </h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon projects">
                                        <FolderIcon />
                                    </div>
                                    <div class="stat-content">
                                        <p class="stat-value">{{ userStats.projects || 0 }}</p>
                                        <p class="stat-label">Projets</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon tasks">
                                        <CheckCircleIcon />
                                    </div>
                                    <div class="stat-content">
                                        <p class="stat-value">{{ userStats.tasks || 0 }}</p>
                                        <p class="stat-label">Tâches</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon hours">
                                        <ClockIcon />
                                    </div>
                                    <div class="stat-content">
                                        <p class="stat-value">{{ formatHours(userStats.hours_worked || 0) }}</p>
                                        <p class="stat-label">Heures travaillées</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon activity">
                                        <FireIcon />
                                    </div>
                                    <div class="stat-content">
                                        <p class="stat-value">{{ userStats.activity_score || 0 }}%</p>
                                        <p class="stat-label">Score d'activité</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="detail-card">
                            <div class="activity-header">
                                <h3 class="card-title">
                                    <ClockIcon class="card-icon" />
                                    Activité récente
                                </h3>
                                <button @click="viewAllActivity" class="view-all-button">
                                    Voir tout
                                </button>
                            </div>
                            <div class="activity-list">
                                <div v-if="recentActivity.length === 0" class="empty-activity">
                                    <p>Aucune activité récente</p>
                                </div>
                                <div v-for="activity in recentActivity" :key="activity.id"
                                    class="activity-item" :class="`type-${activity.type}`">
                                    <div class="activity-icon">
                                        <component :is="getActivityIcon(activity.type)" />
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-text">{{ activity.description }}</p>
                                        <p class="activity-time">{{ formatRelativeTime(activity.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teams & Permissions -->
                        <div class="detail-card">
                            <div class="teams-header">
                                <h3 class="card-title">
                                    <UserGroupIcon class="card-icon" />
                                    Équipes & Permissions
                                </h3>
                                <button @click="manageTeams" class="manage-button">
                                    Gérer
                                </button>
                            </div>
                            <div class="teams-list">
                                <div v-if="userTeams.length === 0" class="no-teams">
                                    <p>Aucune équipe assignée</p>
                                </div>
                                <div v-for="team in userTeams.slice(0, 3)" :key="team.id" class="team-item">
                                    <div class="team-info">
                                        <div class="team-avatar">{{ team.name.charAt(0) }}</div>
                                        <div class="team-details">
                                            <span class="team-name">{{ team.name }}</span>
                                            <span class="team-role">{{ team.role || 'Membre' }}</span>
                                        </div>
                                    </div>
                                    <div class="team-actions">
                                        <span class="team-status" :class="team.is_public ? 'public' : 'private'">
                                            {{ team.is_public ? 'Public' : 'Privé' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="userTeams.length > 3" class="more-teams">
                                    + {{ userTeams.length - 3 }} autre(s) équipe(s)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <div class="footer-left">
                    <div class="audit-info">
                        <p class="audit-text">
                            Dernière mise à jour: {{ formatDate(user.updated_at) }}
                            <span v-if="user.updated_by">par {{ user.updated_by }}</span>
                        </p>
                    </div>
                </div>
                <div class="footer-right">
                    <button @click="closeModal" class="footer-button cancel">
                        Fermer
                    </button>
                    <button v-if="authStore.isAdmin" @click="saveChanges" class="footer-button save">
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user.store'
import { getRoleLabel } from '@/enums/user-role'
import type { User } from '@/types/User'
import { userService } from '@/services/user.service'

import {
    XMarkIcon,
    PhotoIcon,
    CalendarIcon,
    ClockIcon,
    CheckCircleIcon,
    EnvelopeIcon,
    CogIcon,
    BoltIcon,
    PencilSquareIcon,
    KeyIcon,
    UserIcon,
    TrashIcon,
    ArrowRightOnRectangleIcon,
    ArrowDownTrayIcon,
    ChartBarIcon,
    FolderIcon,
    FireIcon,
    UserGroupIcon,
    ExclamationCircleIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    FlagIcon,
    BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps<{
    user: User
}>()

const emit = defineEmits<{
    close: []
    edit: [user: User]
    delete: [userId: string | number]
    refresh: []
}>()

const authStore = useAuthStore()
const userStore = useUserStore()

// State
const loading = ref(false)
const error = ref<string | null>(null)
const userStats = ref<any>({})
const recentActivity = ref<any[]>([])
const userTeams = ref<any[]>([])
const notificationSettings = ref({
    email: true,
    push: false,
    sms: false
})

// Computed
const userStatus = computed(() => {
    if (props.user.email_verified_at) {
        return props.user.is_active ? 'Actif' : 'Inactif'
    }
    return 'Non vérifié'
})

const statusClass = computed(() => {
    if (props.user.email_verified_at) {
        return props.user.is_active ? 'active' : 'inactive'
    }
    return 'pending'
})

const statusBadgeClass = computed(() => {
    if (props.user.email_verified_at) {
        return props.user.is_active ? 'status-active' : 'status-inactive'
    }
    return 'status-pending'
})

const roleBadgeClass = computed(() => {
    switch (props.user.role) {
        case 'admin':
            return 'role-admin'
        case 'manager':
            return 'role-manager'
        case 'user':
            return 'role-user'
        default:
            return 'role-user'
    }
})

const accountType = computed(() => {
    if (props.user.role === 'admin') return 'Administrateur'
    if (props.user.role === 'manager') return 'Manager'
    return 'Utilisateur standard'
})

const statusActionText = computed(() => {
    return props.user.is_active ? 'Désactiver' : 'Activer'
})

const statusActionClass = computed(() => {
    return props.user.is_active ? 'deactivate' : 'activate'
})

// Methods
const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}

const formatRelativeTime = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 60) {
        return `Il y a ${diffMins} min`
    } else if (diffHours < 24) {
        return `Il y a ${diffHours} h`
    } else if (diffDays < 7) {
        return `Il y a ${diffDays} j`
    } else {
        return formatDate(dateString)
    }
}

const formatLastLogin = (dateString: string | null) => {
    if (!dateString) return 'Jamais'
    const date = new Date(dateString)
    return formatRelativeTime(dateString)
}

const formatHours = (hours: number) => {
    return hours.toFixed(1).replace('.', ',') + 'h'
}

const getActivityIcon = (type: string) => {
    const icons: Record<string, any> = {
        login: EyeIcon,
        task: DocumentTextIcon,
        comment: ChatBubbleLeftRightIcon,
        project: FolderIcon,
        update: FlagIcon,
        notification: BellIcon
    }
    return icons[type] || ClockIcon
}

const closeModal = () => {
    emit('close')
}

const editUser = () => {
    emit('edit', props.user)
}

const deleteUser = () => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${props.user.name} ?`)) {
        emit('delete', props.user.id)
        closeModal()
    }
}

const loadUserDetails = async () => {
    loading.value = true
    error.value = null

    try {
        // Charger les statistiques
        const [stats, activity, teams] = await Promise.all([
            userService.getUserStats(props.user.id),
            userService.getUserActivity(props.user.id),
            userService.getUserTeams(props.user.id)
        ])

        userStats.value = stats.data || {}
        recentActivity.value = activity.data || []
        userTeams.value = teams.data || []
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Erreur lors du chargement des détails'
    } finally {
        loading.value = false
    }
}

const toggleUserStatus = async () => {
    try {
        await userStore.updateUser(props.user.id, {
            is_active: !props.user.is_active
        })
        emit('refresh')
    } catch (err: any) {
        console.error('Erreur lors du changement de statut:', err)
    }
}

const sendResetPassword = async () => {
    if (confirm(`Envoyer un email de réinitialisation à ${props.user.email} ?`)) {
        try {
            await userService.sendPasswordReset(props.user.id)
            alert('Email de réinitialisation envoyé avec succès')
        } catch (err: any) {
            alert('Erreur lors de l\'envoi de l\'email')
        }
    }
}

const impersonateUser = async () => {
    if (confirm(`Prendre le contrôle du compte de ${props.user.name} ?`)) {
        try {
            await userService.impersonate(props.user.id)
            // Redirection automatique gérée par le service
        } catch (err: any) {
            alert('Erreur lors de l\'impersonation')
        }
    }
}

const exportUserData = async () => {
    try {
        const response = await userService.exportUserData(props.user.id)
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `user-${props.user.id}-data-${new Date().toISOString().split('T')[0]}.json`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (err: any) {
        console.error('Erreur lors de l\'export:', err)
    }
}

const changeAvatar = () => {
    // Implémentez la logique pour changer l'avatar
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.onchange = async (e) => {
        const file = (e.target as HTMLInputElement).files?.[0]
        if (file) {
            try {
                await userService.uploadAvatar(props.user.id, file)
                emit('refresh')
            } catch (err) {
                console.error('Erreur lors du téléchargement:', err)
            }
        }
    }
    input.click()
}

const updateNotificationSettings = async () => {
    try {
        await userService.updateNotificationSettings(props.user.id, notificationSettings.value)
    } catch (err) {
        console.error('Erreur lors de la mise à jour:', err)
    }
}

const enableMFA = async () => {
    try {
        await userService.enableMFA(props.user.id)
        emit('refresh')
    } catch (err) {
        console.error('Erreur lors de l\'activation MFA:', err)
    }
}

const viewSessions = () => {
    // Implémentez la logique pour voir les sessions
    console.log('Voir sessions pour:', props.user.id)
}

const viewAllActivity = () => {
    // Implémentez la logique pour voir toute l'activité
    console.log('Voir toute l\'activité pour:', props.user.id)
}

const manageTeams = () => {
    // Implémentez la logique pour gérer les équipes
    console.log('Gérer les équipes pour:', props.user.id)
}

const saveChanges = () => {
    // Implémentez la logique pour sauvegarder les modifications
    console.log('Sauvegarder les modifications pour:', props.user.id)
}

// Lifecycle
onMounted(() => {
    loadUserDetails()
})
</script>

<style scoped>
/* Overlay */
.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

/* Modal Container */
.modal-container {
    background: white;
    border-radius: 1rem;
    width: 100%;
    max-width: 1200px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

/* Header */
.modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.header-left {
    flex: 1;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.user-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    opacity: 0.9;
}

.user-id {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 9999px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.active {
    background-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.status-dot.inactive {
    background-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}

.status-dot.pending {
    background-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
}

.status-text {
    font-weight: 500;
}

.close-button {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.close-button:hover {
    background: rgba(255, 255, 255, 0.3);
}

.close-icon {
    width: 1.25rem;
    height: 1.25rem;
}

/* Content */
.modal-content {
    padding: 2rem;
    overflow-y: auto;
    flex: 1;
}

/* Loading & Error States */
.loading-state,
.error-state {
    padding: 3rem;
    text-align: center;
}

.loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    margin: 0 auto 1rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.loading-text,
.error-title {
    color: #374151;
    margin-bottom: 0.5rem;
}

.error-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 1rem;
    color: #ef4444;
}

.error-content {
    max-width: 400px;
    margin: 0 auto;
}

.error-message {
    color: #6b7280;
    margin-bottom: 1rem;
}

.retry-button {
    padding: 0.5rem 1.5rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.retry-button:hover {
    background: #2563eb;
}

/* Grid Layout */
.user-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.left-column,
.right-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Detail Cards */
.detail-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.profile-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.actions-card {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

/* Profile Card */
.profile-header {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.avatar-section {
    position: relative;
}

.user-avatar.large {
    width: 6rem;
    height: 6rem;
    border-radius: 1rem;
    background-size: cover;
    background-position: center;
    border: 4px solid white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.user-avatar.large.placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
}

.avatar-actions {
    position: absolute;
    bottom: -0.5rem;
    left: 50%;
    transform: translateX(-50%);
}

.avatar-action-button {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 9999px;
    font-size: 0.75rem;
    color: #374151;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.avatar-action-button:hover {
    background: #f9fafb;
}

.action-icon {
    width: 0.875rem;
    height: 0.875rem;
}

.profile-info {
    flex: 1;
}

.user-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.25rem 0;
}

.user-email {
    color: #6b7280;
    margin: 0 0 1rem 0;
}

.profile-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.meta-icon {
    width: 1rem;
    height: 1rem;
}

/* Profile Stats */
.profile-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-badge,
.status-badge,
.verification-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.role-badge.role-admin {
    background-color: #fee2e2;
    color: #dc2626;
}

.role-badge.role-manager {
    background-color: #dbeafe;
    color: #1d4ed8;
}

.role-badge.role-user {
    background-color: #f3f4f6;
    color: #374151;
}

.status-badge.status-active {
    background-color: #dcfce7;
    color: #16a34a;
}

.status-badge.status-inactive {
    background-color: #fee2e2;
    color: #dc2626;
}

.status-badge.status-pending {
    background-color: #fef3c7;
    color: #d97706;
}

.verification-badge {
    background-color: #f3f4f6;
    color: #6b7280;
}

.verification-badge.verified {
    background-color: #dcfce7;
    color: #16a34a;
}

.verified-icon {
    width: 1rem;
    height: 1rem;
}

/* Card Titles */
.card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 1rem 0;
}

.card-icon {
    width: 1.25rem;
    height: 1.25rem;
    color: #6b7280;
}

/* Lists */
.contact-list,
.settings-list,
.activity-list,
.teams-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contact-item,
.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.contact-label,
.setting-label {
    font-weight: 500;
    color: #374151;
}

.contact-value,
.setting-value {
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 3rem;
    height: 1.5rem;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #d1d5db;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 1rem;
    width: 1rem;
    left: 0.25rem;
    bottom: 0.25rem;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked+.slider {
    background-color: #10b981;
}

input:checked+.slider:before {
    transform: translateX(1.5rem);
}

.toggle-label {
    margin-left: 0.5rem;
}

/* Buttons */
.enable-button,
.view-button,
.manage-button,
.view-all-button {
    padding: 0.25rem 0.75rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    cursor: pointer;
}

.enable-button:hover,
.view-button:hover,
.manage-button:hover,
.view-all-button:hover {
    background: #2563eb;
}

/* Actions Grid */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.action-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.action-button.edit:hover {
    border-color: #10b981;
    color: #10b981;
}

.action-button.reset:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.action-button.deactivate:hover {
    border-color: #ef4444;
    color: #ef4444;
}

.action-button.activate:hover {
    border-color: #10b981;
    color: #10b981;
}

.action-button.delete:hover {
    border-color: #dc2626;
    color: #dc2626;
}

.action-button.impersonate:hover {
    border-color: #8b5cf6;
    color: #8b5cf6;
}

.action-button.export:hover {
    border-color: #f59e0b;
    color: #f59e0b;
}

.action-button-icon {
    width: 1.5rem;
    height: 1.5rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon.projects {
    background-color: #e0e7ff;
    color: #4f46e5;
}

.stat-icon.tasks {
    background-color: #fce7f3;
    color: #db2777;
}

.stat-icon.hours {
    background-color: #fef3c7;
    color: #d97706;
}

.stat-icon.activity {
    background-color: #dcfce7;
    color: #16a34a;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

/* Activity */
.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    transition: background 0.2s;
}

.activity-item:hover {
    background: #f9fafb;
}

.activity-item.type-login {
    border-left: 3px solid #3b82f6;
}

.activity-item.type-task {
    border-left: 3px solid #10b981;
}

.activity-item.type-comment {
    border-left: 3px solid #8b5cf6;
}

.activity-item.type-project {
    border-left: 3px solid #f59e0b;
}

.activity-icon {
    width: 2rem;
    height: 2rem;
    background: #f3f4f6;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.activity-icon svg {
    width: 1rem;
    height: 1rem;
    color: #6b7280;
}

.activity-content {
    flex: 1;
}

.activity-text {
    margin: 0 0 0.25rem 0;
    color: #374151;
    font-size: 0.875rem;
}

.activity-time {
    margin: 0;
    color: #9ca3af;
    font-size: 0.75rem;
}

.empty-activity {
    padding: 2rem;
    text-align: center;
    color: #9ca3af;
}

/* Teams */
.teams-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.team-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.5rem;
    transition: background 0.2s;
}

.team-item:hover {
    background: #f9fafb;
}

.team-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.team-avatar {
    width: 2rem;
    height: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.team-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.team-name {
    font-weight: 500;
    color: #111827;
}

.team-role {
    font-size: 0.75rem;
    color: #6b7280;
}

.team-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-weight: 500;
}

.team-status.public {
    background: #dcfce7;
    color: #16a34a;
}

.team-status.private {
    background: #fee2e2;
    color: #dc2626;
}

.no-teams {
    padding: 2rem;
    text-align: center;
    color: #9ca3af;
}

.more-teams {
    text-align: center;
    padding: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

/* Footer */
.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}

.audit-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

.footer-right {
    display: flex;
    gap: 0.75rem;
}

.footer-button {
    padding: 0.625rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.footer-button.cancel {
    background: white;
    color: #374151;
    border-color: #d1d5db;
}

.footer-button.cancel:hover {
    background: #f9fafb;
}

.footer-button.save {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.footer-button.save:hover {
    background: #2563eb;
}

/* Responsive */
@media (max-width: 1024px) {
    .user-details-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .modal-header {
        flex-direction: column;
        gap: 1rem;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-stats {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .footer-right {
        flex-direction: column;
        width: 100%;
    }

    .footer-button {
        width: 100%;
    }
}
</style>