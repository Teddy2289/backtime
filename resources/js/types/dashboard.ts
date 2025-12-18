export interface DashboardStats {
    overview: OverviewStats;
    task_stats: TaskStats;
    user_stats: UserStats;
    recent_activity: RecentActivity;
    team_stats: TeamStats;
    weekly_analytics: WeeklyAnalytics;
    monthly_analytics: MonthlyAnalytics;
    top_performers: TopPerformers;
}

export interface OverviewStats {
    total_users: number;
    total_tasks: number;
    total_projects: number;
    total_teams: number;
    total_comments: number;
    total_files: number;
    active_users: number;
    pending_tasks: number;
}

export interface TaskStats {
    status_distribution: Record<string, TaskStatus>;
    priority_distribution: Record<string, number>;
    overdue_tasks: number;
    upcoming_tasks: number;
    total_estimated_time: number;
    total_worked_time: number;
    completed_this_month: number;
    completion_rate: number;
}

export interface TaskStatus {
    count: number;
    percentage: number;
    label: string;
}

export interface UserStats {
    total_users: number;
    role_distribution: Record<string, RoleDistribution>;
    active_users_this_month: number;
    new_users_this_week: number;
    tasks_per_user: Record<string, number>;
    verified_users: number;
    unverified_users: number;
}

export interface RoleDistribution {
    count: number;
    percentage: number;
    label: string;
}

export interface RecentActivity {
    recent_tasks: RecentTask[];
    recent_comments: RecentComment[];
    recent_files: RecentFile[];
}

export interface RecentTask {
    id: number;
    title: string;
    status: string;
    priority?: string;
    assigned_to?: { name: string };
    project?: { name: string };
    created_at: string;
    is_overdue?: boolean;
}

export interface RecentComment {
    id: number;
    content: string;
    user: { name: string; avatar?: string };
    task: { id: number; title: string };
    created_at: string;
    is_edited: boolean;
}

export interface RecentFile {
    id: number;
    file_name: string;
    file_size: string;
    uploader: { name: string };
    task: { title: string };
    created_at: string;
    icon: string;
    is_image: boolean;
}

export interface TeamStats {
    total_teams: number;
    tasks_per_team: Record<string, number>;
    projects_per_team: Record<string, number>;
    members_per_team: Record<string, number>;
    public_teams: number;
    private_teams: number;
}

export interface WeeklyAnalytics {
    labels: string[];
    tasks: number[];
    comments: number[];
    files: number[];
    weekly_tasks_completed: number;
}

export interface MonthlyAnalytics {
    monthly_task_status: Record<string, number>;
    monthly_completion_rate: number;
}

export interface TopPerformers {
    top_task_completers: TopPerformer[];
    top_commenters: TopPerformer[];
    top_file_uploaders: TopPerformer[];
}

export interface TopPerformer {
    user_id: number;
    name: string;
    completed_tasks?: number;
    comment_count?: number;
    file_count?: number;
    avatar?: string;
}

// Work Time Types
export interface UserWorkTime {
    user_info: {
        id: number;
        name: string;
        email: string;
    };
    work_time_stats?: {
        today: WorkTimeDay;
        weekly: WorkTimePeriodSummary;
        monthly: WorkTimePeriodSummary;
    };
    recent_sessions?: WorkSession[];
}

export interface WorkTimeDay {
    total_seconds: number;
    total_hours: number;
    net_seconds: number;
    net_hours: number;
    pause_seconds: number;
    pause_hours: number;
    status: string;
}

export interface WorkTimePeriodSummary {
    total_seconds: number;
    total_hours: number;
    net_seconds: number;
    net_hours: number;
    days_worked: number;
    average_daily_hours: number;
}

export interface WorkSession {
    id: number;
    session_start: string;
    session_end: string;
    duration_seconds: number;
    duration_hours: number;
    type: string;
    work_date: string;
    created_at: string;
}

export interface WorkTimePeriod {
    period: string;
    start_date: string;
    end_date: string;
    summary: WorkTimePeriodSummary;
    daily_details: WorkTimeDayDetail[];
    user: {
        id: number;
        name: string;
    };
}

export interface WorkTimeDayDetail {
    date: string;
    total_seconds: number;
    total_hours: number;
    net_seconds: number;
    net_hours: number;
    pause_seconds: number;
    pause_hours: number;
    status: string;
    notes?: string;
}

export interface WorkHistory {
    work_times: WorkTime[];
    pagination: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
}

export interface WorkTime {
    id: number;
    work_date: string;
    day_name: string;
    start_time?: string;
    end_time?: string;
    pause_start?: string;
    pause_end?: string;
    total_seconds: number;
    total_hours: number;
    net_seconds: number;
    net_hours: number;
    pause_seconds: number;
    pause_hours: number;
    status: string;
    notes?: string;
    created_at: string;
}
