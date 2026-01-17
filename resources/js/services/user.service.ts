import { api } from "@/services/api";
import type { User, PaginationMeta } from "@/types/User";

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
    async createUser(userData: any): Promise<{ data: User; message: string }> {
        // Vérifier si un fichier avatar est présent
        if (userData.avatar && userData.avatar instanceof File) {
            const formData = new FormData();

            // Ajouter le fichier
            formData.append("avatar", userData.avatar);

            // Ajouter les autres champs
            Object.keys(userData).forEach((key) => {
                if (key !== "avatar" && userData[key] !== undefined) {
                    formData.append(key, userData[key]);
                }
            });

            return await api.upload("/users", formData);
        }

        return await api.post("/users", userData);
    }

    // Mettre à jour un utilisateur
    async updateUser(
        id: string | number,
        userData: any
    ): Promise<{ data: User; message: string }> {
        // Vérifier si un fichier avatar est présent
        if (userData.avatar && userData.avatar instanceof File) {
            // Option 1: Uploader l'avatar séparément
            await this.uploadAvatar(id, userData.avatar);

            // Ensuite mettre à jour les autres données
            const { avatar, ...restData } = userData;
            return await api.put(`/users/${id}`, restData);
        }

        // Si pas de fichier, envoyer en JSON normal
        return await api.put(`/users/${id}`, userData);
    }

    async uploadAvatar(
        userId: string | number,
        avatarFile: File
    ): Promise<{ data: User; message: string }> {
        const formData = new FormData();
        formData.append("avatar", avatarFile);
        return await api.upload(`/users/${userId}/avatar`, formData);
    }

    // Supprimer un utilisateur
    async deleteUser(id: string | number): Promise<{ message: string }> {
        return await api.delete(`/users/${id}`);
    }
}

export const userService = new UserService();
