<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Components;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Reusable form field builders for common patterns.
 *
 * Usage:
 *   FormFields::name()
 *   FormFields::slug('name')
 *   FormFields::money('price', 'EGP')
 *   FormFields::enumSelect('status', StatusEnum::class)
 */
class FormFields
{
    /**
     * Create a name input field with slug auto-generation.
     *
     * @param  string  $slugField  The slug field to auto-populate
     */
    public static function name(string $slugField = 'slug'): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state) use ($slugField): void {
                $set($slugField, Str::slug($state ?? ''));
            });
    }

    /**
     * Create a title input field with slug auto-generation.
     *
     * @param  string  $slugField  The slug field to auto-populate
     */
    public static function title(string $slugField = 'slug'): TextInput
    {
        return TextInput::make('title')
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state) use ($slugField): void {
                $set($slugField, Str::slug($state ?? ''));
            });
    }

    /**
     * Create a slug input field.
     *
     * @param  string|null  $uniqueTable  Table name for unique validation
     */
    public static function slug(?string $uniqueTable = null): TextInput
    {
        $field = TextInput::make('slug')
            ->required()
            ->maxLength(255)
            ->alphaDash();

        if ($uniqueTable) {
            $field->unique($uniqueTable, 'slug', ignoreRecord: true);
        } else {
            $field->unique(ignoreRecord: true);
        }

        return $field;
    }

    /**
     * Create a money/currency input field.
     *
     * @param  string  $name  Field name
     * @param  string  $currency  Currency prefix
     */
    public static function money(string $name, string $currency = 'EGP'): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->prefix($currency)
            ->step(0.01)
            ->minValue(0);
    }

    /**
     * Create a required money input field.
     *
     * @param  string  $name  Field name
     * @param  string  $currency  Currency prefix
     */
    public static function moneyRequired(string $name, string $currency = 'EGP'): TextInput
    {
        return self::money($name, $currency)->required();
    }

    /**
     * Create an enum-based select field.
     *
     * @param  string  $name  Field name
     * @param  class-string<BackedEnum>  $enumClass  Enum class with options() static method
     */
    public static function enumSelect(string $name, string $enumClass): Select
    {
        $options = [];

        if (method_exists($enumClass, 'options')) {
            $options = $enumClass::options();
        } elseif (method_exists($enumClass, 'cases')) {
            $options = collect($enumClass::cases())
                ->mapWithKeys(fn ($case) => [$case->value => method_exists($case, 'label') ? $case->label() : $case->name])
                ->all();
        }

        return Select::make($name)
            ->options($options)
            ->native(false);
    }

    /**
     * Create a required enum select field.
     *
     * @param  string  $name  Field name
     * @param  class-string<BackedEnum>  $enumClass  Enum class
     */
    public static function enumSelectRequired(string $name, string $enumClass): Select
    {
        return self::enumSelect($name, $enumClass)->required();
    }

    /**
     * Create an active/inactive toggle.
     *
     * @param  string  $name  Field name
     * @param  string  $label  Label text
     */
    public static function activeToggle(string $name = 'is_active', string $label = 'Active'): Toggle
    {
        return Toggle::make($name)
            ->label($label)
            ->default(true);
    }

    /**
     * Create a featured toggle.
     */
    public static function featuredToggle(string $name = 'is_featured', string $label = 'Featured'): Toggle
    {
        return Toggle::make($name)
            ->label($label)
            ->default(false);
    }

    /**
     * Create a date picker with common defaults.
     *
     * @param  string  $name  Field name
     * @param  string|null  $label  Custom label
     */
    public static function date(string $name, ?string $label = null): DatePicker
    {
        $field = DatePicker::make($name)
            ->native(false);

        if ($label) {
            $field->label($label);
        }

        return $field;
    }

    /**
     * Create a datetime picker with common defaults.
     *
     * @param  string  $name  Field name
     * @param  string|null  $label  Custom label
     * @param  int  $minutesStep  Minutes step for time picker
     */
    public static function dateTime(string $name, ?string $label = null, int $minutesStep = 15): DateTimePicker
    {
        $field = DateTimePicker::make($name)
            ->native(false)
            ->seconds(false)
            ->minutesStep($minutesStep);

        if ($label) {
            $field->label($label);
        }

        return $field;
    }

    /**
     * Create a sort order input field.
     */
    public static function sortOrder(string $name = 'sort_order'): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->default(0)
            ->minValue(0);
    }

    /**
     * Create an email input field.
     */
    public static function email(string $name = 'email'): TextInput
    {
        return TextInput::make($name)
            ->email()
            ->required()
            ->maxLength(255);
    }

    /**
     * Create a phone input field.
     */
    public static function phone(string $name = 'phone'): TextInput
    {
        return TextInput::make($name)
            ->tel()
            ->maxLength(20);
    }

    /**
     * Create a URL input field.
     */
    public static function url(string $name, ?string $label = null): TextInput
    {
        $field = TextInput::make($name)
            ->url()
            ->maxLength(255);

        if ($label) {
            $field->label($label);
        }

        return $field;
    }
}
