<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reference_no',
        'user_id',
        'document_type_id',
        'purpose',
        'status',
        'admin_remarks',
        'processed_by',
        'requested_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function certificate()
    {
        return $this->hasOne(ECertificate::class, 'request_id');
    }
}