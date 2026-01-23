import { api } from "./api";

export interface WorkStatisticsPeriod {
    type: string;
    start_date: string;
    end_date: string;
}

export interface DailyStatistic {
    days_count: number;
    total_hours: number;
    average_hours: number;
}

export interface WorkDayDetail {
    date: string;
    day_name: string;
    total_hours: number;
    target_hours: number;
    extra_hours: number;
    progress_percentage: number;
    status: string;
    is_within_schedule: boolean;
}

export interface TaskStatistics {
    total_created: number;
    total_assigned: number;
    completed: number;
    overdue: number;
    completion_rate: number;
    overdue_rate: number;
    by_status: Record<string, number>;
    by_priority: Record<string, number>;
    assigned_tasks_details: Array<{
        id: number;
        title: string;
        status: string;
        priority: string;
        due_date: string | null;
        is_overdue: boolean;
        progress: number;
        created_at: string;
    }>;
}

export interface WorkSession {
    type: string;
    start: string | null;
    end: string | null;
    duration_hours: number;
    duration_formatted: string;
}

export interface WorkSessionDay {
    date: string;
    day_name: string;
    total_hours: number;
    status: string;
    sessions: WorkSession[];
}

export interface WorkStatisticsResponse {
    period: WorkStatisticsPeriod;
    work_statistics: {
        total_hours: number;
        total_days: number;
        average_daily_hours: number;
        daily_target_hours: number;
        extra_hours: number;
        completion_rate: number;
        days_with_exceeded_target: number;
        daily_statistics: Record<string, DailyStatistic>;
        work_days_details: WorkDayDetail[];
    };
    task_statistics: TaskStatistics;
    totals: {
        total_work_hours: number;
        total_tasks_created: number;
        total_tasks_assigned: number;
        completed_tasks: number;
        productivity_score: number;
    };
    user: {
        id: number;
        name: string;
        role: string;
    };
}

export interface WorkSessionsResponse {
    period: {
        start_date: string;
        end_date: string;
    };
    total_days: number;
    total_hours: number;
    sessions_by_day: WorkSessionDay[];
}

export interface UpdateProfileData {
    name?: string;
    email?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
}

export interface ChangePasswordData {
    current_password: string;
    new_password: string;
    new_password_confirmation: string;
}

export interface UploadAvatarResponse {
    avatar_url: string;
    filename: string;
}

class ProfileService {
    /**
     * Récupérer les statistiques de travail
     */
    async getWorkStatistics(params?: {
        period?: string;
        start_date?: string;
        end_date?: string;
    }): Promise<WorkStatisticsResponse> {
        const response = await api.get<any>("/auth/profile/work-statistics", {
            params,
        });
        // Extraire le data de la réponse
        return response.data; // response.data contient déjà le WorkStatisticsResponse
    }

    /**
     * Récupérer les sessions de travail détaillées
     */
    async getWorkSessions(params?: {
        start_date?: string;
        end_date?: string;
    }): Promise<WorkSessionsResponse> {
        return api.get("/auth/profile/work-sessions", { params });
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    async updateProfile(data: UpdateProfileData): Promise<any> {
        return api.put("/auth/profile", data);
    }

    /**
     * Changer le mot de passe
     */
    async changePassword(data: ChangePasswordData): Promise<any> {
        return api.post("/auth/profile/change-password", data);
    }

    /**
     * Uploader un avatar
     */
    async uploadAvatar(file: File): Promise<UploadAvatarResponse> {
        const formData = new FormData();
        formData.append("avatar", file);
        return api.upload("/auth/profile/avatar", formData);
    }

    /**
     * Supprimer l'avatar
     */
    async removeAvatar(): Promise<any> {
        return api.delete("/auth/profile/avatar");
    }

    /**
     * Récupérer le profil utilisateur
     */
    async getProfile(): Promise<any> {
        return api.get("/auth/profile");
    }

    /**
     * Obtenir les initiales de l'utilisateur
     */
    static getInitials(name: string): string {
        if (!name) return "??";
        return name
            .split(" ")
            .map((word) => word[0])
            .join("")
            .toUpperCase()
            .substring(0, 2);
    }

    /**
     * Formater une date
     */
    static formatDate(dateString?: string): string {
        if (!dateString) return "Non disponible";
        return new Date(dateString).toLocaleDateString("fr-FR", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    /**
     * Formater la dernière connexion
     */
    static formatLastLogin(dateString?: string): string {
        if (!dateString) return "Jamais";

        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

        if (diffHours < 1) {
            return "Il y a moins d'une heure";
        } else if (diffHours < 24) {
            return `Il y a ${diffHours} heure${diffHours > 1 ? "s" : ""}`;
        } else {
            return ProfileService.formatDate(dateString);
        }
    }

    /**
     * Formater le rôle
     */
    static formatRole(role?: string): string {
        if (!role) return "Utilisateur";

        const roleMap: Record<string, string> = {
            admin: "Administrateur",
            user: "Utilisateur",
            manager: "Gestionnaire",
            editor: "Éditeur",
        };

        return roleMap[role] || role;
    }

    /**
     * Formater la durée en heures pour l'affichage
     */
    static formatHours(hours: number): string {
        if (hours === 0) return "0h";
        if (hours < 1) {
            const minutes = Math.round(hours * 60);
            return `${minutes}m`;
        }
        return `${hours.toFixed(1)}h`;
    }

    /**
     * Obtenir la couleur du score de productivité
     */
    static getProductivityColor(score: number): string {
        if (score >= 80) return "text-green-600";
        if (score >= 60) return "text-yellow-600";
        if (score >= 40) return "text-orange-600";
        return "text-red-600";
    }

    /**
     * Obtenir la couleur pour le taux de complétion
     */
    static getCompletionColor(rate: number): string {
        if (rate >= 80) return "text-green-600";
        if (rate >= 60) return "text-yellow-600";
        if (rate >= 40) return "text-orange-600";
        return "text-red-600";
    }
}

// Export d'une instance unique
export const profileService = new ProfileService();
export default ProfileService;
