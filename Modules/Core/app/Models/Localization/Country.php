<?php

namespace Modules\Core\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Modules\Core\Concerns\SwitchDriver;

class Country extends Model
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
    protected $fillable = [
        'name',
        'code',
        'phone',
        'lat',
        'lng',
        'created_at',
        'updated_at',
        'translations',
        'timezones',
        'numeric_code',
        'is_activated',
        'flag',
        'emojiU',
        'emoji',
        'wikiDataId',
        'currency_symbol',
        'currency_name',
        'currency',
        'region',
        'native',
        'tld',
        'capital',
        'nationality',
        'iso3',
    ];

    protected $casts = [
        'translations' => 'json',
        'timezones' => 'json',
        'is_activated' => 'boolean',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function getSchema()
    {
        return [
            'name',
            'code',
            'phone',
            'lat',
            'lng',
            'created_at',
            'updated_at',
            'translations',
            'timezones',
            'numeric_code',
            'is_activated',
            'flag',
            'emojiU',
            'emoji',
            'wikiDataId',
            'currency_symbol',
            'currency_name',
            'currency',
            'region',
            'native',
            'tld',
            'capital',
            'nationality',
            'iso3',
        ];
    }

    public function getRows()
    {
        $countryJson = __DIR__.'/../../../database/data/countries.json';

        $jsonFileExists = File::exists($countryJson);
        if ($jsonFileExists) {
            $data = json_decode(File::get($countryJson), true);

            // Convert string IDs to integers for proper Sushi handling
            return collect($data)->map(function ($item) {
                if (isset($item['id'])) {
                    $item['id'] = (int) $item['id'];
                }
                if (isset($item['is_activated'])) {
                    $item['is_activated'] = (bool) $item['is_activated'];
                }

                return $item;
            })->toArray();
        } else {
            return [];
        }
    }
}
