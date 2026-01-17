import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { userService } from "@/services/user.service";
import type { User, PaginationMeta, UserFilters } from "@/types/User";

export const useUserStore = defineStore("user", () => {
    // State
    const users = ref<User[]>([]);
    const currentUser = ref<User | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Pagination state
    const meta = ref<PaginationMeta>({
        total: 0,
        per_page: 15,
        current_page: 1,
        last_page: 1,
    });

    // Getters
    const totalUsers = computed(() => meta.value.total);
    const currentPage = computed(() => meta.value.current_page);
    const lastPage = computed(() => meta.value.last_page);
    const perPage = computed(() => meta.value.per_page);
    const from = computed(() => (currentPage.value - 1) * perPage.value + 1);
    const to = computed(() =>
        Math.min(currentPage.value * perPage.value, totalUsers.value)
    );

    // Actions
    const fetchUsers = async (
        page = 1,
        perPage = 15,
        filters: UserFilters = {}
    ) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await userService.getUsers(page, perPage, filters);
            users.value = response.data;
            meta.value = response.meta;
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Failed to fetch users";
            console.error("Error fetching users:", err);
        } finally {
            loading.value = false;
        }
    };

    const fetchUser = async (id: string | number) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await userService.getUser(id);
            currentUser.value = response.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || "Failed to fetch user";
            console.error("Error fetching user:", err);
        } finally {
            loading.value = false;
        }
    };

    const createUser = async (userData: Partial<User>) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await userService.createUser(userData);
            // Ajouter le nouvel utilisateur à la liste
            users.value.unshift(response.data);
            meta.value.total += 1;
            return response.data;
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Failed to create user";
            console.error("Error creating user:", err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateUser = async (id: string | number, userData: Partial<User>) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await userService.updateUser(id, userData);

            // Mettre à jour l'utilisateur dans la liste
            const index = users.value.findIndex((user) => user.id === id);
            if (index !== -1) {
                users.value[index] = response.data;
            }

            // Si c'est l'utilisateur courant, le mettre à jour aussi
            if (currentUser.value?.id === id) {
                currentUser.value = response.data;
            }

            return response.data;
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Failed to update user";
            console.error("Error updating user:", err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteUser = async (id: string | number) => {
        loading.value = true;
        error.value = null;

        try {
            await userService.deleteUser(id);

            // Supprimer l'utilisateur de la liste
            users.value = users.value.filter((user) => user.id !== id);
            meta.value.total -= 1;
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Failed to delete user";
            console.error("Error deleting user:", err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const changePage = async (page: number) => {
        await fetchUsers(page, meta.value.per_page);
    };

    const searchUsers = async (search: string) => {
        await fetchUsers(1, meta.value.per_page, { search });
    };

    const filterByRole = async (role: string) => {
        await fetchUsers(1, meta.value.per_page, { role });
    };

    // Reset store
    const reset = () => {
        users.value = [];
        currentUser.value = null;
        loading.value = false;
        error.value = null;
        meta.value = {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        };
    };

    return {
        // State
        users,
        currentUser,
        loading,
        error,
        meta,

        // Getters
        totalUsers,
        currentPage,
        lastPage,
        perPage,
        from,
        to,

        // Actions
        fetchUsers,
        fetchUser,
        createUser,
        updateUser,
        deleteUser,
        changePage,
        searchUsers,
        filterByRole,
        reset,
    };
});
