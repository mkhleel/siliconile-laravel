<?php

use Illuminate\Support\Collection;
use Modules\Cms\Models\Navigation;

if (! function_exists('menu')) {
    function menu($key): Collection
    {
        $menu = Navigation::where('key', $key)->first();

        return $menu ? collect($menu->items) : collect([]);
    }
}
