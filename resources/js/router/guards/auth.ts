import type { NavigationGuard } from "vue-router";
import { useAuthStore } from "@/stores/auth";

export const authGuard: NavigationGuard = async (to) => {
    const authStore = useAuthStore();

    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
    const guestOnly = to.matched.some((record) => record.meta.guestOnly);

    const requiredRole =
        typeof to.meta.requiredRole === "string"
            ? to.meta.requiredRole
            : undefined;

    // 🔒 Route protégée → utilisateur non connecté
    if (requiresAuth && !authStore.isAuthenticated) {
        return { name: "login" };
    }

    // 🚫 Route invité → utilisateur connecté
    if (guestOnly && authStore.isAuthenticated) {
        return { name: "dashboard" };
    }

    // 🎭 Vérification du rôle
    if (requiredRole && !authStore.hasRole(requiredRole)) {
        return { name: "dashboard" };
    }

    // 🔑 Vérification du token
    if (requiresAuth) {
        const isValid = await authStore.checkToken();

        if (!isValid) {
            authStore.clearAuthData();
            return { name: "login" };
        }
    }

    return true;
};
