import { api } from "./api";
import type {
    DashboardStats,
    UserWorkTime,
    WorkTimePeriod,
    WorkHistory,
    UserStats,
    WeeklyAnalytics,
    MonthlyAnalytics,
    TopPerformers,
} from "@/types/dashboard";

export class DashboardService {
    /**
     * Récupère toutes les statistiques du dashboard
     */
    static async getStats(): Promise<DashboardStats> {
        try {
            const response = await api.get<{
                success: boolean;
                data: DashboardStats;
            }>("/dashboard");
            return response.data;
        } catch (error) {
            console.error("Error fetching dashboard stats:", error);
            throw error;
        }
    }

    /**
     * Récupère les statistiques widgets
     */
    static async getWidgetStats(): Promise<any> {
        try {
            const response = await api.get<{ success: boolean; data: any }>(
                "/dashboard/widgets"
            );
            return response.data;
        } catch (error) {
            console.error("Error fetching widget stats:", error);
            throw error;
        }
    }

    /**
     * Récupère les statistiques d'un utilisateur spécifique
     */
    static async getUserStats(userId: number): Promise<UserStats> {
        try {
            const response = await api.get<{
                success: boolean;
                data: UserStats;
            }>(`/dashboard/user/${userId}`);
            return response.data;
        } catch (error) {
            console.error("Error fetching user stats:", error);
            throw error;
        }
    }

    /**
     * Récupère le temps de travail d'un utilisateur
     */
    static async getUserWorkTime(userId: number): Promise<UserWorkTime> {
        try {
            const response = await api.get<{
                success: boolean;
                data: UserWorkTime;
            }>(`dashboard/users/${userId}/work-time`);
            return response.data;
        } catch (error) {
            console.error("Error fetching user work time:", error);
            throw error;
        }
    }

    /**
     * Récupère le temps de travail par période
     */
    static async getUserWorkTimeByPeriod(
        userId: number,
        period: string
    ): Promise<WorkTimePeriod> {
        try {
            const response = await api.get<{
                success: boolean;
                data: WorkTimePeriod;
            }>(`dashboard/users/${userId}/work-time/${period}`);
            return response.data;
        } catch (error) {
            console.error("Error fetching user work time by period:", error);
            throw error;
        }
    }

    /**
     * Récupère l'historique du temps de travail
     */
    static async getUserWorkHistory(
        userId: number,
        page = 1,
        perPage = 20
    ): Promise<WorkHistory> {
        try {
            const response = await api.get<{
                success: boolean;
                data: WorkHistory;
            }>(`/users/${userId}/work-history`, {
                params: { page, per_page: perPage },
            });
            return response.data;
        } catch (error) {
            console.error("Error fetching user work history:", error);
            throw error;
        }
    }

    /**
     * Récupère le temps de travail via les tâches
     */
    static async getUserTaskWorkTime(userId: number): Promise<any> {
        try {
            const response = await api.get<{ success: boolean; data: any }>(
                `dashboard/users/${userId}/task-work-time`
            );
            return response.data;
        } catch (error) {
            console.error("Error fetching user task work time:", error);
            throw error;
        }
    }

    /**
     * Récupère les analytics hebdomadaires
     */
    static async getWeeklyAnalytics(): Promise<WeeklyAnalytics> {
        try {
            const response = await api.get<{
                success: boolean;
                data: WeeklyAnalytics;
            }>("/dashboard/weekly");
            return response.data;
        } catch (error) {
            console.error("Error fetching weekly analytics:", error);
            throw error;
        }
    }

    /**
     * Récupère les analytics mensuels
     */
    static async getMonthlyAnalytics(): Promise<MonthlyAnalytics> {
        try {
            const response = await api.get<{
                success: boolean;
                data: MonthlyAnalytics;
            }>("/dashboard/monthly");
            return response.data;
        } catch (error) {
            console.error("Error fetching monthly analytics:", error);
            throw error;
        }
    }

    /**
     * Récupère les top performers
     */
    static async getTopPerformers(): Promise<TopPerformers> {
        try {
            const response = await api.get<{
                success: boolean;
                data: TopPerformers;
            }>("/dashboard/top-performers");
            return response.data;
        } catch (error) {
            console.error("Error fetching top performers:", error);
            throw error;
        }
    }
}
