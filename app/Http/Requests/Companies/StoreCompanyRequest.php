<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set true biar bisa diakses
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:companies,name',
        ];
    }
}
