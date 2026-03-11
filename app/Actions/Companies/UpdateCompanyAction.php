<?php
namespace App\Actions\Companies;
use App\Models\Company;

class UpdateCompanyAction {
    public function execute(Company $company, array $data): Company {
        $company->update($data);
        return $company;
    }
}
