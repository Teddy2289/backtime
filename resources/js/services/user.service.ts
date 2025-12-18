import { api } from "@/services/api";
import type { User, UsersResponse, PaginationMeta } from "@/types/User";

export class UserService {
    // Récupérer tous les utilisateurs avec pagination
    async getUsers(
        page = 1,
        perPage = 15,
        filters?: { search?: string; role?: string }
    ): Promise<{ data: User[]; meta: PaginationMeta }> {
        const params: any = {
            page,
            per_page: perPage,
            ...filters,
        };

        return await api.get("/users", { params });
    }

    // Récupérer un utilisateur par ID
    async getUser(id: string | number): Promise<{ data: User }> {
        return await api.get(`/users/${id}`);
    }

    // Créer un nouvel utilisateur
    async createUser(
        userData: Partial<User>
    ): Promise<{ data: User; message: string }> {
        return await api.post("/users", userData);
    }

    // Mettre à jour un utilisateur
    async updateUser(
        id: string | number,
        userData: Partial<User>
    ): Promise<{ data: User; message: string }> {
        return await api.put(`/users/${id}`, userData);
    }

    // Supprimer un utilisateur
    async deleteUser(id: string | number): Promise<{ message: string }> {
        return await api.delete(`/users/${id}`);
    }
}

export const userService = new UserService();
