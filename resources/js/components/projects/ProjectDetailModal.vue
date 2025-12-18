<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <!-- This element is to trick the browser into centering the modal contents. -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Content -->
                <div v-else>
                    <!-- Header -->
                    <div class="bg-white px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-gray-900">{{ project?.name }}</h3>
                            <div class="flex space-x-2">
                                <span
                                    :class="`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-${getStatusColor(project?.status)}-100 text-${getStatusColor(project?.status)}-800`">
                                    {{ getStatusLabel(project?.status) }}
                                </span>
                                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                                    <XMarkIcon class="h-6 w-6" />
                                </button>
                            </div>
                        </div>
                        <p v-if="project?.description" class="mt-2 text-gray-600">{{ project.description }}</p>
                    </div>

                    <!-- Main Content -->
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Left Column: Project Details -->
                            <div class="lg:col-span-2">
                                <!-- Team Information -->
                                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Team Information</h4>
                                    <div v-if="project?.team" class="flex items-center">
                                        <div
                                            class="flex-shrink-0 h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <span class="text-blue-600 font-medium text-lg">{{
                                                getInitials(project.team.name) }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ project.team.name }}</div>
                                            <div v-if="project.team.description" class="text-sm text-gray-500 mt-1">{{
                                                project.team.description }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-gray-400 italic">No team assigned</div>
                                </div>

                                <!-- Timeline -->
                                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Timeline</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Start Date</span>
                                            <span class="text-sm font-medium">{{ formatDate(project?.start_date)
                                            }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">End Date</span>
                                            <span class="text-sm font-medium">{{ formatDate(project?.end_date) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Duration</span>
                                            <span class="text-sm font-medium">{{ calculateDuration(project?.start_date,
                                                project?.end_date) }}</span>
                                        </div>
                                        <div v-if="isEndDateApproaching"
                                            class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                                            <div class="flex items-center">
                                                <ExclamationIcon class="h-5 w-5 text-yellow-400 mr-2" />
                                                <span class="text-sm text-yellow-700">Project ending soon</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Quick Actions</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <button v-if="project?.status !== 'completed'" @click="completeProject"
                                            class="px-3 py-2 bg-green-100 text-green-800 rounded-md hover:bg-green-200 text-sm font-medium">
                                            Complete
                                        </button>
                                        <button v-if="project?.status !== 'on_hold' && project?.status !== 'completed'"
                                            @click="putOnHold"
                                            class="px-3 py-2 bg-yellow-100 text-yellow-800 rounded-md hover:bg-yellow-200 text-sm font-medium">
                                            On Hold
                                        </button>
                                        <button v-if="project?.status !== 'cancelled'" @click="cancelProject"
                                            class="px-3 py-2 bg-red-100 text-red-800 rounded-md hover:bg-red-200 text-sm font-medium">
                                            Cancel
                                        </button>
                                        <button v-if="project?.status !== 'active'" @click="reactivateProject"
                                            class="px-3 py-2 bg-blue-100 text-blue-800 rounded-md hover:bg-blue-200 text-sm font-medium">
                                            Reactivate
                                        </button>
                                    </div>
                                </div>

                                <!-- Status History -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Project Details</h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Created</span>
                                            <span class="text-sm font-medium">{{ formatDateTime(project?.created_at)
                                            }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Last Updated</span>
                                            <span class="text-sm font-medium">{{ formatDateTime(project?.updated_at)
                                            }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Tasks</span>
                                            <span class="text-sm font-medium">{{ project?.tasks_count || 0 }}
                                                tasks</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Team Members -->
                            <div>
                                <!-- Team Members -->
                                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-lg font-semibold text-gray-900">Team Members</h4>
                                        <span class="text-sm text-gray-500">{{ project?.team_members?.length || 0 }}
                                            members</span>
                                    </div>

                                    <div v-if="project?.team_members && project.team_members.length > 0"
                                        class="space-y-3">
                                        <div v-for="member in project.team_members" :key="member.id"
                                            class="flex items-center p-2 hover:bg-gray-100 rounded-md">
                                            <div class="flex-shrink-0">
                                                <div v-if="member.avatar_url"
                                                    class="h-10 w-10 rounded-full overflow-hidden">
                                                    <img :src="member.avatar_url" :alt="member.name"
                                                        class="h-full w-full object-cover" />
                                                </div>
                                                <div v-else
                                                    class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-medium">{{ member.initials }}</span>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                                                <p class="text-xs text-gray-500">{{ member.email }}</p>
                                                <p v-if="member.role" class="text-xs text-gray-400 mt-1">Role: {{
                                                    member.role }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4">
                                        <UserGroupIcon class="mx-auto h-8 w-8 text-gray-400" />
                                        <p class="mt-2 text-sm text-gray-500">No team members</p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Actions</h4>
                                    <div class="space-y-2">
                                        <button @click="handleEdit"
                                            class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                            <PencilIcon class="h-4 w-4 mr-2" />
                                            Edit Project
                                        </button>
                                        <button @click="handleAssignUsers"
                                            class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <UserPlusIcon class="h-4 w-4 mr-2" />
                                            Manage Assignments
                                        </button>
                                        <button @click="handleViewTasks"
                                            class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <ClipboardIcon class="h-4 w-4 mr-2" />
                                            View Tasks ({{ project?.tasks_count || 0 }})
                                        </button>
                                        <button @click="handleDelete"
                                            class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                            <TrashIcon class="h-4 w-4 mr-2" />
                                            Delete Project
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                Project ID: {{ project?.id }}
                            </div>
                            <div class="flex space-x-2">
                                <button @click="closeModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useProjectTeamStore } from '@/stores/projectsTeams.store';
import { useRouter } from 'vue-router';
import {
    XMarkIcon,
    PencilIcon,
    UserPlusIcon,
    TrashIcon,
    ClipboardIcon,
    UserGroupIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

import type { ProjectTeam } from '@/types/projectsTeams';

const props = defineProps<{
    show: boolean;
    project: ProjectTeam | null;
}>();

const emit = defineEmits<{
    close: [];
    edit: [project: ProjectTeam];
    deleted: [];
}>();

// Store and Router
const projectStore = useProjectTeamStore();
const router = useRouter();

// State
const loading = ref(false);

// Computed
const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'on_hold', label: 'On Hold' },
    { value: 'cancelled', label: 'Cancelled' },
];

const isEndDateApproaching = computed(() => {
    if (!props.project?.end_date || !props.project?.start_date) return false;

    const endDate = new Date(props.project.end_date);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return diffDays <= 7 && diffDays > 0 && props.project.status === 'active';
});

// Methods
const getInitials = (name?: string) => {
    if (!name) return '??';
    return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
};

const getStatusLabel = (status?: string) => {
    if (!status) return 'Unknown';
    const option = statusOptions.find(opt => opt.value === status);
    return option?.label || status;
};

const getStatusColor = (status?: string) => {
    if (!status) return 'gray';
    const colors: Record<string, string> = {
        active: 'green',
        completed: 'blue',
        on_hold: 'yellow',
        cancelled: 'red',
    };
    return colors[status] || 'gray';
};

const formatDate = (date?: string | null) => {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (date?: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const calculateDuration = (startDate?: string | null, endDate?: string | null) => {
    if (!startDate || !endDate) return 'N/A';

    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Same day';
    if (diffDays === 1) return '1 day';
    if (diffDays < 30) return `${diffDays} days`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} months`;
    return `${Math.floor(diffDays / 365)} years`;
};

const closeModal = () => {
    emit('close');
};

const handleEdit = () => {
    if (props.project) {
        emit('edit', props.project);
        closeModal();
    }
};

const handleDelete = () => {
    if (props.project) {
        emit('deleted');
        closeModal();
    }
};

const handleAssignUsers = () => {
    if (props.project) {
        // Navigate to assignment page or open assign modal
        console.log('Assign users to project:', props.project.id);
        // You can implement this based on your needs
    }
};

const handleViewTasks = () => {
    if (props.project) {
        router.push({ name: 'tasks', query: { project_id: props.project.id } });
        closeModal();
    }
};

// Status Management Actions
const completeProject = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.completeProject(props.project.id);
        // The store will update the project status automatically
    } catch (error) {
        console.error('Failed to complete project:', error);
    } finally {
        loading.value = false;
    }
};

const putOnHold = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.putProjectOnHold(props.project.id);
    } catch (error) {
        console.error('Failed to put project on hold:', error);
    } finally {
        loading.value = false;
    }
};

const cancelProject = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.cancelProject(props.project.id);
    } catch (error) {
        console.error('Failed to cancel project:', error);
    } finally {
        loading.value = false;
    }
};

const reactivateProject = async () => {
    if (!props.project) return;

    try {
        loading.value = true;
        await projectStore.reactivateProject(props.project.id);
    } catch (error) {
        console.error('Failed to reactivate project:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for project changes to fetch detailed data
watch(
    () => props.project,
    async (project) => {
        if (project && props.show) {
            try {
                loading.value = true;
                // Fetch detailed project data with team members
                await projectStore.fetchProjectWithTeam(project.id);
            } catch (error) {
                console.error('Failed to load project details:', error);
            } finally {
                loading.value = false;
            }
        }
    },
    { immediate: true }
);
</script>