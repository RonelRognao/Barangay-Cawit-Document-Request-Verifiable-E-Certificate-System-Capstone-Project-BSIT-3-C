<?php

namespace App\Http\Requests\Admin;

use App\Models\BarangayOfficial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageGovernance() ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['required', Rule::in(BarangayOfficial::POSITIONS)],
            'designation_title' => ['nullable', 'string', 'max:120'],
            'committee' => ['nullable', 'string', 'max:120'],
            'term_start' => ['nullable', 'date'],
            'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_primary_signatory' => ['nullable', 'boolean'],
            'is_attest_signatory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}