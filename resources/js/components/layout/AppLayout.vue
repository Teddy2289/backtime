<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation principale - Design professionnel élégant -->
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo et navigation de gauche -->
                    <div class="flex items-center space-x-8">
                        <!-- Logo minimaliste -->
                        <div class="flex items-center">
                            <router-link
                                :to="{ name: 'dashboard' }"
                                class="flex items-center space-x-2 group"
                            >
                                <div
                                    class="h-8 w-8 rounded-lg bg-gradient-to-br from-secondary to-primary-light flex items-center justify-center"
                                >
                                    <svg
                                        class="h-4 w-4 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"
                                        />
                                    </svg>
                                </div>
                                <span
                                    class="text-lg font-semibold text-gray-800"
                                    >Administration</span
                                >
                            </router-link>
                        </div>

                        <!-- Navigation principale horizontale -->
                        <div class="hidden lg:flex items-center space-x-1">
                            <router-link
                                v-for="item in mainNavigation"
                                :key="item.name"
                                :to="item.to"
                                :class="[
                                    'px-3 py-2 rounded-md text-xs font-medium transition-colors duration-150 flex items-center space-x-1.5',
                                    $route.name === item.to.name
                                        ? 'bg-blue-50 text-blue-600 border border-blue-100'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-3.5 w-3.5"
                                />
                                <span>{{ item.name }}</span>
                                <span
                                    v-if="item.badge"
                                    class="bg-red-100 text-red-600 text-[10px] px-1.5 py-0.5 rounded-full"
                                >
                                    {{ item.badge }}
                                </span>
                            </router-link>
                        </div>
                    </div>

                    <!-- Navigation de droite - Actions compactes -->
                    <div class="flex items-center space-x-2">
                        <!-- Recherche compacte -->
                        <div class="hidden md:block">
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"
                                >
                                    <MagnifyingGlassIcon
                                        class="h-3.5 w-3.5 text-gray-400"
                                    />
                                </div>
                                <input
                                    type="search"
                                    placeholder="Rechercher..."
                                    class="pl-9 pr-3 py-1.5 w-48 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                />
                            </div>
                        </div>

                        <!-- Boutons d'action compactes -->
                        <div class="flex items-center space-x-1">
                            <!-- Notifications -->
                            <div class="relative" data-menu>
                                <button
                                    @click="toggleNotifications"
                                    class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600 hover:text-gray-900"
                                >
                                    <BellIcon class="h-4 w-4" />
                                    <span
                                        v-if="unreadNotifications > 0"
                                        class="absolute -top-0.5 -right-0.5 h-2 w-2 bg-red-500 rounded-full"
                                    ></span>
                                </button>

                                <!-- Dropdown Notifications -->
                                <div
                                    v-if="showNotifications"
                                    class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50"
                                >
                                    <div
                                        class="px-4 py-2 border-b border-gray-100"
                                    >
                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <h3
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                Notifications
                                            </h3>
                                            <button
                                                class="text-xs text-blue-600 hover:text-blue-800"
                                            >
                                                Tout marquer comme lu
                                            </button>
                                        </div>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto">
                                        <div
                                            v-for="notification in notifications"
                                            :key="notification.id"
                                            class="px-4 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
                                        >
                                            <div
                                                class="flex items-start space-x-3"
                                            >
                                                <div
                                                    :class="[
                                                        'h-8 w-8 rounded-full flex items-center justify-center',
                                                        notification.iconBg,
                                                    ]"
                                                >
                                                    <component
                                                        :is="notification.icon"
                                                        class="h-4 w-4 text-white"
                                                    />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="text-sm font-medium text-gray-900"
                                                    >
                                                        {{ notification.title }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-gray-500 truncate"
                                                    >
                                                        {{
                                                            notification.message
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-gray-400 mt-0.5"
                                                    >
                                                        {{ notification.time }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="px-4 py-2 border-t border-gray-100"
                                    >
                                        <router-link
                                            :to="{ name: 'notifications' }"
                                            @click="closeAllMenus"
                                            class="block text-center text-xs font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            Voir toutes les notifications
                                        </router-link>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu utilisateur compact -->
                            <div class="relative" data-menu>
                                <button
                                    @click="toggleUserMenu"
                                    class="flex items-center space-x-2 p-1 rounded-lg hover:bg-gray-100"
                                >
                                    <div
                                        class="h-7 w-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center"
                                    >
                                        <span
                                            class="text-[10px] font-semibold text-white"
                                            >{{
                                                authStore.user?.initials || "U"
                                            }}</span
                                        >
                                    </div>
                                    <ChevronDownIcon
                                        class="h-3 w-3 text-gray-500"
                                    />
                                </button>

                                <!-- Dropdown utilisateur - Style professionnel -->
                                <div
                                    v-if="showUserMenu"
                                    class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                                >
                                    <div
                                        class="px-3 py-2 border-b border-gray-100"
                                    >
                                        <p
                                            class="text-xs font-medium text-gray-500 mb-1"
                                        >
                                            Connecté en tant que
                                        </p>
                                        <p
                                            class="text-sm font-semibold text-gray-900 truncate"
                                        >
                                            {{
                                                authStore.user?.name ||
                                                "Utilisateur"
                                            }}
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 truncate"
                                        >
                                            {{
                                                authStore.user?.email ||
                                                "email@exemple.com"
                                            }}
                                        </p>
                                    </div>
                                    <div class="py-1">
                                        <router-link
                                            :to="{ name: 'profile' }"
                                            @click="closeAllMenus"
                                            class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            <UserCircleIcon
                                                class="h-4 w-4 text-gray-400"
                                            />
                                            <span>Mon profil</span>
                                        </router-link>
                                        <!-- CORRECTION: Utiliser la route 'admin.settings' au lieu de 'settings' -->
                                        <router-link
                                            :to="{ name: 'admin.settings' }"
                                            @click="closeAllMenus"
                                            v-if="
                                                authStore.user?.role === 'ADMIN'
                                            "
                                            class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            <CogIcon
                                                class="h-4 w-4 text-gray-400"
                                            />
                                            <span>Paramètres</span>
                                        </router-link>
                                        <div
                                            class="border-t border-gray-100 my-1"
                                        ></div>
                                        <button
                                            @click="handleLogout"
                                            class="flex items-center space-x-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 w-full"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                                />
                                            </svg>
                                            <span>Déconnexion</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu mobile -->
                            <button
                                @click="toggleMobileMenu"
                                class="lg:hidden p-1.5 rounded-lg hover:bg-gray-100 text-gray-600"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        v-if="!showMobileMenu"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        v-else
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu mobile simplifié -->
            <div
                v-if="showMobileMenu"
                class="lg:hidden bg-white border-t border-gray-100 px-4 py-3"
            >
                <div class="space-y-1">
                    <router-link
                        v-for="item in mainNavigation"
                        :key="item.name"
                        :to="item.to"
                        @click="closeAllMenus"
                        :class="[
                            'flex items-center justify-between px-3 py-2 rounded-md text-sm',
                            $route.name === item.to.name
                                ? 'bg-blue-50 text-blue-600'
                                : 'text-gray-600 hover:bg-gray-50',
                        ]"
                    >
                        <div class="flex items-center space-x-2">
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.name }}</span>
                        </div>
                        <span
                            v-if="item.badge"
                            class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full"
                        >
                            {{ item.badge }}
                        </span>
                    </router-link>
                </div>
            </div>
        </nav>

        <!-- Page Content - Conteneur bien structuré -->
        <main class="py-6">
            <div class="container mx-auto px-4">
                <!-- Breadcrumbs minimaux -->
                <nav class="mb-6">
                    <ol
                        class="flex items-center space-x-1 text-xs text-gray-500"
                    >
                        <li>
                            <router-link
                                :to="{ name: 'dashboard' }"
                                class="hover:text-gray-700"
                                >Tableau de bord</router-link
                            >
                        </li>
                        <li class="flex items-center">
                            <ChevronDownIcon
                                class="h-3 w-3 transform -rotate-90"
                            />
                        </li>
                        <li class="text-gray-900 font-medium">
                            {{ currentPageTitle }}
                        </li>
                    </ol>
                </nav>

                <!-- Slot principal - Garanti sans scroll horizontal -->
                <div
                    class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden"
                >
                    <slot />
                </div>
            </div>
        </main>

        <!-- Overlay pour fermer les menus -->
        <div
            v-if="showUserMenu || showNotifications"
            class="fixed inset-0 z-40"
            @click="closeAllMenus"
        ></div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

import {
    HomeIcon,
    ChartBarIcon,
    FolderIcon,
    UserGroupIcon,
    CogIcon,
    UserCircleIcon,
    BellIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    CheckCircleIcon,
} from "@heroicons/vue/24/outline";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// États des menus
const showUserMenu = ref(false);
const showNotifications = ref(false);
const showMobileMenu = ref(false);

// Navigation principale simplifiée
const mainNavigation = [
    {
        name: "Tableau de bord",
        to: { name: "dashboard" },
        icon: HomeIcon,
    },
    {
        name: "Projets",
        to: { name: "projects" },
        icon: FolderIcon,
        badge: 3,
    },
    {
        name: "Tâches",
        to: { name: "tasks" },
        icon: ClipboardDocumentListIcon,
        badge: 12,
    },
    {
        name: "Équipes",
        to: { name: "teams.list" },
        icon: UserGroupIcon,
    },
    {
        name: "Utilisateurs",
        to: { name: "users" },
        icon: ChartBarIcon,
    },
];

// Notifications de démo
const notifications = ref([
    {
        id: 1,
        title: "Nouvelle tâche",
        message: 'Assignée: "Refonte interface"',
        icon: ClipboardDocumentListIcon,
        iconBg: "bg-blue-500",
        time: "5 min",
    },
    {
        id: 2,
        title: "Échéance",
        message: 'Projet "Mobile App" dans 2 jours',
        icon: ClockIcon,
        iconBg: "bg-yellow-500",
        time: "30 min",
    },
    {
        id: 3,
        title: "Tâche complétée",
        message: '"Dashboard Admin" terminé',
        icon: CheckCircleIcon,
        iconBg: "bg-green-500",
        time: "2 heures",
    },
]);

const unreadNotifications = computed(() => notifications.value.length);

// Titre de la page actuelle
const currentPageTitle = computed(() => {
    const routeName = route.name as string;
    const navItem = mainNavigation.find((item) => item.to.name === routeName);
    return navItem?.name || "Tableau de bord";
});

// Méthodes de toggle - CORRECTION: utiliser stopPropagation
const toggleUserMenu = (event: Event) => {
    event.stopPropagation();
    showUserMenu.value = !showUserMenu.value;
    if (showUserMenu.value) showNotifications.value = false;
};

const toggleNotifications = (event: Event) => {
    event.stopPropagation();
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) showUserMenu.value = false;
};

const toggleMobileMenu = (event: Event) => {
    event.stopPropagation();
    showMobileMenu.value = !showMobileMenu.value;
};

const closeAllMenus = () => {
    showUserMenu.value = false;
    showNotifications.value = false;
    showMobileMenu.value = false;
};

const handleLogout = async () => {
    closeAllMenus();
    try {
        await authStore.logout();
        router.push({ name: "login" });
    } catch (error) {
        console.error("Erreur lors de la déconnexion:", error);
    }
};

// Gestion du clic à l'extérieur - Version améliorée
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const isMenuElement =
        target.closest("[data-menu]") ||
        target.closest(".menu-dropdown") ||
        target.closest(".relative");

    if (!isMenuElement) {
        closeAllMenus();
    }
};

// Gestion des touches clavier
const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === "Escape") {
        closeAllMenus();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    document.addEventListener("keydown", handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
    document.removeEventListener("keydown", handleKeyDown);
});
</script>

<style scoped>
/* Styles spécifiques pour éviter le scroll horizontal */
.container {
    max-width: 1200px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
}

/* Assure que le contenu ne déborde pas */
.overflow-hidden {
    overflow: hidden;
}

/* Transitions smooth pour les menus */
.menu-enter-active,
.menu-leave-active {
    transition: all 0.2s ease;
}

.menu-enter-from,
.menu-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Améliorations pour les dropdowns */
[data-menu] {
    position: relative;
}

/* Scrollbar minimaliste */
::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
