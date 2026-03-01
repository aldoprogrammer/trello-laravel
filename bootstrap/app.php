<?php

use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception) {
            return ApiResponse::error('Validation error', 422, $exception->errors());
        });

        $exceptions->render(function (AuthenticationException $exception) {
            return ApiResponse::error($exception->getMessage() ?: 'Unauthorized', 401);
        });

        $exceptions->render(function (AuthorizationException $exception) {
            return ApiResponse::error($exception->getMessage() ?: 'Forbidden', 403);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception) {
            return ApiResponse::error('Resource not found', 404);
        });
    })->create();
