<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Navigation extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'items', 'activated', 'location'];

    /** @var array<int, string> */
    public array $translatable = ['items'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'json',
            'activated' => 'boolean',
        ];
    }
}
