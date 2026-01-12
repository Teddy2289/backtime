import axios from "axios";
import { useAuthStore } from "./stores/auth";

// Configuration Axios
axios.defaults.baseURL = "/api";
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.withCredentials = true;

// Intercepteur de réponse pour gérer les erreurs
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const authStore = useAuthStore();
            authStore.clearAuthData();

            // Rediriger vers login seulement si on n'est pas déjà sur la page login
            if (!window.location.pathname.includes("/login")) {
                window.location.href = "/login";
            }
        }
        return Promise.reject(error);
    }
);
