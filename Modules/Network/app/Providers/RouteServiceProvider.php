<?php

declare(strict_types=1);

namespace Modules\Network\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Network';

    /**
     * Called before routes are registered.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     */
    protected function mapWebRoutes(): void
    {
        $webRoutes = module_path($this->name, 'routes/web.php');

        if (file_exists($webRoutes)) {
            Route::middleware('web')
                ->group($webRoutes);
        }
    }

    /**
     * Define the "api" routes for the application.
     */
    protected function mapApiRoutes(): void
    {
        $apiRoutes = module_path($this->name, 'routes/api.php');

        if (file_exists($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group($apiRoutes);
        }
    }
}
