export interface ProjectTeam {
    id: number;
    team_id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    status: "active" | "completed" | "on_hold" | "cancelled";
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    team?: {
        id: number;
        name: string;
        description: string | null;
        owner_id: number;
        is_public: boolean;
    };
    team_members?: Array<{
        id: number;
        name: string;
        email: string;
        avatar: string | null;
        avatar_url: string | null;
        initials: string;
        role: string;
    }>;
    tasks_count?: number;
}

export interface CreateProjectTeamData {
    team_id: number;
    name: string;
    description?: string;
    start_date?: string;
    end_date?: string;
    status?: "active" | "completed" | "on_hold" | "cancelled";
}

export interface UpdateProjectTeamData {
    team_id?: number;
    name?: string;
    description?: string;
    start_date?: string;
    end_date?: string;
    status?: "active" | "completed" | "on_hold" | "cancelled";
}

export interface ProjectTeamFilter {
    team_id?: number;
    status?: string;
    search?: string;
    order_by?: string;
    order_direction?: "asc" | "desc";
    start_date_from?: string;
    start_date_to?: string;
    end_date_from?: string;
    end_date_to?: string;
    page?: number;
    per_page?: number;
}

export interface PaginatedProjectsTeams {
    data: ProjectTeam[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface ProjectStatistics {
    total: number;
    active: number;
    completed: number;
    on_hold: number;
    cancelled: number;
    upcoming_end: number;
    average_duration_days: number;
    completion_rate: number;
}

export interface AssignableUser {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    initials: string;
    can_assign: boolean;
}

export const STATUS_OPTIONS = [
    { value: "active", label: "Active", color: "green" },
    { value: "completed", label: "Completed", color: "blue" },
    { value: "on_hold", label: "On Hold", color: "yellow" },
    { value: "cancelled", label: "Cancelled", color: "red" },
];

export const STATUS_COLORS = {
    active: "green",
    completed: "blue",
    on_hold: "yellow",
    cancelled: "red",
};
