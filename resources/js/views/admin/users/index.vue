<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        User Management
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button type="button" @click="createUser"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add User
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li v-for="user in users" :key="user.id">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-indigo-600 font-semibold">
                                                {{ user.initials }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <span :class="roleClass(user.role)"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ user.role }}
                                    </span>
                                    <div class="flex space-x-2">
                                        <router-link :to="{ name: 'admin.users.edit', params: { id: user.id } }"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </router-link>
                                        <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">1</span>
                            to
                            <span class="font-medium">10</span>
                            of
                            <span class="font-medium">97</span>
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Previous
                            </button>
                            <button
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                1
                            </button>
                            <button
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                2
                            </button>
                            <button
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Next
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface User {
    id: number
    name: string
    email: string
    role: string
    initials: string
}

const users = ref<User[]>([
    { id: 1, name: 'John Doe', email: 'john@example.com', role: 'admin', initials: 'JD' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'manager', initials: 'JS' },
    { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'user', initials: 'BJ' },
    { id: 4, name: 'Alice Brown', email: 'alice@example.com', role: 'user', initials: 'AB' },
    { id: 5, name: 'Charlie Wilson', email: 'charlie@example.com', role: 'user', initials: 'CW' },
])

const roleClass = (role: string) => {
    switch (role) {
        case 'admin': return 'bg-red-100 text-red-800'
        case 'manager': return 'bg-yellow-100 text-yellow-800'
        default: return 'bg-green-100 text-green-800'
    }
}

const createUser = () => {
    console.log('Create user')
}

const deleteUser = (user: User) => {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        console.log('Delete user:', user)
    }
}
</script>