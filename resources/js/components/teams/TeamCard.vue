<template>
    <div
        class="group relative bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden">
        <!-- Effet d'arrière-plan au survol -->
        <div
            class="absolute inset-0 bg-gradient-to-br from-primary/0 via-primary/0 to-secondary/0 group-hover:from-primary/5 group-hover:via-primary/3 group-hover:to-secondary/5 transition-all duration-500">
        </div>

        <!-- Badge Public/Privé avec icône -->
        <div class="absolute top-4 right-4 z-10">
            <span v-if="team.is_public"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-50 text-green-700 border border-green-200 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Public
            </span>
            <span v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-gray-100 to-gray-50 text-gray-700 border border-gray-200 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Privé
            </span>
        </div>

        <!-- Contenu de l'équipe -->
        <div class="relative z-0 p-5">
            <!-- Nom de l'équipe et initiale -->
            <div class="flex items-start gap-3 mb-4">
                <div
                    class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center shadow-sm border border-gray-100">
                    <span class="text-lg font-bold text-gray-700">{{ getInitialesEquipe(team.name) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3
                        class="text-base font-semibold text-gray-900 truncate group-hover:text-primary transition-colors">
                        {{ team.name }}
                    </h3>
                    <p v-if="team.description" class="mt-1 text-sm text-gray-500 line-clamp-2">
                        {{ team.description }}
                    </p>
                </div>
            </div>

            <!-- Séparateur -->
            <div class="my-4 h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

            <!-- Statistiques de l'équipe -->
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0c-.66 0-1.293.1-1.879.277M17 10h.01M7 10h.01M12 10h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500">Membres</div>
                        <div class="text-lg font-bold text-gray-900">{{ team.members_count || 0 }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500">Projets</div>
                        <div class="text-lg font-bold text-gray-900">{{ team.projects_count || 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- Informations du propriétaire -->
            <div v-if="team.owner" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg mb-5">
                <div v-if="team.owner.avatar_url"
                    class="w-8 h-8 rounded-full overflow-hidden border-2 border-white shadow-sm">
                    <img :src="team.owner.avatar_url" :alt="team.owner.name" class="w-full h-full object-cover" />
                </div>
                <div v-else
                    class="w-8 h-8 bg-gradient-to-br from-primary/20 to-secondary/20 rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                    <span class="text-xs font-semibold text-gray-700">{{ getInitiales(team.owner.name) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-gray-500">Créé par</div>
                    <div class="text-sm font-semibold text-gray-900 truncate">{{ team.owner.name }}</div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <div class="text-xs text-gray-500">
                    Créé {{ formaterDateRelative(team.created_at) }}
                </div>

                <div class="flex items-center gap-2">
                    <button @click="$emit('view', team)"
                        class="group/btn relative inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm transition-all duration-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Voir
                    </button>

                    <button v-if="canEdit" @click="$emit('edit', team)"
                        class="group/btn relative inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-primary bg-primary/5 border border-primary/20 rounded-lg hover:bg-primary/10 hover:border-primary/30 hover:shadow-sm transition-all duration-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Éditer
                    </button>

                    <button v-if="canDelete" @click="$emit('delete', team)"
                        class="group/btn relative inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 hover:border-red-300 hover:shadow-sm transition-all duration-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Team } from '@/services/team.service'

defineProps<{
    team: Team
    canEdit?: boolean
    canDelete?: boolean
}>()

defineEmits<{
    view: [team: Team]
    edit: [team: Team]
    delete: [team: Team]
}>()

// Fonctions utilitaires
const getInitialesEquipe = (nom: string) => {
    if (!nom) return 'ÉQ';
    return nom
        .split(' ')
        .map(mot => mot[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const getInitiales = (nom?: string) => {
    if (!nom) return '??';
    return nom
        .split(' ')
        .map(mot => mot[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const formaterDateRelative = (date?: string) => {
    if (!date) return 'récemment';

    const maintenant = new Date();
    const dateEquipe = new Date(date);
    const differenceTemps = Math.abs(maintenant.getTime() - dateEquipe.getTime());
    const differenceJours = Math.ceil(differenceTemps / (1000 * 60 * 60 * 24));

    if (differenceJours === 0) return "aujourd'hui";
    if (differenceJours === 1) return 'hier';
    if (differenceJours < 7) return `il y a ${differenceJours}j`;
    if (differenceJours < 30) return `il y a ${Math.floor(differenceJours / 7)} sem`;
    if (differenceJours < 365) return `il y a ${Math.floor(differenceJours / 30)} mois`;
    return `il y a ${Math.floor(differenceJours / 365)} ans`;
};
</script>

<style scoped>
/* Animations personnalisées */
.group:hover .group-hover\:translate-y-0 {
    transform: translateY(0);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Couleurs personnalisées */
:deep(.bg-primary) {
    background-color: #ab2283;
}

:deep(.bg-secondary) {
    background-color: #31b6b8;
}

:deep(.text-primary) {
    color: #ab2283;
}

:deep(.text-secondary) {
    color: #31b6b8;
}
</style>