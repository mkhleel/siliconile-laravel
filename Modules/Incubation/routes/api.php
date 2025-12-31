<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Incubation Module
|--------------------------------------------------------------------------
|
| API endpoints for the incubation module.
|
*/

Route::prefix('incubation')->name('api.incubation.')->group(function () {
    // Public cohort listing
    Route::get('/cohorts', function () {
        return \Modules\Incubation\Models\Cohort::query()
            ->acceptingApplications()
            ->public()
            ->select(['id', 'name', 'slug', 'description', 'application_start_date', 'application_end_date'])
            ->get();
    })->name('cohorts.index');

    // Check application status by code
    Route::get('/applications/{applicationCode}/status', function (string $applicationCode) {
        $application = \Modules\Incubation\Models\Application::where('application_code', $applicationCode)
            ->select(['id', 'application_code', 'startup_name', 'status', 'created_at', 'interview_scheduled_at'])
            ->first();

        if (! $application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        return response()->json([
            'code' => $application->application_code,
            'startup_name' => $application->startup_name,
            'status' => $application->status->getLabel(),
            'status_color' => $application->status->getColor(),
            'applied_at' => $application->created_at->toIso8601String(),
            'interview_scheduled' => $application->interview_scheduled_at?->toIso8601String(),
        ]);
    })->name('applications.status');
});

// Authenticated API routes
Route::middleware(['auth:sanctum'])->prefix('incubation')->name('api.incubation.')->group(function () {
    // Submit application
    Route::post('/cohorts/{cohort}/apply', function (\Modules\Incubation\Models\Cohort $cohort) {
        // Validation and submission handled by ApplicationService
        return response()->json(['message' => 'Use the web form for applications'], 400);
    })->name('cohorts.apply');
});
