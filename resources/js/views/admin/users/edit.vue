<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Edit User
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Update user information and permissions
                    </p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button type="button" @click="$router.back()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="button" @click="saveUser"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Changes
                    </button>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form class="space-y-8 divide-y divide-gray-200">
                        <div class="space-y-8 divide-y divide-gray-200">
                            <!-- Personal Information -->
                            <div>
                                <div>
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Personal Information
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Basic user details
                                    </p>
                                </div>

                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <label for="name" class="block text-sm font-medium text-gray-700">
                                            Full name
                                        </label>
                                        <div class="mt-1">
                                            <input type="text" id="name" v-model="form.name"
                                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
                                        </div>
                                    </div>

                                    <div class="sm:col-span-4">
                                        <label for="email" class="block text-sm font-medium text-gray-700">
                                            Email address
                                        </label>
                                        <div class="mt-1">
                                            <input type="email" id="email" v-model="form.email"
                                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="role" class="block text-sm font-medium text-gray-700">
                                            Role
                                        </label>
                                        <select id="role" v-model="form.role"
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="user">User</option>
                                            <option value="manager">Manager</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Reset -->
                            <div class="pt-8">
                                <div>
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Password
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Leave blank to keep current password
                                    </p>
                                </div>

                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <label for="password" class="block text-sm font-medium text-gray-700">
                                            New Password
                                        </label>
                                        <div class="mt-1">
                                            <input type="password" id="password" v-model="form.password"
                                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="password_confirmation"
                                            class="block text-sm font-medium text-gray-700">
                                            Confirm Password
                                        </label>
                                        <div class="mt-1">
                                            <input type="password" id="password_confirmation"
                                                v-model="form.password_confirmation"
                                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const form = reactive({
    name: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: ''
})

onMounted(() => {
    // Simuler le chargement des données utilisateur
    const userId = route.params.id
    console.log('Loading user:', userId)

    // Données factices
    form.name = 'John Doe'
    form.email = 'john@example.com'
    form.role = 'admin'
})

const saveUser = () => {
    console.log('Saving user:', form)
    // Ici vous enverriez les données à l'API
    router.push({ name: 'admin.users' })
}
</script>