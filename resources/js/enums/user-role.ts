export enum UserRole {
    ADMIN = "admin",
    MANAGER = "manager",
    USER = "user",
    TEAMLEAD = "team_leader",
}

export const RoleLabels: Record<UserRole, string> = {
    [UserRole.ADMIN]: "Administrator",
    [UserRole.MANAGER]: "Manager",
    [UserRole.USER]: "User",
    [UserRole.TEAMLEAD]: "Team Lead",
};

export function getRoleLabel(role: UserRole | string): string {
    return RoleLabels[role as UserRole] || role;
}
