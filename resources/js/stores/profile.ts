import { defineStore } from "pinia";
import { ref, computed } from "vue";
import {
    profileService,
    type WorkStatisticsResponse,
    type WorkSessionsResponse,
} from "@/services/profile.service";

export const useProfileStore = defineStore("profile", () => {
    // State
    const statistics = ref<WorkStatisticsResponse | null>(null);
    const sessions = ref<WorkSessionsResponse | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const workStatistics = computed(() => statistics.value);
    const workSessions = computed(() => sessions.value);
    const totalWorkHours = computed(
        () => statistics.value?.work_statistics.total_hours || 0,
    );
    const totalTasksCreated = computed(
        () => statistics.value?.task_statistics.total_created || 0,
    );
    const totalTasksAssigned = computed(
        () => statistics.value?.task_statistics.total_assigned || 0,
    );
    const completedTasks = computed(
        () => statistics.value?.task_statistics.completed || 0,
    );
    const productivityScore = computed(
        () => statistics.value?.totals.productivity_score || 0,
    );
    const completionRate = computed(
        () => statistics.value?.task_statistics.completion_rate || 0,
    );
    const overdueRate = computed(
        () => statistics.value?.task_statistics.overdue_rate || 0,
    );
    const dailyStatistics = computed(
        () => statistics.value?.work_statistics.daily_statistics || {},
    );
    const workDaysDetails = computed(
        () => statistics.value?.work_statistics.work_days_details || [],
    );
    const taskByStatus = computed(
        () => statistics.value?.task_statistics.by_status || {},
    );
    const taskByPriority = computed(
        () => statistics.value?.task_statistics.by_priority || {},
    );
    const assignedTasks = computed(
        () => statistics.value?.task_statistics.assigned_tasks_details || [],
    );

    // Actions
    const fetchWorkStatistics = async (params?: {
        period?: string;
        start_date?: string;
        end_date?: string;
    }) => {
        try {
            isLoading.value = true;
            error.value = null;
            statistics.value = await profileService.getWorkStatistics(params);
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors de la récupération des statistiques";
            console.error("Error fetching work statistics:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchWorkSessions = async (params?: {
        start_date?: string;
        end_date?: string;
    }) => {
        try {
            isLoading.value = true;
            error.value = null;
            sessions.value = await profileService.getWorkSessions(params);
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors de la récupération des sessions";
            console.error("Error fetching work sessions:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateProfile = async (data: {
        name?: string;
        email?: string;
        current_password?: string;
        password?: string;
        password_confirmation?: string;
    }) => {
        try {
            isLoading.value = true;
            error.value = null;
            const response = await profileService.updateProfile(data);
            return response;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors de la mise à jour du profil";
            console.error("Error updating profile:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const changePassword = async (data: {
        current_password: string;
        new_password: string;
        new_password_confirmation: string;
    }) => {
        try {
            isLoading.value = true;
            error.value = null;
            const response = await profileService.changePassword(data);
            return response;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors du changement de mot de passe";
            console.error("Error changing password:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const uploadAvatar = async (file: File) => {
        try {
            isLoading.value = true;
            error.value = null;
            const response = await profileService.uploadAvatar(file);
            return response;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors de l'upload de l'avatar";
            console.error("Error uploading avatar:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const removeAvatar = async () => {
        try {
            isLoading.value = true;
            error.value = null;
            const response = await profileService.removeAvatar();
            return response;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors de la suppression de l'avatar";
            console.error("Error removing avatar:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const refreshStatistics = async () => {
        if (statistics.value?.period.type) {
            await fetchWorkStatistics({ period: statistics.value.period.type });
        } else {
            await fetchWorkStatistics();
        }
    };

    const reset = () => {
        statistics.value = null;
        sessions.value = null;
        isLoading.value = false;
        error.value = null;
    };

    return {
        // State
        statistics,
        sessions,
        isLoading,
        error,

        // Getters
        workStatistics,
        workSessions,
        totalWorkHours,
        totalTasksCreated,
        totalTasksAssigned,
        completedTasks,
        productivityScore,
        completionRate,
        overdueRate,
        dailyStatistics,
        workDaysDetails,
        taskByStatus,
        taskByPriority,
        assignedTasks,

        // Actions
        fetchWorkStatistics,
        fetchWorkSessions,
        updateProfile,
        changePassword,
        uploadAvatar,
        removeAvatar,
        refreshStatistics,
        reset,
    };
});
