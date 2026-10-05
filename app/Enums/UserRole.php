<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Customer = 'customer';

    /**
     * Get the translated, human-readable name of the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Administrator'),
            self::Owner => __('Owner'),
            self::Customer => __('Customer'),
        };
    }

    /**
     * Get every role as a value/label pair for select inputs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
