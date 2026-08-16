<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\StripsEmptyFileField;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSheqComplianceDocumentRequest extends FormRequest
{
    use StripsEmptyFileField;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['sometimes', 'file', 'max:10240'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ];
    }
}
