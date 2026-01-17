import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type { Ref } from "vue";
import { UserRole } from "@/enums/user-role";
import type { User, ProfileData } from "@/types/auth";
import { authService } from "@/services/auth.service";
import router from "@/router";

export const useAuthStore = defineStore("auth", () => {
    // State
    const user: Ref<User | null> = ref(null);
    const token: Ref<string | null> = ref(localStorage.getItem("token"));
    const roles: Ref<string[]> = ref([]);
    const permissions: Ref<string[]> = ref([]);

    // Helper pour extraire les données de la réponse
    const extractData = <T>(response: any): T => {
        // Si c'est une structure ApiResponse
        if (response && typeof response === "object" && "data" in response) {
            return response.data;
        }
        // Sinon, retourner la réponse directement
        return response;
    };

    // Getters
    const isAuthenticated = computed(() => !!token.value);
    const getUser = computed(() => user.value);
    const getRoles = computed(() => roles.value);
    const getPermissions = computed(() => permissions.value);

    const hasRole = (role: UserRole | string): boolean => {
        return roles.value.includes(role) || user.value?.role === role;
    };

    const hasPermission = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    const isAdmin = computed(() => user.value?.role === UserRole.ADMIN);
    const isManager = computed(() => hasRole(UserRole.MANAGER));
    const isRegularUser = computed(() => hasRole(UserRole.USER));
    const avatarUrl = computed(() => user.value?.avatar_url || null);

    // Actions
    const setAuthData = (data: any): void => {
        // Extraire les données de la réponse
        const payload = extractData<any>(data);

        token.value = payload.access_token;
        user.value = payload.user;

        roles.value = payload.user?.role ? [payload.user.role] : [];
        permissions.value = payload.user?.permissions ?? [];

        authService.setToken(payload.access_token);
    };

    const clearAuthData = (): void => {
        token.value = null;
        user.value = null;
        roles.value = [];
        permissions.value = [];
        authService.removeToken();
    };

    const login = async (credentials: any) => {
        try {
            const data = await authService.login(credentials);
            setAuthData(data);
            await redirectBasedOnRole();
            return data;
        } catch (error: any) {
            throw error;
        }
    };

    const register = async (userData: any) => {
        try {
            const data = await authService.register(userData);
            setAuthData(data);
            await redirectBasedOnRole();
            return data;
        } catch (error: any) {
            throw error;
        }
    };

    const logout = async () => {
        try {
            await authService.logout();
        } catch (error) {
            console.error("Logout error:", error);
        } finally {
            clearAuthData();
            router.push({ name: "login" });
        }
    };

    const refreshToken = async () => {
        try {
            const response = await authService.refreshToken();
            const data = extractData<{ access_token: string }>(response);
            token.value = data.access_token;
            authService.setToken(data.access_token);
            return data;
        } catch (error: any) {
            clearAuthData();
            throw error;
        }
    };

    const fetchUser = async (): Promise<User> => {
        try {
            const response = await authService.getMe();
            const userData = extractData<User>(response);
            user.value = userData;
            return userData;
        } catch (error: any) {
            clearAuthData();
            throw error;
        }
    };

    const fetchProfile = async (): Promise<ProfileData> => {
        try {
            const response = await authService.getProfile();
            const profile = extractData<ProfileData>(response);

            user.value = profile as User;
            roles.value = profile?.role ? [profile.role] : [];
            permissions.value = profile?.permissions || [];

            return profile;
        } catch (error: any) {
            throw error;
        }
    };

    const checkToken = async (): Promise<boolean> => {
        if (!token.value) return false;

        try {
            await fetchProfile();
            return true;
        } catch (error: any) {
            if (error.response?.status === 401) {
                try {
                    await refreshToken();
                    await fetchProfile();
                    return true;
                } catch {
                    clearAuthData();
                    return false;
                }
            }
            return false;
        }
    };

    const redirectBasedOnRole = async () => {
        await fetchProfile();

        if (hasRole(UserRole.ADMIN)) {
            router.push({ name: "dashboard" });
        } else if (hasRole(UserRole.MANAGER)) {
            router.push({ name: "manager.dashboard" });
        } else {
            router.push({ name: "dashboard" });
        }
    };

    const updateProfile = async (profileData: any): Promise<User> => {
        try {
            const response = await authService.updateProfile(profileData);
            const userData = extractData<User>(response);
            user.value = userData;
            return userData;
        } catch (error: any) {
            throw error;
        }
    };

    const uploadAvatar = async (avatarFile: File): Promise<User> => {
        try {
            const response = await authService.uploadAvatar(avatarFile);
            const avatarData = extractData<User>(response);
            user.value = { ...user.value, ...avatarData };
            return avatarData;
        } catch (error: any) {
            throw error;
        }
    };

    const removeAvatar = async (): Promise<void> => {
        try {
            await authService.removeAvatar();
            if (user.value) {
                user.value = {
                    ...user.value,
                    avatar: null,
                    avatar_url: null,
                };
            }
        } catch (error: any) {
            throw error;
        }
    };

    // Initialisation
    if (token.value) {
        authService.setToken(token.value);
    }

    return {
        // State
        user,
        token,
        roles,
        permissions,

        // Getters
        isAuthenticated,
        getUser,
        getRoles,
        getPermissions,
        hasRole,
        hasPermission,
        isAdmin,
        isManager,
        isRegularUser,
        avatarUrl,

        // Actions
        setAuthData,
        clearAuthData,
        login,
        register,
        logout,
        refreshToken,
        fetchUser,
        fetchProfile,
        checkToken,
        redirectBasedOnRole,
        updateProfile,
        uploadAvatar,
        removeAvatar,
    };
});
