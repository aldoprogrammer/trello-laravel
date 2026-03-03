<?php

use function Pest\Laravel\get;

it('has security headers', function () {
    $response = get('/api/jobs');

    $response->assertHeader('X-Frame-Options', 'DENY')
             ->assertHeader('X-Content-Type-Options', 'nosniff');
});
