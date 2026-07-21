<?php

namespace App\Enums;

enum ConversionOperation: string
{
    case Multiply = 'multiply';
    case Divide = 'divide';

    public function label(): string
    {
        return match ($this) {
            self::Multiply => 'Multiply (×)',
            self::Divide => 'Divide (÷)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Multiply => '×',
            self::Divide => '÷',
        };
    }

    /**
     * Convert a value at the given rate using this operation.
     */
    public function apply(float $value, float $rate): float
    {
        return match ($this) {
            self::Multiply => $value * $rate,
            self::Divide => $value / $rate,
        };
    }
}
