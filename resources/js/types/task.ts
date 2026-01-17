export interface Task {
    id: number;
    project_id: number;
    title: string;
    description?: string;
    assigned_to?: number;
    assigned_user?: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
    };
    status: "backlog" | "todo" | "doing" | "done";
    priority: "low" | "medium" | "high";
    start_date?: string;
    due_date?: string;
    estimated_time?: number;
    progress: number;
    is_overdue: boolean;
    tags: string[];
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    parent_task_id?: number;
    story_points?: number;
    is_confidential: boolean;

    // Relations optionnelles
    project?: {
        id: number;
        name: string;
    };
    files?: any[];
    comments?: any[];
}

export interface PaginatedTasks {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
export interface PaginatedTasks {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface TaskFilter {
    search?: string;
    status?: string;
    priority?: "low" | "medium" | "high";
}

export interface TaskStatistics {
    total_tasks: number;
    completed_tasks: number;
    in_progress_tasks: number;
    pending_tasks: number;
    overdue_tasks: number;
    average_completion_time?: number;
    by_status: {
        backlog: number;
        todo: number;
        doing: number;
        done: number;
    };
    by_priority: {
        low: number;
        medium: number;
        high: number;
    };
}

export interface CreateTaskData {
    project_id: number;
    title: string;
    description?: string;
    assigned_to?: number;
    status?: string;
    priority?: string;
    start_date?: string;
    due_date?: string;
    estimated_time?: number;
    tags?: string[];
}

export interface UpdateTaskData {
    project_id?: number;
    title?: string;
    description?: string;
    assigned_to?: number | null;
    status?: string;
    priority?: string;
    start_date?: string | null;
    due_date?: string | null;
    estimated_time?: number;
    tags?: string[];
    parent_task_id?: number | null;
    story_points?: number | null;
    is_confidential?: boolean;
}
// Types complémentaires
interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    initials?: string;
}

interface Project {
    id: number;
    name: string;
    description?: string;
}

interface TimeLog {
    id: number;
    task_id: number;
    user_id: number;
    start_time: string;
    end_time?: string;
    duration: number;
    description?: string;
}

interface Comment {
    id: number;
    task_id: number;
    user_id: number;
    content: string;
    created_at: string;
}

interface TaskFile {
    id: number;
    task_id: number;
    filename: string;
    original_name: string;
    path: string;
    size: number;
    mime_type: string;
    uploaded_by: number;
    created_at: string;
}
