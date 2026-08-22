<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceProviderRequest extends FormRequest
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
            'types' => 'required|array|min:1',
            'types.*' => 'in:waste_collection,recycling,hazardous,general',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'slip_number_prefix' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
    }
}
