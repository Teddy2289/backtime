import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { authGuard } from "./guards/auth";
import type { RouteLocationNormalized } from "vue-router";

const router = createRouter({
    history: createWebHistory("/administrateur/"),
    routes,
    scrollBehavior(_to, _from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    },
});

// Guard global
router.beforeEach(authGuard);

// Mettre à jour le titre de la page
router.afterEach((to: RouteLocationNormalized) => {
    const title = to.meta.title || "My App";
    document.title = `${title} - Administration`;
});

export default router;
