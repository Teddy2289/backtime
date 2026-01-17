export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar?: string | null;
    avatar_url?: string | null;
    initials?: string;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    last_login_at?: string;
}

export interface UsersResponse {
    data: User[];
    meta: PaginationMeta;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from?: number;
    to?: number;
}

export interface UserFilters {
    search?: string;
    role?: string;
    page?: number;
    per_page?: number;
}
