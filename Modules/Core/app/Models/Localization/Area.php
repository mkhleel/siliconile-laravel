<?php

namespace Modules\Core\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Modules\Core\Concerns\SwitchDriver;

class Area extends Model
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
    protected $fillable = ['name', 'translations', 'is_activated', 'city_id', 'lat', 'lng', 'created_at', 'updated_at'];

    protected $casts = [
        'translations' => 'json',
        'is_activated' => 'boolean',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function getSchema()
    {
        return [
            'name',
            'translations',
            'is_activated',
            'city_id',
            'lat',
            'lng',
            'created_at',
            'updated_at',
        ];
    }

    public function getRows()
    {
        $areaJson = __DIR__.'/../../../database/data/areas.json';

        $jsonFileExists = File::exists($areaJson);
        if ($jsonFileExists) {
            $data = json_decode(File::get($areaJson), true);

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
