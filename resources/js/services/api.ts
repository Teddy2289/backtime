import axios from "axios";
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from "axios";

class ApiService {
    private axiosInstance: AxiosInstance;

    constructor() {
        this.axiosInstance = axios.create({
            baseURL: "http://localhost:8000/api",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/json",
            },
            withCredentials: false,
        });

        this.setupInterceptors();
    }

    private setupInterceptors(): void {
        // Intercepteur de requête
        this.axiosInstance.interceptors.request.use(
            (config) => {
                const token = localStorage.getItem("token");
                if (token) {
                    config.headers.Authorization = `Bearer ${token}`;
                }
                return config;
            },
            (error) => {
                return Promise.reject(error);
            }
        );

        // Intercepteur de réponse
        this.axiosInstance.interceptors.response.use(
            (response: AxiosResponse) => response,
            (error) => {
                if (error.response?.status === 401) {
                    localStorage.removeItem("token");
                    delete this.axiosInstance.defaults.headers.common[
                        "Authorization"
                    ];

                    if (!window.location.pathname.includes("/login")) {
                        window.location.href = "/login";
                    }
                }
                return Promise.reject(error);
            }
        );
    }

    // Méthodes HTTP
    public get<T = any>(url: string, config?: AxiosRequestConfig): Promise<T> {
        return this.axiosInstance
            .get<T>(url, config)
            .then((response) => response.data);
    }

    public post<T = any>(
        url: string,
        data?: any,
        config?: AxiosRequestConfig
    ): Promise<T> {
        return this.axiosInstance
            .post<T>(url, data, config)
            .then((response) => response.data);
    }

    public put<T = any>(
        url: string,
        data?: any,
        config?: AxiosRequestConfig
    ): Promise<T> {
        return this.axiosInstance
            .put<T>(url, data, config)
            .then((response) => response.data);
    }

    public delete<T = any>(
        url: string,
        config?: AxiosRequestConfig
    ): Promise<T> {
        return this.axiosInstance
            .delete<T>(url, config)
            .then((response) => response.data);
    }

    public patch<T = any>(
        url: string,
        data?: any,
        config?: AxiosRequestConfig
    ): Promise<T> {
        return this.axiosInstance
            .patch<T>(url, data, config)
            .then((response) => response.data);
    }

    // Pour les uploads de fichiers
    public upload<T = any>(url: string, formData: FormData): Promise<T> {
        return this.axiosInstance
            .post<T>(url, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((response) => response.data);
    }

    // Méthode pour mettre à jour le token
    public setToken(token: string): void {
        localStorage.setItem("token", token);
        this.axiosInstance.defaults.headers.common[
            "Authorization"
        ] = `Bearer ${token}`;
    }

    // Méthode pour supprimer le token
    public removeToken(): void {
        localStorage.removeItem("token");
        delete this.axiosInstance.defaults.headers.common["Authorization"];
    }
}

// Export d'une instance unique
export const api = new ApiService();
