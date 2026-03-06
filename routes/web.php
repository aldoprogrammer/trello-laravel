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

Route::get('/docs/swagger.yaml', function () {
    $path = base_path('docs/swagger.yaml');

    abort_unless(file_exists($path), 404);

    return Response::file($path, [
        'Content-Type' => 'application/yaml',
    ]);
});
