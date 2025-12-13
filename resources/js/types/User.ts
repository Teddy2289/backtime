export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar: string | null;
    avatar_url: string | null;
    initials: string;
    email_verified_at: string | null;
    permissions: string[];
    created_at: string;
    updated_at: string;
}

export interface UserFilters {
    search?: string;
    role?: string;
    page?: number;
    per_page?: number;
}
