<?php

namespace App\Http\Requests\Admin;

use App\Models\BlotterRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlotterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canProcessRequests() ?? false;
    }

    public function rules(): array
    {
        return [
            'respondent_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'respondent_name' => ['required_without:respondent_user_id', 'nullable', 'string', 'max:255'],
            'complainant_name' => ['required', 'string', 'max:255'],
            'incident_type' => ['required', 'string', 'max:120'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'purok_id' => ['nullable', 'integer', 'exists:puroks,id'],
            'summary' => ['required', 'string', 'max:3000'],
            'status' => ['required', Rule::in(BlotterRecord::STATUSES)],
        ];
    }
}