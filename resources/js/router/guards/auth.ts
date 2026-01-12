import type { NavigationGuard } from "vue-router";
import { useAuthStore } from "@/stores/auth";

export const authGuard: NavigationGuard = async (to, from, next) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
    const guestOnly = to.matched.some((record) => record.meta.guestOnly);
    const requiredRole =
        typeof to.meta.requiredRole === "string"
            ? to.meta.requiredRole
            : undefined;

    // Si la route nécessite une authentification
    if (requiresAuth && !authStore.isAuthenticated) {
        next({ name: "login" });
        return;
    }

    // Si la route est réservée aux invités
    if (guestOnly && authStore.isAuthenticated) {
        next({ name: "dashboard" });
        return;
    }

    // Vérification des rôles
    if (requiredRole && !authStore.hasRole(requiredRole)) {
        next({ name: "dashboard" });
        return;
    }

    // Vérifier si le token est valide pour les routes protégées
    if (requiresAuth) {
        const isValid = await authStore.checkToken();
        if (!isValid) {
            authStore.clearAuthData();
            next({ name: "login" });
            return;
        }
    }

    next();
};
