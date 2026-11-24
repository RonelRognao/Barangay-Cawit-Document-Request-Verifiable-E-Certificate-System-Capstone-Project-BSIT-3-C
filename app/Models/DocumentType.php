<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'document_name',
        'description',
        'fee',
        'is_fee_exempt_default',
        'validity_days',
        'requires_business_details',
        'requires_supporting_doc',
        'is_ra11261',
        'allowed_purposes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'is_fee_exempt_default' => 'boolean',
            'validity_days' => 'integer',
            'requires_business_details' => 'boolean',
            'requires_supporting_doc' => 'boolean',
            'is_ra11261' => 'boolean',
            'allowed_purposes' => 'array',
            'is_active' => 'boolean',
        ];
    }
}