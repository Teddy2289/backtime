<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar pour admin -->
        <div class="flex">
            <!-- Sidebar -->
            <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
                <div class="flex-1 flex flex-col min-h-0 bg-gray-800">
                    <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                        <div class="flex items-center flex-shrink-0 px-4">
                            <h1 class="text-white text-xl font-bold">Admin Panel</h1>
                        </div>
                        <nav class="mt-5 flex-1 px-2 space-y-1">
                            <router-link v-for="item in navigation" :key="item.name" :to="item.to" :class="[
                                'group flex items-center px-2 py-2 text-sm font-medium rounded-md',
                                $route.name === item.to.name
                                    ? 'bg-gray-900 text-white'
                                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                            ]">
                                <component :is="item.icon" :class="[
                                    'mr-3 flex-shrink-0 h-6 w-6',
                                    $route.name === item.to.name ? 'text-white' : 'text-gray-400 group-hover:text-gray-300'
                                ]" />
                                {{ item.name }}
                            </router-link>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="md:pl-64 flex flex-col flex-1">
                <!-- Top bar -->
                <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white shadow">
                    <div class="flex-1 px-4 flex justify-between">
                        <div class="flex-1 flex items-center">
                            <h2 class="text-lg font-medium text-gray-900">{{ pageTitle }}</h2>
                        </div>
                        <div class="ml-4 flex items-center md:ml-6">
                            <!-- User menu -->
                            <div class="ml-3 relative">
                                <button @click="toggleUserMenu"
                                    class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <span class="sr-only">Open user menu</span>
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <span v-if="authStore.user?.initials" class="text-indigo-600 font-semibold">
                                            {{ authStore.user.initials }}
                                        </span>
                                        <svg v-else class="h-5 w-5 text-indigo-600" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>

                                <!-- Dropdown menu -->
                                <div v-if="showUserMenu"
                                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <router-link :to="{ name: 'profile' }"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Your Profile
                                    </router-link>
                                    <button @click="logout"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page content -->
                <main class="flex-1">
                    <div class="py-6">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                            <slot />
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
    HomeIcon,
    UsersIcon,
    Cog6ToothIcon as CogIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const showUserMenu = ref(false)

const pageTitle = computed(() => {
    return route.meta.title || 'Admin Dashboard'
})

const navigation = [
    { name: 'Dashboard', to: { name: 'admin.dashboard' }, icon: HomeIcon },
    { name: 'Users', to: { name: 'admin.users' }, icon: UsersIcon },
    { name: 'Reports', to: { name: 'admin.reports' }, icon: ChartBarIcon },
    { name: 'Settings', to: { name: 'admin.settings' }, icon: CogIcon },
]

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value
}

const logout = async () => {
    await authStore.logout()
    router.push({ name: 'login' })
}
</script>