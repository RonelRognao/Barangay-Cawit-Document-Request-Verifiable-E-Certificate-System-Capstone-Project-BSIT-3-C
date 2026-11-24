<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ECertificate extends Model
{
    protected $table = 'e_certificates';

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'certificate_no',
        'issued_by',
        'issued_at',
        'verification_token',
        'revoked_at',
        'verification_hash',
        'revoked_by',
        'revocation_reason',
        'primary_official_id',
        'signatory_name',
        'signatory_title',
        'attested_by_name',
        'attested_by_title',
        'expires_at',
    ];

    public function primaryOfficial()
    {
        return $this->belongsTo(BarangayOfficial::class, 'primary_official_id');
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime', 
        ];
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function request()
    {
        return $this->belongsTo(DocumentRequest::class, 'request_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}