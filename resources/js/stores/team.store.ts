import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type {
    Team,
    TeamMember,
    TeamFilters,
    PaginatedResponse,
} from "@/services/team.service";
import { teamService } from "@/services/team.service";
import { useAuthStore } from "../stores/auth";

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

    // Getters (computed)
    const myTeams = computed(() => {
        return teams.value.filter(
            (team) =>
                (team.owner_id !== undefined &&
                    team.owner_id === authStore.user?.id!.toString()) ||
                teamMembers.value.some(
                    (member) => member.id === String(authStore.user?.id)
                )
        );
    });

    const ownedTeams = computed(() => {
        return teams.value.filter(
            (team) =>
                team.owner_id !== undefined &&
                team.owner_id === authStore.user?.id!.toString()
        );
    });

    const publicTeams = computed(() => {
        return teams.value.filter((team) => team.is_public);
    });

    const canEditCurrentTeam = computed(() => {
        if (!currentTeam.value || !authStore.user) return false;
        return teamService.canEditTeam(
            currentTeam.value,
            String(authStore.user.id)
        );
    });

    const canDeleteCurrentTeam = computed(() => {
        if (!currentTeam.value || !authStore.user) return false;
        return teamService.canDeleteTeam(
            currentTeam.value,
            String(authStore.user.id)
        );
    });

    // Actions
    async function fetchTeams(filters: TeamFilters = {}) {
        isLoading.value = true;
        error.value = null;

        try {
            const response: PaginatedResponse<Team> =
                await teamService.getTeams(filters);
            teams.value = response.data;
            pagination.value = response.meta;
            return response;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors du chargement des équipes";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchTeam(id: string) {
        isLoading.value = true;
        error.value = null;

        try {
            currentTeam.value = await teamService.getTeam(id);
            return currentTeam.value;
        } catch (err: any) {
            error.value =
                err.response?.data?.message ||
                "Erreur lors du chargement de l'équipe";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchTeamMembers(teamId: string) {
        isLoading.value = true;
        error.value = null;

        try {
            teamMembers.value = await teamService.getTeamMembers(teamId);
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
            const team = await teamService.createTeam(data);
            teams.value.unshift(team);
            return team;
        } catch (err: any) {
            error.value =
                err.response?.data?.message || "Erreur lors de la création";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateTeam(id: string, data: Partial<Team>) {
        isLoading.value = true;
        error.value = null;

        try {
            const team = await teamService.updateTeam(id, data);

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

    async function deleteTeam(id: string) {
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

    async function addTeamMember(teamId: string, userId: string) {
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

    async function removeTeamMember(teamId: string, userId: string) {
        isLoading.value = true;
        error.value = null;

        try {
            await teamService.removeMember(teamId, userId);

            // Retirer de la liste locale
            teamMembers.value = teamMembers.value.filter(
                (member) => member.id !== userId
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

    async function fetchMyTeams() {
        isLoading.value = true;
        error.value = null;

        try {
            const myTeams = await teamService.getMyTeams();
            // Fusionner avec les équipes existantes
            const existingIds = teams.value.map((t) => t.id);
            const newTeams = myTeams.filter(
                (team) => !existingIds.includes(team.id)
            );
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

        // Actions
        fetchTeams,
        fetchTeam,
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
