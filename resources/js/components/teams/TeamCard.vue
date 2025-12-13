<template>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
        <div class="p-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ team.name }}</h3>
                    <p v-if="team.description" class="mt-1 text-xs text-gray-500 line-clamp-2">
                        {{ team.description }}
                    </p>
                </div>
                <span v-if="team.is_public"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Public
                </span>
                <span v-else
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Privé
                </span>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <span class="flex items-center">
                        <UsersIcon class="h-3 w-3 mr-1" />
                        {{ team.members_count || 0 }} membres
                    </span>
                    <span v-if="team.owner" class="flex items-center">
                        <UserIcon class="h-3 w-3 mr-1" />
                        {{ team.owner.name }}
                    </span>
                </div>

                <div class="flex space-x-2">
                    <button @click="$emit('view', team)"
                        class="text-xs px-3 py-1 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Voir
                    </button>

                    <button v-if="canEdit" @click="$emit('edit', team)"
                        class="text-xs px-3 py-1 border border-blue-300 rounded-md text-blue-700 hover:bg-blue-50">
                        Éditer
                    </button>

                    <button v-if="canDelete" @click="$emit('delete', team)"
                        class="text-xs px-3 py-1 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UsersIcon, UserIcon } from '@heroicons/vue/24/outline'
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
</script>