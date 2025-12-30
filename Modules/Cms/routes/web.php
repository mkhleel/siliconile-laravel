<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cms', CmsController::class)->names('cms');
});
