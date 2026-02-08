<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCompanyUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'role' => ['required', 'in:viewer,manager'],
        ];
    }
}
