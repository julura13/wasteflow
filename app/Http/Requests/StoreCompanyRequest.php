<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'rebate_percentage' => 'nullable|numeric|min:0|max:100',
            'default_waste_service_provider_id' => 'nullable|exists:service_providers,id',
            'default_recycling_service_provider_id' => 'nullable|exists:service_providers,id',
            'is_active' => 'boolean',
        ];
    }
}
