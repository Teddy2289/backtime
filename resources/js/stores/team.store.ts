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
import { UserRole } from "@/enums/user-role"; // Assurez-vous d'importer UserRole

export const useTeamStore = defineStore("team", () => {
    const authStore = useAuthStore();

    // State (inchangé)
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
    
    // Getters (computed)
    const myTeams = computed(() => {
        const user = authStore.user;
        if (!user?.id) return [];

        const userId = String(user.id);
        return safeTeams.value.filter((team) => {
            if (!team) return false;
            return (
                (team.owner_id && String(team.owner_id) === userId) ||
                teamMembers.value.some((member) => member.id === userId)
            );
        });
    });

    const ownedTeams = computed(() => {
        const user = authStore.user;
        if (!user?.id) return [];

        const userId = String(user.id);
        return safeTeams.value.filter(
            (team) => team?.owner_id && String(team.owner_id) === userId
        );
    });
    
    const publicTeams = computed(() => {
        return safeTeams.value.filter((team) => team?.is_public);
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

    // NOUVEAU : Vérifier si l'utilisateur peut créer une équipe
    const canCreateTeam = computed(() => {
        const user = authStore.user;
        if (!user?.id) return false;
        
        // Seuls les admins et managers peuvent créer des équipes
        return authStore.hasRole(UserRole.ADMIN) || 
               authStore.hasRole(UserRole.MANAGER);
    });

    // Actions

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

    // MODIFIÉ : Fonction createTeam avec vérification des permissions
async function createTeam(data: {
    name: string;
    description?: string;
    is_public?: boolean;
}) {
    isLoading.value = true;
    error.value = null;

    try {
        // Vérifier les permissions avant de créer
        if (!canCreateTeam.value) {
            error.value = "Seuls les administrateurs et managers peuvent créer des équipes";
            throw new Error(error.value);
        }

        // Vérifier que l'utilisateur est connecté et a un ID
        if (!authStore.user?.id) {
            error.value = "Utilisateur non connecté";
            throw new Error(error.value);
        }

        // Préparer les données avec owner_id
        const teamData = {
            ...data,
            owner_id: authStore.user.id.toString() // Convertir en string si nécessaire
        };

        console.log("📝 Création d'équipe avec données:", teamData);

        const team = await teamService.createTeam(teamData);
        teams.value.unshift(team);
        return team;
    } catch (err: any) {
        // Ne pas écraser l'erreur de permission personnalisée
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

    async function updateTeam(id: string, data: Partial<Team>) {
        isLoading.value = true;
        error.value = null;

        try {
            const team = await teamService.updateTeam(id, data as any);

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
    
    async function fetchTeams(filters: TeamFilters = {}) {
        isLoading.value = true;
        error.value = null;

        try {
            console.log("🔄 Fetching teams with filters:", filters);

            const response = await teamService.getTeams(filters);

            console.log("📦 Service response:", response);
            console.log("📦 Response data type:", typeof response.data);
            console.log("📦 Is array?", Array.isArray(response.data));

            // CORRECTION CRITIQUE : Vérifiez bien la structure
            if (response && response.data) {
                // Si response.data est déjà un tableau
                if (Array.isArray(response.data)) {
                    teams.value = response.data;
                }
                // Si response.data est un objet avec une propriété data (cas Laravel)
                else if (
                    response.data.data &&
                    Array.isArray(response.data.data)
                ) {
                    teams.value = response.data.data;
                } else {
                    teams.value = [];
                }
            } else {
                teams.value = [];
            }

            console.log("✅ Teams after assignment:", teams.value);
            console.log("✅ Teams length:", teams.value.length);

            if (response.meta) {
                pagination.value = {
                    current_page: response.meta.current_page || 1,
                    last_page: response.meta.last_page || 1,
                    per_page: response.meta.per_page || 15,
                    total: response.meta.total || 0,
                };
            }

            return response;
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
        canCreateTeam, // AJOUTÉ

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