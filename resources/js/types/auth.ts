import { User } from "./User";

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface RegisterData extends LoginCredentials {
    name: string;
    password_confirmation: string;
}

export interface AuthResponse {
    access_token: string;
    token_type: string;
    expires_in?: number;
    user: User;
}

export interface ProfileData {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    avatar: string | null;
    avatar_url: string | null;
    initials: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: string[];
    permissions: string[];
}

export interface UpdateProfileData {
    name?: string;
    email?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
}
export { User };
