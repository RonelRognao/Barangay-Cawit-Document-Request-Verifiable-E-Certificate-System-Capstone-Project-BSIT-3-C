<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canProcessRequests() ?? false;
    }

    public function rules(): array
    {
        $allowed = $this->user()?->canApproveRequests()
            ? ['Pending', 'Under Review', 'Approved', 'Rejected', 'Completed']
            : ['Pending', 'Under Review'];

        return [
            'status' => ['required', Rule::in($allowed)],
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
            'derogatory_override' => ['nullable', 'boolean'],
        ];
    }
}