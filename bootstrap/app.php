<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'account.active' => \App\Http\Middleware\CheckAccountActive::class,
        ]);

        // منع محاولة إعادة التوجيه إلى Route باسم login في طلبات الـ API
        // نمرّر دالة ترجع null بدلاً من مسار، فيتصرّف Laravel بإرجاع 401 JSON بدون Redirect.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
