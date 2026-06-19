<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Owner = 'owner';
    case Barber = 'barber';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Super administrador',
            self::Owner => 'Dono',
            self::Barber => 'Profissional',
            self::Receptionist => 'Recepcionista',
        };
    }
}
