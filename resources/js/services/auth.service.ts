import { api } from "./api";
import type {
    LoginCredentials,
    RegisterData,
    AuthResponse,
    ProfileData,
    UpdateProfileData,
} from "@/types/auth";

export class AuthService {
    // Authentification
    async login(credentials: LoginCredentials): Promise<AuthResponse> {
        return api.post("/auth/login", credentials);
    }

    async register(userData: RegisterData): Promise<AuthResponse> {
        return api.post("/auth/register", userData);
    }

    async logout(): Promise<void> {
        return api.post("/auth/logout");
    }

    async refreshToken(): Promise<AuthResponse> {
        return api.post("/auth/refresh");
    }

    // Profil utilisateur
    async getMe(): Promise<any> {
        return api.get("/auth/me");
    }

    async getProfile(): Promise<ProfileData> {
        return api.get("/auth/profile");
    }

    async updateProfile(profileData: UpdateProfileData): Promise<ProfileData> {
        return api.put("/auth/profile", profileData);
    }

    async uploadAvatar(avatarFile: File): Promise<any> {
        const formData = new FormData();
        formData.append("avatar", avatarFile);
        return api.upload("/auth/profile/avatar", formData);
    }

    async removeAvatar(): Promise<void> {
        return api.delete("/auth/profile/avatar");
    }

    // Méthodes pour gérer le token
    setToken(token: string): void {
        api.setToken(token);
    }

    removeToken(): void {
        api.removeToken();
    }
}

// Export d'une instance unique
export const authService = new AuthService();
