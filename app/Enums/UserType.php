<?php

namespace App\Enums;

enum UserType: string
{
    case Staff = 'staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Customer => 'Customer',
        };
    }
}
