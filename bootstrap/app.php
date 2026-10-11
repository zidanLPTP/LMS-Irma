<?php

use App\Http\Middleware\CatatAktivitas;
use App\Http\Middleware\PeranMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'peran' => PeranMiddleware::class,
            'catat.aktivitas' => CatatAktivitas::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('masuk'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $simpanTujuanKuis = function (Request $request): void {
            $referer = $request->header('referer');
            $tujuan = route('siswa.beranda');

            if ($referer) {
                $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: $request->getHost();
                $refHost = parse_url($referer, PHP_URL_HOST);
                $refPath = parse_url($referer, PHP_URL_PATH) ?: '';

                // Host harus sama dengan host aplikasi dan jalur berupa rute kuis siswa
                if ($refHost === $appHost && (str_contains($refPath, '/kuis') || str_contains($refPath, '/percobaan'))) {
                    $tujuan = $referer;
                }
            }

            session()->put('url.intended', $tujuan);
        };

        $exceptions->render(function (HttpException $e, Request $request) use ($simpanTujuanKuis) {
            if ($e->getStatusCode() === 419) {
                $simpanTujuanKuis($request);

                return response()->view('errors.419', [], 419);
            }
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($simpanTujuanKuis) {
            $simpanTujuanKuis($request);

            return response()->view('errors.419', [], 419);
        });
    })->create();
