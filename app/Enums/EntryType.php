<?php

namespace App\Enums;

enum EntryType: string
{
    case Received = 'received';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::Paid => 'Paid',
        };
    }
}
