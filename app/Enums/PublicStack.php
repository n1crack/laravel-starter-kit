<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How the public, unauthenticated pages are rendered. The admin and user
 * panels are always React, regardless of this choice.
 */
enum PublicStack: string
{
    case Livewire = 'livewire';
    case React = 'react';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Livewire->value => self::Livewire->label(),
            self::React->value => self::React->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Livewire => 'Blade + Livewire',
            self::React => 'React (Inertia)',
        };
    }
}
