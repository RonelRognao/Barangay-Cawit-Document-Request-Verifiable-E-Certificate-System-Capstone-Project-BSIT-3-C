<?php

namespace App\Http\Requests\Resident;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isResident() ?? false;
    }

    public function rules(): array
    {
        $docType = DocumentType::find($this->input('document_type_id'));
        $requiresBiz = $docType?->requires_business_details ?? false;
        $requiresDoc = $docType?->requires_supporting_doc ?? false;
        $isRa11261 = $docType?->is_ra11261 ?? false;

        return [
            'document_type_id' => [
                'required',
                'integer',
                Rule::exists('document_types', 'id')->where('is_active', 1),
            ],
            'purpose_category' => ['required', 'string', 'max:120'],
            'purpose' => ['required', 'string', 'min:10', 'max:2000'],
            'fee_exemption_claim' => ['nullable', Rule::in(['none', 'senior_citizen', 'pwd'])],

            // Business Clearance specific fields
            'business_name' => [$requiresBiz ? 'required' : 'nullable', 'string', 'max:255'],
            'business_address' => [$requiresBiz ? 'required' : 'nullable', 'string', 'max:500'],
            'nature_of_business' => [$requiresBiz ? 'required' : 'nullable', 'string', 'max:255'],
            'application_type' => [$requiresBiz ? 'required' : 'nullable', Rule::in(['New', 'Renewal'])],
            'dti_sec_no' => ['nullable', 'string', 'max:100'],
            'ctc_no' => ['nullable', 'string', 'max:100'],

            // Supporting document upload
            'attachment' => [$requiresDoc ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],

            // RA 11261 First-Time Jobseeker fields
            'oath_of_undertaking' => [$isRa11261 ? 'accepted' : 'nullable'],
            'school_or_qualification' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'purpose.min' => 'Please enter a specific, descriptive purpose (at least 10 characters; avoid vague entries like "needs").',
            'oath_of_undertaking.accepted' => 'You must confirm the RA 11261 Oath of Undertaking for First-Time Jobseekers.',
            'attachment.required' => 'Supporting document (e.g., DTI/SEC registration or Cedula) is required for this document type.',
        ];
    }
}