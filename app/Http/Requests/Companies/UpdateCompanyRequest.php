<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil ID company dari route (misal: /api/companies/{company})
        $companyId = $this->route('company')->id ?? $this->route('company');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Logika: Unik di tabel companies, tapi abaikan ID yang sedang diupdate
                Rule::unique('companies', 'name')->ignore($companyId),
            ],
        ];
    }
}
