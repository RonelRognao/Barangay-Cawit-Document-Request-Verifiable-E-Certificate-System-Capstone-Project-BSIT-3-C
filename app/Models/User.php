<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Keeps Laravel from failing if your table doesn't have updated_at
    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'role',
        'position_title',
        'contact_number',
        'address',
        'account_status',
        'created_at',
        'purok_id',
        'birth_date',
        'sex',
        'civil_status',
        'residency_since',
        'is_registered_voter',
        'is_senior_citizen',
        'is_pwd',
        'valid_id_type',
        'valid_id_path',
        'proof_of_residency_path',
        'kyc_status',
        'kyc_remarks',
        'kyc_verified_by',
        'kyc_verified_at',
        'ra11261_availed_at',
    ];

    public const STAFF_ROLES = [
        'admin',
        'punong_barangay',
        'secretary',
        'treasurer',
        'clerk',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'birth_date' => 'date',
            'residency_since' => 'date',
            'kyc_verified_at' => 'datetime',
            'is_registered_voter' => 'boolean',
            'is_senior_citizen' => 'boolean',
            'is_pwd' => 'boolean',
            'ra11261_availed_at' => 'datetime',
        ];
    }

    public function purok()
    {
        return $this->belongsTo(Purok::class);
    }

    public function kycVerifier()
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    public function blotterRecords()
    {
        return $this->hasMany(BlotterRecord::class, 'respondent_user_id');
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }

    public function residencyMonths(): ?int
    {
        if (!$this->residency_since) {
            return null;
        }

        return (int) $this->residency_since->diffInMonths(now('Asia/Manila'));
    }

    public function residencyDurationLabel(): string
    {
        $months = $this->residencyMonths();
        if ($months === null) {
            return 'Not specified';
        }
        if ($months < 1) {
            return 'Less than 1 month';
        }
        $years = intdiv($months, 12);
        $remMonths = $months % 12;

        if ($years > 0 && $remMonths > 0) {
            return "{$years} yr(s), {$remMonths} mo(s)";
        }

        return $years > 0 ? "{$years} year(s)" : "{$remMonths} month(s)";
    }

    public function canVerifyPayments(): bool
    {
        return in_array($this->role, ['admin', 'punong_barangay', 'secretary', 'treasurer'], true);
    }

    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function issuedCertificates()
    {
        return $this->hasMany(ECertificate::class, 'issued_by');
    }

    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function isAdmin(): bool
    {
        return $this->isStaff();
    }

    public function canProcessRequests(): bool
    {
        return in_array($this->role, ['admin', 'punong_barangay', 'secretary', 'clerk'], true);
    }

    public function canApproveRequests(): bool
    {
        return in_array($this->role, ['admin', 'punong_barangay', 'secretary'], true);
    }

    public function canIssueCertificates(): bool
    {
        return in_array($this->role, ['admin', 'punong_barangay', 'secretary'], true);
    }

    public function canViewAuditLogs(): bool
    {
        return in_array($this->role, ['admin', 'punong_barangay', 'secretary', 'treasurer'], true);
    }

    public function canManageGovernance(): bool
    {
        return in_array($this->role, ['admin', 'punong_barangay', 'secretary'], true);
    }

    public function isResident(): bool
    {
        return $this->role === 'resident';
    }

    public function roleLabel(): string
    {
        if (!empty($this->position_title)) {
            return $this->position_title;
        }

        return match ($this->role) {
            'admin' => 'System Administrator',
            'punong_barangay' => 'Punong Barangay',
            'secretary' => 'Barangay Secretary',
            'treasurer' => 'Barangay Treasurer',
            'clerk' => 'Barangay Clerk',
            default => 'Resident',
        };
    }

    // Tells Laravel your password column is named 'password_hash'
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }
}