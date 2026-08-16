<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Inertia's forceFormData always sends a `file` field, even when no replacement file was
     * chosen - a null value is serialized as an empty string rather than omitted entirely.
     * Without this, the `file` rule sees a present-but-empty value and fails validation on
     * every edit that doesn't also replace the file.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('file'), [null, ''], true)) {
            $this->request->remove('file');
        }
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
        ];
    }
}
