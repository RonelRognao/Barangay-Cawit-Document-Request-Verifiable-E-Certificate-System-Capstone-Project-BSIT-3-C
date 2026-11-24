<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageGovernance() ?? false;
    }

    public function rules(): array
    {
        return [
            'document_name' => ['required', 'string', 'max:255', 'unique:document_types,document_name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:document_types,code'],
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