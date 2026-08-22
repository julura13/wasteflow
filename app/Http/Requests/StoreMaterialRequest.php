<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
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
        $baseRules = [
            'waste_stream_id' => 'required|exists:waste_streams,id',
            'grade_id' => 'required|exists:grades,id',
            'classification_id' => 'required|exists:classifications,id',
            'facility_id' => 'required|exists:facilities,id',
            'service_provider_id' => 'nullable|exists:service_providers,id',
            'weight_required' => 'required|string|max:255',
            'rebate_offered' => 'sometimes|boolean',
            'backing_document' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ];

        $rebateRules = $this->boolean('rebate_offered')
            ? [
                'rebate_rate' => 'required|numeric|min:0|max:999999.99',
                'client_rebate_share' => 'required|numeric|min:0|max:100',
            ]
            : [
                'rebate_rate' => 'nullable|numeric|min:0|max:999999.99',
                'client_rebate_share' => 'nullable|numeric|min:0|max:100',
            ];

        return array_merge($baseRules, $rebateRules);
    }
}
