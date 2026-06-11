<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-recurring-orders');
    }

    public function rules(): array
    {
        $orderType = $this->input('order_type');

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'service_provider_id' => ['required', 'integer', 'exists:service_providers,id'],
            'order_type' => ['required', Rule::in(['waste', 'recycling'])],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'quantity_lines' => ['required', 'array', 'min:1'],
            'quantity_lines.*.container_option_id' => [
                'required',
                'integer',
                Rule::exists('container_options', 'id')->where(fn ($q) => $q->where('order_type', $orderType)->where('is_active', true)),
            ],
            'quantity_lines.*.quantity' => ['required', 'integer', 'min:1'],
            'quantity_lines.*.description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'days_of_week.required' => 'Please select at least one day of the week.',
            'days_of_week.min' => 'Please select at least one day of the week.',
            'quantity_lines.required' => 'Please add at least one container quantity line.',
        ];
    }
}
