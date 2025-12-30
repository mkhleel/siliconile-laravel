<?php

namespace Modules\Core\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Modules\Core\Concerns\SwitchDriver;

/**
 * @property int $id
 * @property string $name
 * @property string $iso
 * @property string $translations
 * @property float $exchange_rate
 * @property string $symbol
 * @property bool $is_activated
 * @property string $position
 * @property string $created_at
 * @property string $updated_at
 */
class Currency extends Model
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
    protected $fillable = ['translations', 'exchange_rate', 'symbol', 'is_activated', 'name', 'iso', 'created_at', 'updated_at'];

    protected $casts = [
        'translations' => 'json',
        'is_activated' => 'boolean',
    ];

    public function getSchema()
    {
        return [
            'translations',
            'exchange_rate',
            'symbol',
            'is_activated',
            'name',
            'iso',
            'created_at',
            'updated_at',
        ];
    }

    public function getRows()
    {
        $currencyJson = __DIR__.'/../../../database/data/currencies.json';
        $jsonFileExists = File::exists($currencyJson);
        if ($jsonFileExists) {
            $data = json_decode(File::get($currencyJson), true);

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
