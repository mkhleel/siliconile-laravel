<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Http\Controllers\MembershipController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('memberships', MembershipController::class)->names('membership');
});
