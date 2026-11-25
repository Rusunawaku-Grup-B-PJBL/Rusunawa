<?php

use App\Http\Middleware\ApiResponseEnvelope;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserIsAdmin::class,
        ]);
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/login'
        );
        $middleware->api(append: [ApiResponseEnvelope::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $error = fn (string $message, int $code, $errors = null) => response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'errors' => $errors ?? (object) [],
        ], $code);

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($error) {
            if ($request->is('api/*')) {
                return $error('Sesi tidak valid. Silakan masuk kembali.', 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($error) {
            if ($request->is('api/*')) {
                return $error($e->validator->errors()->first(), 422, $e->errors());
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($error) {
            if ($request->is('api/*')) {
                return $error('Data tidak ditemukan.', 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($error) {
            if ($request->is('api/*')) {
                return $error('Terlalu banyak percobaan. Coba lagi sebentar lagi.', 429);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) use ($error) {
            if ($request->is('api/*') && $e->getStatusCode() === 403) {
                return $error('Anda tidak memiliki izin untuk melakukan tindakan ini.', 403);
            }
        });
    })->create();