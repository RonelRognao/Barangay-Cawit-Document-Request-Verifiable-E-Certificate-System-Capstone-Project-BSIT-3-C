<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyResidentKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canApproveRequests() ?? false;
    }

    public function rules(): array
    {
        return [
            'kyc_status' => ['required', Rule::in(['verified', 'rejected', 'pending'])],
            'purok_id' => ['nullable', 'integer', 'exists:puroks,id'],
            'residency_since' => ['nullable', 'date', 'before_or_equal:today'],
            'is_registered_voter' => ['nullable', 'boolean'],
            'is_senior_citizen' => ['nullable', 'boolean'],
            'is_pwd' => ['nullable', 'boolean'],
            'kyc_remarks' => ['nullable', 'string', 'max:500', 'required_if:kyc_status,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'kyc_remarks.required_if' => 'Please provide a reason when rejecting a resident KYC verification.',
        ];
    }
}