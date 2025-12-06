<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class WhistleblowingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar el binding del repositorio
        $this->app->bind(
            \Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface::class,
            \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository::class
        );

        // Registrar el modelo de Eloquent como singleton
        $this->app->singleton(
            \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel::class,
            function () {
                return new \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel();
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar las rutas del módulo Whistleblowing
        $this->registerRoutes();
    }

    /**
     * Registrar las rutas del módulo
     */
    protected function registerRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(base_path('src/Whistleblowing/Presentation/Routes/api.php'));
    }
}
