<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageGovernance() ?? false;
    }

    public function rules(): array
    {
        $target = $this->route('staff');
        $allowedRoles = ['punong_barangay', 'secretary', 'treasurer', 'clerk'];
        if ($this->user()?->role === 'admin') {
            $allowedRoles[] = 'admin';
        }

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)],
            'role' => ['required', Rule::in($allowedRoles)],
            'position_title' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'account_status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}