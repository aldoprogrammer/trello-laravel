<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('landing');
});

Route::get('/docs', function () {
    return redirect()->away(rtrim(config('app.url'), '/').'/api/docs');
});

Route::get('/api/documentation', function () {
    return redirect()->away(rtrim(config('app.url'), '/').'/api/docs');
});

Route::get('/api/docs', function () {
    return view('swagger');
});

Route::get('/api/openapi', function () {
    $path = base_path('docs/openapi.bundle.yaml');

    abort_unless(file_exists($path), 404);

    return Response::file($path, [
        'Content-Type' => 'application/yaml',
    ]);
});

Route::get('/api/openapi/files/{file}', function (string $file) {
    abort_unless(str_ends_with($file, '.yaml'), 404);
    abort_unless(! str_contains($file, '..'), 404);
    abort_unless(
        str_starts_with($file, 'paths/')
        || str_starts_with($file, 'schemas/')
        || $file === 'components.yaml',
        404
    );

    $path = base_path("docs/{$file}");
    abort_unless(file_exists($path), 404);

    return Response::file($path, [
        'Content-Type' => 'application/yaml',
    ]);
})->where('file', '.*');
