<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePurokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageGovernance() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'unique:puroks,name'],
            'sitio_leader' => ['nullable', 'string', 'max:255'],
        ];
    }
}