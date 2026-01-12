export enum UserRole {
    ADMIN = "admin",
    MANAGER = "manager",
    USER = "user",
    TEAMLEAD = "team_leader",
}

export const RoleLabels: Record<UserRole, string> = {
    [UserRole.ADMIN]: "Administrateur",
    [UserRole.MANAGER]: "Gestionnaire",
    [UserRole.USER]: "Utilisateur",
    [UserRole.TEAMLEAD]: "Chef d'équipe",
};

export function getRoleLabel(role: UserRole | string): string {
    return RoleLabels[role as UserRole] || role;
}
