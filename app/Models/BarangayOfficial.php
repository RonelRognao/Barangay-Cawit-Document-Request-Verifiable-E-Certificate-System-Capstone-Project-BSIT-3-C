<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangayOfficial extends Model
{
    public const POSITIONS = [
        'Punong Barangay',
        'OIC - Punong Barangay',
        'Barangay Secretary',
        'Barangay Treasurer',
        'Barangay Kagawad',
    ];

    protected $fillable = [
        'user_id',
        'full_name',
        'position',
        'designation_title',
        'committee',
        'term_start',
        'term_end',
        'signature_path',
        'is_primary_signatory',
        'is_attest_signatory',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
            'is_primary_signatory' => 'boolean',
            'is_attest_signatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function displayTitle(): string
    {
        return $this->designation_title ?: $this->position;
    }
}