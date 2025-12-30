<?php

namespace Modules\Core\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Modules\Core\Concerns\SwitchDriver;

/**
 * @property int $id
 * @property string $iso
 * @property string $name
 * @property string $arabic
 * @property string $created_at
 * @property string $updated_at
 */
class Language extends Model
{
    use SwitchDriver;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['translations', 'is_activated', 'iso', 'name', 'created_at', 'updated_at'];

    protected $casts = [
        'translations' => 'json',
        'is_activated' => 'boolean',
    ];

    // get only activated languages
    public function scopeActivated($query)
    {
        return $query->where('is_activated', true);
    }

    public function getSchema(): array
    {
        return [
            'translations',
            'is_activated',
            'iso',
            'name',
            'created_at',
            'updated_at',
        ];
    }

    public function getRows()
    {
        $languageJson = __DIR__.'/../../../database/data/languages.json';

        $jsonFileExists = File::exists($languageJson);
        if ($jsonFileExists) {
            $data = json_decode(File::get($languageJson), true);

            // Convert string IDs to integers for proper Sushi handling
            return collect($data)->map(function ($item) {
                $item['id'] = (int) $item['id'];
                $item['is_activated'] = (bool) $item['is_activated'];

                return $item;
            })->toArray();
        } else {
            return [];
        }

        //        $files = File::files(lang_path());
        //        $languages = [];
        //
        //        foreach ($files as $file) {
        //            if ($file->getExtension() === 'json') {
        //                $languages[] = [
        //                    'name' => $file->getFilenameWithoutExtension(),
        //                ];
        //            }
        //        }
        //
        //        return $languages;

    }
}
