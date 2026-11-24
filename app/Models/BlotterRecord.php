<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlotterRecord extends Model
{
    public const STATUSES = [
        'Pending Mediation',
        'Under Conciliation',
        'CFA Issued',
        'Amicably Settled',
        'Dismissed',
    ];

    public const ACTIVE_DEROGATORY_STATUSES = [
        'Pending Mediation',
        'Under Conciliation',
        'CFA Issued',
    ];

    public const INCIDENT_TYPES = [
        'Physical Injury / Altercation',
        'Threats / Harassment / Slander',
        'Property / Boundary Dispute',
        'Unpaid Debt / Estafa',
        'Disturbance of Peace / Noise',
        'Family / Domestic Matter',
        'Other Barangay Dispute',
    ];

    protected $fillable = [
        'case_no',
        'respondent_user_id',
        'respondent_name',
        'complainant_name',
        'incident_type',
        'incident_date',
        'purok_id',
        'summary',
        'status',
        'resolution_notes',
        'recorded_by',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    public function respondent()
    {
        return $this->belongsTo(User::class, 'respondent_user_id');
    }

    public function purok()
    {
        return $this->belongsTo(Purok::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isDerogatory(): bool
    {
        return in_array($this->status, self::ACTIVE_DEROGATORY_STATUSES, true);
    }
}