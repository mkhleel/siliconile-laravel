<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cms', CmsController::class)->names('cms');
});
