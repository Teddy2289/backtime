<template>
    <div>
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Dashboard
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Welcome back, {{ authStore.user?.name }}!
                </p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Cards stat ici -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Role
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ roleLabel }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autres cards... -->
        </div>

        <!-- Section admin si admin -->
        <div v-if="authStore.isAdmin" class="mt-8">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Admin Quick Actions
                    </h3>
                    <div class="mt-5">
                        <router-link :to="{ name: 'admin.users' }"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Manage Users
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getRoleLabel } from '@/enums/user-role'

const authStore = useAuthStore()

const roleLabel = computed(() => {
    if (authStore.user?.role) {
        return getRoleLabel(authStore.user.role)
    }
    return 'User'
})
</script>