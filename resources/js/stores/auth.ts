import { defineStore } from "pinia";
import { ref, computed, watch } from "vue";
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

    console.log("user.value?.role", user.value?.role);

    const isManager = computed(() => hasRole(UserRole.MANAGER));
    const isRegularUser = computed(() => hasRole(UserRole.USER));
    const avatarUrl = computed(() => user.value?.avatar_url || null);

    // Actions
    const setAuthData = (data: any): void => {
        // réponse API = { success, message, data: { access_token, user } }
        const payload = data.data;

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
            const data = await authService.refreshToken();
            token.value = data.access_token;
            authService.setToken(data.access_token);
            return data;
        } catch (error: any) {
            clearAuthData();
            throw error;
        }
    };

    const fetchUser = async () => {
        try {
            const data = await authService.getMe();
            user.value = data.data;
            return data.data;
        } catch (error: any) {
            clearAuthData();
            throw error;
        }
    };

    const fetchProfile = async () => {
        try {
            const response = await authService.getProfile();

            const profile = response.data;

            user.value = profile;
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

    const updateProfile = async (profileData: any) => {
        try {
            const data = await authService.updateProfile(profileData);
            user.value = data;
            return data;
        } catch (error: any) {
            throw error;
        }
    };

    const uploadAvatar = async (avatarFile: File) => {
        try {
            const data = await authService.uploadAvatar(avatarFile);
            user.value = { ...user.value, ...data.data };
            return data;
        } catch (error: any) {
            throw error;
        }
    };

    const removeAvatar = async () => {
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
