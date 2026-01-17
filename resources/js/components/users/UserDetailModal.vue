<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="$emit('close')"
        ></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg"
            >
                <!-- Header -->
                <div
                    class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary/10 via-secondary/5 to-primary/5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-primary">
                                Détails de l'utilisateur
                            </h2>
                            <p class="text-sm text-dark mt-1">
                                Informations complètes
                            </p>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="text-dark hover:text-blue-200 transition-colors p-1"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                    <!-- Avatar Section -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            <div
                                v-if="user.avatar_url || user.avatar"
                                class="w-28 h-28 rounded-full overflow-hidden border-4 border-white shadow-lg"
                            >
                                <img
                                    :src="
                                        user.avatar
                                            ? user.avatar
                                            : '/default-avatar.png'
                                    "
                                    class="w-full h-full object-cover"
                                    :alt="user.name"
                                />
                            </div>
                            <div
                                v-else
                                class="w-28 h-28 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center border-4 border-white shadow-lg"
                            >
                                <span class="text-white text-3xl font-bold">
                                    {{ getInitials(user.name) }}
                                </span>
                            </div>
                        </div>

                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900">
                                {{ user.name }}
                            </h3>
                            <p class="text-gray-600 mt-1">{{ user.email }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                Membre depuis
                                {{ formatDateShort(user.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- User Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Rôle -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500"
                                    >Rôle</span
                                >
                                <span
                                    :class="[
                                        'inline-flex px-3 py-1 rounded-full text-xs font-medium',
                                        user.role === 'admin'
                                            ? 'bg-red-100 text-red-800'
                                            : user.role === 'manager'
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ getRoleLabel(user.role) }}
                                </span>
                            </div>
                        </div>

                        <!-- Statut -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500"
                                    >Statut</span
                                >
                                <span
                                    :class="[
                                        'inline-flex px-3 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-yellow-100 text-yellow-800',
                                    ]"
                                >
                                    {{
                                        user.email_verified_at
                                            ? "Vérifié"
                                            : "Non vérifié"
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Date d'inscription -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="space-y-1">
                                <span class="text-sm font-medium text-gray-500"
                                    >Inscription</span
                                >
                                <p class="text-sm text-gray-900">
                                    {{ formatDate(user.created_at) }}
                                </p>
                            </div>
                        </div>

                        <!-- Dernière connexion -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="space-y-1">
                                <span class="text-sm font-medium text-gray-500"
                                    >Dernière connexion</span
                                >
                                <p class="text-sm text-gray-900">
                                    {{ formatLastLogin(user.last_login_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Informations supplémentaires -->
                    <div class="space-y-4">
                        <!-- ID -->
                        <div
                            class="flex items-center justify-between py-2 border-b border-gray-100"
                        >
                            <span class="text-sm font-medium text-gray-500"
                                >ID</span
                            >
                            <span class="text-sm text-gray-900 font-mono">{{
                                user.id
                            }}</span>
                        </div>

                        <!-- Mise à jour -->
                        <div
                            class="flex items-center justify-between py-2 border-b border-gray-100"
                        >
                            <span class="text-sm font-medium text-gray-500"
                                >Dernière mise à jour</span
                            >
                            <span class="text-sm text-gray-900">{{
                                formatDate(user.updated_at)
                            }}</span>
                        </div>

                        <!-- Avatar URL -->
                        <div
                            v-if="user.avatar_url || user.avatar"
                            class="mt-4 p-4 bg-blue-50 rounded-lg"
                        >
                            <h4 class="text-sm font-medium text-gray-700 mb-2">
                                URL de l'avatar
                            </h4>
                            <div class="flex items-center gap-2">
                                <input
                                    :value="getAvatarUrl(user)"
                                    readonly
                                    class="flex-1 text-sm bg-white px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                />
                                <button
                                    @click="copyAvatarUrl"
                                    class="px-3 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <DocumentDuplicateIcon class="w-4 h-4" />
                                    Copier
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Cliquez sur "Copier" pour copier l'URL dans le
                                presse-papier
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl"
                >
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500">
                                Utilisateur #{{ user.id }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="$emit('close')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Fermer
                            </button>
                            <button
                                @click="editUser"
                                class="px-4 py-2 text-sm font-medium text-white bg-secondary rounded-lg hover:secondary-dark transition-colors"
                            >
                                Modifier
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { XMarkIcon, DocumentDuplicateIcon } from "@heroicons/vue/24/outline";
import { getRoleLabel } from "@/enums/user-role";
import type { User } from "@/types/User";

const props = defineProps<{
    user: User;
}>();

const emit = defineEmits<{
    close: [];
    edit: [user: User];
}>();

const getAvatarUrl = (user: User): string => {
    if (!user) return "";

    // Utiliser avatar_url en priorité, sinon avatar
    const avatarPath = user.avatar_url || user.avatar;

    if (!avatarPath) return "";

    // Si c'est déjà une URL complète, la retourner
    if (avatarPath.startsWith("http")) {
        return avatarPath;
    }

    const baseUrl =
        import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";

    // Si le chemin commence par storage/, l'utiliser tel quel
    if (avatarPath.startsWith("storage/")) {
        return `${baseUrl}/${avatarPath}`;
    }

    // Si le chemin commence par avatars/, ajouter storage/
    if (avatarPath.startsWith("avatars/")) {
        return `${baseUrl}/storage/${avatarPath}`;
    }

    // Pour les noms de fichiers simples
    return `${baseUrl}/storage/avatars/${avatarPath}`;
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

const formatDate = (dateString?: string) => {
    if (!dateString) return "Non disponible";
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString("fr-FR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch {
        return "Date invalide";
    }
};

const formatDateShort = (dateString?: string) => {
    if (!dateString) return "récemment";
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString("fr-FR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        });
    } catch {
        return "récemment";
    }
};

const formatLastLogin = (dateString?: string | null) => {
    if (!dateString) return "Jamais connecté";
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffHours = diffMs / (1000 * 60 * 60);

        if (diffHours < 1) {
            return "À l'instant";
        } else if (diffHours < 24) {
            return `Il y a ${Math.floor(diffHours)}h`;
        } else {
            return formatDate(dateString);
        }
    } catch {
        return "Date invalide";
    }
};

const copyAvatarUrl = async () => {
    const url = getAvatarUrl(props.user);
    if (url) {
        try {
            await navigator.clipboard.writeText(url);
            // Vous pouvez ajouter une notification ici
            console.log("URL copiée:", url);
        } catch (err) {
            console.error("Erreur lors de la copie:", err);
        }
    }
};

const editUser = () => {
    emit("edit", props.user);
    emit("close");
};
</script>
