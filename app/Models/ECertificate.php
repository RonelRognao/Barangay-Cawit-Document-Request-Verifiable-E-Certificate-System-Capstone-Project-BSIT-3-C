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
    ];

    public function request()
    {
        return $this->belongsTo(DocumentRequest::class, 'request_id');
    }
}