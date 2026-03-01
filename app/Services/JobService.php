<?php

namespace App\Services;
use App\Models\Job;

class JobService {
    public function createJob(array $data) {
        return Job::create($data);
    }
}
