import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type { Team, TeamMember } from "@/services/team.service";
import { teamService } from "@/services/team.service";
import { useAuthStore } from "../stores/auth";
import { UserRole } from "@/enums/user-role";

// Définir l'interface pour la réponse paginée
interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        to?: number;
        from?: number;
    };
}

export const useTeamStore = defineStore("team", () => {
    const authStore = useAuthStore();

    // State
    const teams = ref<Team[]>([]);
    const currentTeam = ref<Team | null>(null);
    const teamMembers = ref<TeamMember[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const safeTeams = computed(() => teams.value || []);

    // Getters
    const myTeams = computed(() => {
        const user = authStore.user;
        if (!user?.id) return [];

        const userId = user.id;
        return safeTeams.value.filter((team) => {
            if (!team) return false;
            return (
                (team.owner_id && Number(team.owner_id) === userId) ||
                teamMembers.value.some((member) => Number(member.id) === userId)
            );
        });
    });

    const ownedTeams = computed(() => {
        const user = authStore.user;
        if (!user?.id) return [];

        const userId = String(user.id);
        return safeTeams.value.filter(
            (team) => team?.owner_id && String(team.owner_id) === userId,
        );
    });

    const publicTeams = computed(() => {
        return safeTeams.value.filter((team) => team?.is_public);
    });

    const canEditCurrentTeam = computed(() => {
        if (!currentTeam.value || !authStore.user) return false;
        return teamService.canEditTeam(
            currentTeam.value,
            String(authStore.user.id),
        );
    });

    const canDeleteCurrentTeam = computed(() => {
        if (!currentTeam.value || !authStore.user) return false;
        return teamService.canDeleteTeam(
            currentTeam.value,
            String(authStore.user.id),
        );
    });

    const canCreateTeam = computed(() => {
        const user = authStore.user;
        if (!user?.id) return false;

        return (
            authStore.hasRole(UserRole.ADMIN) ||
            authStore.hasRole(UserRole.MANAGER)
        );
    });

    // Helper pour extraire les données de la réponse
    const extractData = <T>(response: any): T => {
        if (response && typeof response === "object") {
            // Structure { data: ..., meta: ... }
            if ("data" in response) {
                return response.data as T;
            }
        }
        return response as T;
    };

    // Actions
    async function fetchTeamMembers(teamId: number) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await teamService.getTeamMembers(teamId);
            const members = extractData<TeamMember[]>(response);
            teamMembers.value = Array.isArray(members) ? members : [];
            return teamMembers.value;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors du chargement des membres";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createTeam(data: {
        name: string;
        description?: string;
        is_public?: boolean;
    }) {
        isLoading.value = true;
        error.value = null;

        try {
            if (!canCreateTeam.value) {
                error.value =
                    "Seuls les administrateurs et managers peuvent créer des équipes";
                throw new Error(error.value);
            }

            if (!authStore.user?.id) {
                error.value = "Utilisateur non connecté";
                throw new Error(error.value);
            }

            const teamData = {
                ...data,
                owner_id: authStore.user.id.toString(),
            };

            console.log("📝 Création d'équipe avec données:", teamData);

            const response = await teamService.createTeam(teamData);
            const team = extractData<Team>(response);
            teams.value.unshift(team);
            return team;
        } catch (err: any) {
            if (!error.value) {
                error.value =
                    err.response?.data?.message ||
                    err.message ||
                    "Erreur lors de la création";
            }
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateTeam(id: number, data: Partial<Team>) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await teamService.updateTeam(id, data as any);
            const team = extractData<Team>(response);

            // Mettre à jour dans la liste
            const index = teams.value.findIndex((t) => t.id === id);
            if (index !== -1) {
                teams.value[index] = team;
            }

            // Mettre à jour l'équipe courante si c'est la même
            if (currentTeam.value?.id === id) {
                currentTeam.value = team;
            }

            return team;
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Erreur lors de la mise à jour";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function deleteTeam(id: number) {
        isLoading.value = true;
        error.value = null;

        try {
            await teamService.deleteTeam(id);

            // Retirer de la liste
            teams.value = teams.value.filter((team) => team.id !== id);

            // Réinitialiser l'équipe courante si c'est la même
            if (currentTeam.value?.id === id) {
                currentTeam.value = null;
            }
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Erreur lors de la suppression";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function addTeamMember(teamId: number, userId: number) {
        isLoading.value = true;
        error.value = null;

        try {
            await teamService.addMember(teamId, userId);

            // Recharger les membres
            await fetchTeamMembers(teamId);
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors de l'ajout du membre";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function removeTeamMember(teamId: number, userId: number) {
        isLoading.value = true;
        error.value = null;

        try {
            await teamService.removeMember(teamId, userId);

            // Retirer de la liste locale
            teamMembers.value = teamMembers.value.filter(
                (member) => member.id !== userId,
            );
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors du retrait du membre";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchTeams(filters: any = {}) {
        isLoading.value = true;
        error.value = null;

        try {
            console.log("🔄 Fetching teams with filters:", filters);

            const response = await teamService.getTeams(filters);

            console.log("📦 Service response:", response);

            // Extraire les données de la réponse
            const extractedData = extractData<Team[] | PaginatedResponse<Team>>(
                response,
            );

            let teamsArray: Team[] = [];
            let metaData = null;

            // Vérifier la structure
            if (Array.isArray(extractedData)) {
                teamsArray = extractedData;
            } else if (
                extractedData &&
                typeof extractedData === "object" &&
                "data" in extractedData
            ) {
                teamsArray = Array.isArray(extractedData.data)
                    ? extractedData.data
                    : [];
                if ("meta" in extractedData) {
                    metaData = extractedData.meta;
                }
            }

            teams.value = teamsArray;

            console.log("✅ Teams after assignment:", teams.value);
            console.log("✅ Teams length:", teams.value.length);

            // Mettre à jour la pagination si disponible
            if (metaData) {
                pagination.value = {
                    current_page: metaData.current_page || 1,
                    last_page: metaData.last_page || 1,
                    per_page: metaData.per_page || 15,
                    total: metaData.total || 0,
                };
            } else {
                // Réinitialiser la pagination si pas de meta
                pagination.value = {
                    current_page: 1,
                    last_page: 1,
                    per_page: 15,
                    total: teamsArray.length,
                };
            }

            return { data: teamsArray, meta: metaData };
        } catch (err: any) {
            console.error("❌ Error fetching teams:", err);
            error.value =
                err.response?.data?.message ||
                "Erreur lors du chargement des équipes";
            teams.value = [];
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchMyTeams() {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await teamService.getMyTeams();
            const myTeams = extractData<Team[]>(response);

            // Fusionner avec les équipes existantes
            const existingIds = teams.value.map((t) => t.id);
            const newTeams = Array.isArray(myTeams)
                ? myTeams.filter((team) => !existingIds.includes(team.id))
                : [];

            teams.value.push(...newTeams);
            return myTeams;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors du chargement de vos équipes";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    // Réinitialiser le store
    function reset() {
        teams.value = [];
        currentTeam.value = null;
        teamMembers.value = [];
        isLoading.value = false;
        error.value = null;
        pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        };
    }

    return {
        // State
        teams,
        currentTeam,
        teamMembers,
        isLoading,
        error,
        pagination,

        // Getters
        myTeams,
        ownedTeams,
        publicTeams,
        canEditCurrentTeam,
        canDeleteCurrentTeam,
        canCreateTeam,

        // Actions
        fetchTeams,
        fetchTeamMembers,
        createTeam,
        updateTeam,
        deleteTeam,
        addTeamMember,
        removeTeamMember,
        fetchMyTeams,
        reset,
    };
});
