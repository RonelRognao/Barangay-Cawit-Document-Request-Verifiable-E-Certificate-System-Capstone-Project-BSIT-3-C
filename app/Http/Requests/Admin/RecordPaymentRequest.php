<?php

namespace App\Http\Requests\Admin;

use App\Services\FeeAndMandateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canVerifyPayments() ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::in(['unpaid', 'paid', 'exempt'])],
            'fee_exemption_type' => ['required', Rule::in(array_keys(FeeAndMandateService::EXEMPTION_LABELS))],
            'official_receipt_no' => ['nullable', 'string', 'max:60', 'required_if:payment_status,paid'],
            'official_receipt_date' => ['nullable', 'date', 'before_or_equal:today', 'required_if:payment_status,paid'],
        ];
    }

    public function messages(): array
    {
        return [
            'official_receipt_no.required_if' => 'Official Receipt (O.R.) Number is required when marking payment as Paid.',
            'official_receipt_date.required_if' => 'Official Receipt (O.R.) Date is required when marking payment as Paid.',
        ];
    }
}