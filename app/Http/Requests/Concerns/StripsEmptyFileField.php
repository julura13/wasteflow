<?php

namespace App\Http\Requests\Concerns;

trait StripsEmptyFileField
{
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
}
