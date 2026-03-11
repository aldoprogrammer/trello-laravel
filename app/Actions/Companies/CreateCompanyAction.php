<?php
namespace App\Actions\Companies;

use App\Models\Company;
use App\Jobs\SendCompanyWelcomeEmail;
use Illuminate\Support\Facades\Cache;

class CreateCompanyAction {
    public function execute(array $data): Company {
        $company = Company::create($data);
        Cache::flush(); // Clear cache agar data baru muncul
        SendCompanyWelcomeEmail::dispatch($company);
        return $company;
    }
}
