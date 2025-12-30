<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Components;

use BackedEnum;
use Filament\Tables\Columns\TextColumn;

/**
 * Reusable table column builders for common patterns.
 *
 * Usage:
 *   TableColumns::status('status', StatusEnum::class)
 *   TableColumns::money('total', 'SDG')
 *   TableColumns::dateTime('created_at')
 */
class TableColumns
{
    /**
     * Create a status badge column with enum support.
     *
     * @param string $name Column name
     * @param class-string<BackedEnum>|null $enumClass Enum class with label() and color() methods
     */
    public static function status(string $name, ?string $enumClass = null): TextColumn
    {
        $column = TextColumn::make($name)
            ->badge()
            ->sortable();

        if ($enumClass && is_subclass_of($enumClass, BackedEnum::class)) {
            $column
                ->formatStateUsing(fn ($state): string => $state instanceof BackedEnum && method_exists($state, 'label')
                    ? $state->label()
                    : (string) $state
                )
                ->color(fn ($state): string => $state instanceof BackedEnum && method_exists($state, 'color')
                    ? $state->color()
                    : 'gray'
                );
        }

        return $column;
    }

    /**
     * Create a money/currency formatted column.
     *
     * @param string $name Column name
     * @param string $currency Currency code (e.g., 'SDG', 'USD')
     */
    public static function money(string $name, string $currency = 'SDG'): TextColumn
    {
        return TextColumn::make($name)
            ->money($currency)
            ->sortable();
    }

    /**
     * Create a formatted date column.
     *
     * @param string $name Column name
     * @param string $format Date format string
     */
    public static function date(string $name, string $format = 'M j, Y'): TextColumn
    {
        return TextColumn::make($name)
            ->date($format)
            ->sortable();
    }

    /**
     * Create a formatted datetime column.
     *
     * @param string $name Column name
     * @param string $format DateTime format string
     */
    public static function dateTime(string $name, string $format = 'M j, Y H:i'): TextColumn
    {
        return TextColumn::make($name)
            ->dateTime($format)
            ->sortable();
    }

    /**
     * Create a searchable text column with common defaults.
     *
     * @param string $name Column name
     * @param string|null $label Custom label
     */
    public static function text(string $name, ?string $label = null): TextColumn
    {
        $column = TextColumn::make($name)
            ->searchable()
            ->sortable();

        if ($label) {
            $column->label($label);
        }

        return $column;
    }

    /**
     * Create a timestamp column (usually hidden by default).
     *
     * @param string $name Column name (e.g., 'created_at', 'updated_at')
     */
    public static function timestamp(string $name): TextColumn
    {
        return TextColumn::make($name)
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Create a boolean/icon column.
     *
     * @param string $name Column name
     * @param string|null $label Custom label
     */
    public static function boolean(string $name, ?string $label = null): \Filament\Tables\Columns\IconColumn
    {
        $column = \Filament\Tables\Columns\IconColumn::make($name)
            ->boolean()
            ->sortable();

        if ($label) {
            $column->label($label);
        }

        return $column;
    }

    /**
     * Create a relationship count column.
     *
     * @param string $relationshipName Relationship method name
     * @param string|null $label Custom label
     */
    public static function count(string $relationshipName, ?string $label = null): TextColumn
    {
        $column = TextColumn::make("{$relationshipName}_count")
            ->counts($relationshipName)
            ->sortable();

        if ($label) {
            $column->label($label);
        }

        return $column;
    }
}
