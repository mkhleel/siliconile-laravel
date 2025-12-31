<?php

declare(strict_types=1);

namespace Modules\Incubation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Startup stage enum representing business maturity.
 */
enum StartupStage: string implements HasColor, HasLabel
{
    case IDEA = 'idea';
    case MVP = 'mvp';
    case EARLY_TRACTION = 'early_traction';
    case GROWTH = 'growth';
    case SCALING = 'scaling';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IDEA => 'Idea Stage',
            self::MVP => 'MVP / Prototype',
            self::EARLY_TRACTION => 'Early Traction',
            self::GROWTH => 'Growth',
            self::SCALING => 'Scaling',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::IDEA => 'gray',
            self::MVP => 'info',
            self::EARLY_TRACTION => 'warning',
            self::GROWTH => 'success',
            self::SCALING => 'primary',
        };
    }

    /**
     * Get options for select fields.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
