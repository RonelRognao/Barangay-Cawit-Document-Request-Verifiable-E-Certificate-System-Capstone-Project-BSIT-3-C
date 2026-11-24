<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageGovernance() ?? false;
    }

    public function rules(): array
    {
        $docType = $this->route('documentType');

        return [
            'document_name' => ['required', 'string', 'max:255', Rule::unique('document_types', 'document_name')->ignore($docType?->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('document_types', 'code')->ignore($docType?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'fee' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_fee_exempt_default' => ['nullable', 'boolean'],
            'requires_business_details' => ['nullable', 'boolean'],
            'requires_supporting_doc' => ['nullable', 'boolean'],
            'is_ra11261' => ['nullable', 'boolean'],
            'allowed_purposes_text' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}