<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            // Middleware spatie v6 (namespace singular, bukan "Middlewares")
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Di belakang reverse proxy (Vercel, Nginx, Cloudflare) Laravel harus
        // dipercaya agar X-Forwarded-Proto terbaca — tanpa ini $request->secure()
        // selalu false walau visitors datang via HTTPS, sehingga cookie sesi
        // maupun redirect kehilangan skema yang benar.
        //
        // Sengaja DIKUNCI lewat env: mempercayai semua proxy secara buta
        // berbahaya, karena siapa pun bisa memalsukan X-Forwarded-For.
        if ($trusted = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(
                at: $trusted === '*' ? '*' : array_map('trim', explode(',', $trusted)),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Permission middleware yang gagal → 403 konsisten (PDF 12: authorization).
        // Tanpa ini Spatie melempar UnauthorizedException yang bisa berakhir 500.
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, Request $request) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman/aksi ini.');
        });
    })->create();