<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhook/*',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Calcular estadísticas diarias a las 23:59
        $schedule->command('stats:calculate-daily')
            ->dailyAt('23:59')
            ->appendOutputTo(storage_path('logs/daily-stats.log'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
