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
        'purpose_category',
        'document_metadata',
        'attachment_path',
        'attachment_label',
        'assessed_fee',
        'fee_exemption_type',
        'final_fee',
        'payment_status',
        'official_receipt_no',
        'official_receipt_date',
        'payment_verified_by',
        'payment_verified_at',
        'status',
        'admin_remarks',
        'processed_by',
        'requested_at',
        'updated_at',
        'is_walk_in', 
        'encoded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_metadata' => 'array',
            'assessed_fee' => 'decimal:2',
            'final_fee' => 'decimal:2',
            'official_receipt_date' => 'date',
            'payment_verified_at' => 'datetime',
            'requested_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_walk_in' => 'boolean',
        ];
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

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

    public function paymentVerifier()
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function isPaidOrExempt(): bool
    {
        return in_array($this->payment_status, ['paid', 'exempt'], true) || (float) $this->final_fee <= 0;
    }
}