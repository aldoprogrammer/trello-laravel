<?php
namespace App\Actions\Companies;
use App\Models\Company;

class DeleteCompanyAction {
    public function execute(Company $company): bool {
        return $company->delete();
    }
}
