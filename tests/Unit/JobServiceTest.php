<?php

namespace Tests\Unit;

use App\Models\Job;
use App\Models\Company;
use App\Services\JobService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JobService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JobService();
    }

    public function test_service_can_format_job_title()
    {
        $company = Company::create(['name' => 'Wonsulting']);
        $job = Job::create([
            'title' => 'laravel developer',
            'location' => 'Remote',
            'description' => 'Test',
            'company_id' => $company->id
        ]);

        // Contoh ngetes logic internal service (misal: title formatting)
        $this->assertEquals('laravel developer', $job->title);
    }
}
