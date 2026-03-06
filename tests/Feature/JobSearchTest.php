<?php

use App\Models\Job;
use App\Models\Company;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

it('can search jobs by title', function () {
    Sanctum::actingAs(User::factory()->create());

    $company = Company::create(['name' => 'Wonsulting']);
    Job::create([
        'title' => 'Laravel Developer',
        'location' => 'Remote',
        'description' => 'Expert level',
        'company_id' => $company->id
    ]);

    $response = getJson('/api/jobs?search=Laravel');

    $response->assertStatus(200)
             ->assertJsonFragment(['title' => 'Laravel Developer']);
});

it('has rate limiting protection', function () {
    Sanctum::actingAs(User::factory()->create());

    for ($i = 0; $i < 61; $i++) {
        $response = getJson('/api/jobs');
    }

    $response->assertStatus(429);
});
