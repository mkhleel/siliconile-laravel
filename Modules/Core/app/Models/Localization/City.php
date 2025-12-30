<?php

namespace Modules\Core\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Modules\Core\Concerns\SwitchDriver;

class City extends Model
{
    use SwitchDriver;

    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['name', 'translations', 'is_activated', 'country_id', 'lat', 'lng', 'created_at', 'updated_at'];

    protected $casts = [
        'translations' => 'json',
        'is_activated' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function getSchema()
    {
        return [
            'name',
            'translations',
            'is_activated',
            'country_id',
            'lat',
            'lng',
            'created_at',
            'updated_at',
        ];
    }

    public function getRows()
    {
        $cityJson = __DIR__.'/../../../database/data/cities.json';

        $jsonFileExists = File::exists($cityJson);
        if ($jsonFileExists) {
            $data = json_decode(File::get($cityJson), true);

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
