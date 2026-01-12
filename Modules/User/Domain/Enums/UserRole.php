<?php

namespace Modules\User\Domain\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::MANAGER => 'Manager',
            self::USER => 'Utilisateur',
            self::VIEWER => 'Observateur',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
