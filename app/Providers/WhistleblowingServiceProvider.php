<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Src\Whistleblowing\Domain\Events\MessageCreated;
use Src\Whistleblowing\Domain\Events\ReportCreated;
use Src\Whistleblowing\Infrastructure\Listeners\AuditLogListener;

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

        // Registrar el servicio de auditoría inmutable
        $this->app->singleton(
            \Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar las rutas del módulo Whistleblowing
        $this->registerRoutes();

        // Registrar los event listeners para auditoría
        $this->registerEventListeners();
    }

    /**
     * Registrar los event listeners
     */
    protected function registerEventListeners(): void
    {
        Event::listen(
            ReportCreated::class,
            [AuditLogListener::class, 'handleReportCreated']
        );

        Event::listen(
            MessageCreated::class,
            [AuditLogListener::class, 'handleMessageCreated']
        );
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
