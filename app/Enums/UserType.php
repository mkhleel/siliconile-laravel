<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case FREELANCER = 'freelancer';
    case STUDENT = 'student';
    case COMPANY = 'company';

    public function label(): string
    {
        return match ($this) {
            self::FREELANCER => 'Freelancer',
            self::STUDENT => 'Student',
            self::COMPANY => 'Company',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FREELANCER => 'heroicon-o-briefcase',
            self::STUDENT => 'heroicon-o-academic-cap',
            self::COMPANY => 'heroicon-o-building-office',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
