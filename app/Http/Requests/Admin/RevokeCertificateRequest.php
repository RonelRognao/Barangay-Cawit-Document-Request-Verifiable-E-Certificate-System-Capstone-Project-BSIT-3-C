<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RevokeCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canIssueCertificates() ?? false;
    }

    public function rules(): array
    {
        return [
            'revocation_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}