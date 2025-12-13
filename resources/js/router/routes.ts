import type { RouteRecordRaw } from "vue-router";
import { UserRole } from "@/enums/user-role";

export const routes: RouteRecordRaw[] = [
    // Routes publiques
    {
        path: "/login",
        name: "login",
        component: () => import("@/views/auth/Login.vue"),
        meta: {
            layout: "GuestLayout",
            guestOnly: true,
            title: "Login",
        },
    },
    {
        path: "/register",
        name: "register",
        component: () => import("@/views/auth/Register.vue"),
        meta: {
            layout: "GuestLayout",
            guestOnly: true,
            title: "Register",
        },
    },
    {
        path: "/forgot-password",
        name: "forgot-password",
        component: () => import("@/views/auth/ForgotPassword.vue"),
        meta: {
            layout: "GuestLayout",
            guestOnly: true,
            title: "Forgot Password",
        },
    },

    // UN SEUL DASHBOARD POUR TOUS LES RÔLES
    {
        path: "/",
        name: "dashboard",
        component: () => import("@/views/dashboard/Dashboard.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Dashboard",
        },
    },

    // Routes protégées - Profile
    {
        path: "/profile",
        name: "profile",
        component: () => import("@/views/profile/Profile.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Profile",
        },
    },

    // === NOUVELLES ROUTES AJOUTÉES ===

    // Projets
    {
        path: "/projects",
        name: "projects",
        component: () => import("@/views/projects/Index.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Projects",
        },
    },

    // Tâches
    {
        path: "/tasks",
        name: "tasks",
        component: () => import("@/views/tasks/Index.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Tasks",
        },
    },
    {
        path: "/tasks/list",
        name: "tasks.list",
        component: () => import("@/views/tasks/List.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "All Tasks",
        },
    },
    {
        path: "/tasks/calendar",
        name: "tasks.calendar",
        component: () => import("@/views/tasks/Calendar.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Task Calendar",
        },
    },
    {
        path: "/tasks/reports",
        name: "tasks.reports",
        component: () => import("@/views/tasks/Reports.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Task Reports",
        },
    },

    // Équipes
    {
        path: "/teams",
        name: "teams",
        redirect: "/teams/list",
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Teams",
        },
        children: [
            {
                path: "list",
                name: "teams.list",
                component: () => import("@/views/teams/Index.vue"),
                meta: { title: "All Teams" },
            },
            {
                path: "projects",
                name: "teams.projects",
                component: () => import("@/views/teams/Projects.vue"),
                meta: { title: "Team Projects" },
            },
            {
                path: "performance",
                name: "teams.performance",
                component: () => import("@/views/teams/Performance.vue"),
                meta: { title: "Team Performance" },
            },
        ],
    },

    // Rapports
    {
        path: "/reports",
        name: "reports",
        component: () => import("@/views/reports/Index.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Reports",
        },
    },

    // Notifications
    {
        path: "/notifications",
        name: "notifications",
        component: () => import("@/views/notifications/Index.vue"),
        meta: {
            layout: "AppLayout",
            requiresAuth: true,
            title: "Notifications",
        },
    },

    // Routes spécifiques Admin
    {
        path: "/admin",
        name: "admin",
        redirect: "/", // Redirige vers le dashboard unique
        meta: {
            layout: "AdminLayout",
            requiresAuth: true,
            requiredRole: UserRole.ADMIN,
        },
        children: [
            {
                path: "users",
                name: "admin.users",
                component: () => import("@/views/admin/users/index.vue"),
                meta: { title: "User Management" },
            },
            {
                path: "users/create",
                name: "admin.users.create",
                component: () => import("@/views/admin/users/Create.vue"),
                meta: { title: "Create User" },
            },
            {
                path: "users/stats",
                name: "admin.users.stats",
                component: () => import("@/views/admin/users/Stats.vue"),
                meta: { title: "User Statistics" },
            },
            {
                path: "users/:id/edit",
                name: "admin.users.edit",
                component: () => import("@/views/admin/users/edit.vue"),
                meta: { title: "Edit User" },
            },
            {
                path: "roles",
                name: "admin.roles",
                component: () => import("@/views/admin/roles/Index.vue"),
                meta: { title: "Roles & Permissions" },
            },
            {
                path: "security",
                name: "admin.security",
                component: () => import("@/views/admin/security/Index.vue"),
                meta: { title: "Security Settings" },
            },
            {
                path: "settings",
                name: "admin.settings",
                component: () => import("@/views/admin/settings/Index.vue"),
                meta: { title: "System Settings" },
            },
        ],
    },

    // 404
    {
        path: "/:pathMatch(.*)*",
        name: "not-found",
        redirect: { name: "dashboard" },
    },
];
