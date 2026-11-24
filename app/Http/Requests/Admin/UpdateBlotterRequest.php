<?php

namespace App\Http\Requests\Admin;

use App\Models\BlotterRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlotterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canApproveRequests() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(BlotterRecord::STATUSES)],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}