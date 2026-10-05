<?php

namespace App\Enums;

enum Province: string
{
    case Huelva = 'huelva';
    case Cadiz = 'cadiz';
    case Malaga = 'malaga';
    case Granada = 'granada';
    case Almeria = 'almeria';

    /**
     * Get the province's display name (proper nouns, identical in every locale).
     */
    public function label(): string
    {
        return match ($this) {
            self::Huelva => 'Huelva',
            self::Cadiz => 'Cádiz',
            self::Malaga => 'Málaga',
            self::Granada => 'Granada',
            self::Almeria => 'Almería',
        };
    }

    /**
     * Get every province as a value/label pair for select inputs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $province): array => ['value' => $province->value, 'label' => $province->label()],
            self::cases(),
        );
    }
}
