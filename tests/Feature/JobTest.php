<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase; // Pastikan ini ada
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase; // Tambahkan ini di sini

public function test_can_create_job()
{
    // Buat company dulu karena Job butuh company_id
    $company = \App\Models\Company::create(['name' => 'Startup XYZ']);

    $response = $this->postJson('/api/jobs', [
        'title' => 'Programmer',
        'location' => 'Jakarta',
        'description' => 'Bikin aplikasi',
        'company_id' => $company->id // Masukkan ID ini
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('jobs', ['title' => 'Programmer']);
}
    public function test_can_see_company_name_in_job_list()
    {
        // 1. Buat Company
        $company = \App\Models\Company::create(['name' => 'Google']);

        // 2. Buat Job yang terhubung ke Company tersebut
        \App\Models\Job::create([
            'title' => 'Senior Dev',
            'location' => 'Remote',
            'description' => 'Expert only',
            'company_id' => $company->id
        ]);

        // 3. Hit API
        $response = $this->getJson('/api/jobs');

        // 4. Pastikan company_name muncul di response
        $response->assertStatus(200)
                ->assertJsonFragment(['company_name' => 'Google']);
    }
}
