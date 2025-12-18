import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { DashboardService } from "@/services/dashboard.service";
import type {
    DashboardStats,
    UserWorkTime,
    WorkTimePeriod,
    WorkHistory,
    UserStats,
} from "@/types/dashboard";

export const useDashboardStore = defineStore("dashboard", () => {
    // State
    const stats = ref<DashboardStats | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const userWorkTimeData = ref<Map<number, any>>(new Map());
    const userWorkTimeLoading = ref<Map<number, boolean>>(new Map());
    const userWorkTimeError = ref<Map<number, string | null>>(new Map());

    const userWorkTime = ref<Map<number, UserWorkTime>>(new Map());
    const workTimePeriod = ref<Map<string, WorkTimePeriod>>(new Map());
    const workHistory = ref<Map<number, WorkHistory>>(new Map());
    const userStats = ref<Map<number, UserStats>>(new Map());

    // Getters
    const overviewStats = computed(() => stats.value?.overview || null);
    const taskStats = computed(() => stats.value?.task_stats || null);
    const userStatistics = computed(() => stats.value?.user_stats || null);
    const recentActivity = computed(() => stats.value?.recent_activity || null);
    const teamStats = computed(() => stats.value?.team_stats || null);
    const weeklyAnalytics = computed(
        () => stats.value?.weekly_analytics || null
    );
    const monthlyAnalytics = computed(
        () => stats.value?.monthly_analytics || null
    );
    const topPerformers = computed(() => stats.value?.top_performers || null);

    // Actions
    const fetchStats = async () => {
        loading.value = true;
        error.value = null;

        try {
            const data = await DashboardService.getStats();
            stats.value = data;
        } catch (err: any) {
            error.value =
                err.message || "Erreur lors du chargement des statistiques";
            console.error("Error fetching dashboard stats:", err);
        } finally {
            loading.value = false;
        }
    };

    const fetchUserWorkTime = async (userId: number) => {
        try {
            const data = await DashboardService.getUserWorkTime(userId);
            userWorkTime.value.set(userId, data);
            return data;
        } catch (err: any) {
            console.error(`Error fetching work time for user ${userId}:`, err);
            throw err;
        }
    };

    const fetchWorkTimeByPeriod = async (userId: number, period: string) => {
        try {
            const data = await DashboardService.getUserWorkTimeByPeriod(
                userId,
                period
            );
            const key = `${userId}-${period}`;
            workTimePeriod.value.set(key, data);
            return data;
        } catch (err: any) {
            console.error(
                `Error fetching work time period for user ${userId}:`,
                err
            );
            throw err;
        }
    };

    const fetchWorkHistory = async (userId: number, page = 1, perPage = 20) => {
        try {
            const data = await DashboardService.getUserWorkHistory(
                userId,
                page,
                perPage
            );
            workHistory.value.set(userId, data);
            return data;
        } catch (err: any) {
            console.error(
                `Error fetching work history for user ${userId}:`,
                err
            );
            throw err;
        }
    };

    const fetchUserStats = async (userId: number) => {
        try {
            const data = await DashboardService.getUserStats(userId);
            userStats.value.set(userId, data);
            return data;
        } catch (err: any) {
            console.error(`Error fetching stats for user ${userId}:`, err);
            throw err;
        }
    };

    const refreshStats = async () => {
        await fetchStats();
    };

    // Helper methods

    const getWorkTimeByPeriod = (userId: number, period: string) => {
        const key = `${userId}-${period}`;
        return workTimePeriod.value.get(key) || null;
    };

    const getWorkHistory = (userId: number) => {
        return workHistory.value.get(userId) || null;
    };

    const getUserStatisticsById = (userId: number) => {
        return userStats.value.get(userId) || null;
    };

    const getUserWorkTime = (userId: number) => {
        return userWorkTimeData.value.get(userId) || null;
    };

    const isUserWorkTimeLoading = (userId: number) => {
        return userWorkTimeLoading.value.get(userId) || false;
    };

    const getUserWorkTimeError = (userId: number) => {
        return userWorkTimeError.value.get(userId) || null;
    };

    return {
        // State
        stats,
        loading,
        error,

        // Getters
        overviewStats,
        taskStats,
        userStatistics,
        recentActivity,
        teamStats,
        weeklyAnalytics,
        monthlyAnalytics,
        topPerformers,

        // Actions
        fetchStats,
        fetchUserWorkTime,
        fetchWorkTimeByPeriod,
        fetchWorkHistory,
        fetchUserStats,
        refreshStats,

        // Helper methods
        getUserWorkTime,
        getWorkTimeByPeriod,
        getWorkHistory,
        getUserStatisticsById,
        isUserWorkTimeLoading,
        getUserWorkTimeError,
    };
});
