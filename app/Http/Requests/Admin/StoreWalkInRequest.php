<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentType;
use App\Services\FeeAndMandateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canProcessRequests() ?? false;
    }

    public function rules(): array
    {
        $docType = DocumentType::find($this->input('document_type_id'));
        $requiresBiz = $docType?->requires_business_details ?? false;
        $isNewResident = $this->input('resident_mode') === 'new_walkin';

        return [
            'resident_mode' => ['required', Rule::in(['existing', 'new_walkin'])],
            'user_id' => [$isNewResident ? 'nullable' : 'required', 'integer', 'exists:users,id'],

            // Quick walk-in resident fields (for residents without smartphones)
            'walkin_full_name' => [$isNewResident ? 'required' : 'nullable', 'string', 'max:255'],
            'walkin_purok_id' => [$isNewResident ? 'required' : 'nullable', 'integer', 'exists:puroks,id'],
            'walkin_contact_number' => ['nullable', 'string', 'max:50'],
            'walkin_address' => [$isNewResident ? 'required' : 'nullable', 'string', 'max:500'],
            'walkin_residency_since' => ['nullable', 'date', 'before_or_equal:today'],

            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')->where('is_active', 1)],
            'purpose_category' => ['required', 'string', 'max:120'],
            'purpose' => ['required', 'string', 'min:10', 'max:2000'],
            'initial_status' => ['required', Rule::in(['Pending', 'Under Review', 'Approved'])],

            // Business Clearance fields
            'business_name' => [$requiresBiz ? 'required' : 'nullable', 'string', 'max:255'],
            'business_address' => [$requiresBiz ? 'required' : 'nullable', 'string', 'max:500'],
            'nature_of_business' => [$requiresBiz ? 'required' : 'nullable', 'string', 'max:255'],
            'application_type' => [$requiresBiz ? 'required' : 'nullable', Rule::in(['New', 'Renewal'])],
            'dti_sec_no' => ['nullable', 'string', 'max:100'],
            'ctc_no' => ['nullable', 'string', 'max:100'],

            // Optional immediate counter payment / O.R. recording
            'payment_status' => ['required', Rule::in(['unpaid', 'paid', 'exempt'])],
            'fee_exemption_type' => ['required', Rule::in(array_keys(FeeAndMandateService::EXEMPTION_LABELS))],
            'official_receipt_no' => ['nullable', 'string', 'max:60', 'required_if:payment_status,paid'],
            'official_receipt_date' => ['nullable', 'date', 'before_or_equal:today', 'required_if:payment_status,paid'],
        ];
    }
}