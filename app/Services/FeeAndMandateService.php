<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FeeAndMandateService
{
    public const EXEMPTION_LABELS = [
        'none' => 'Standard Fee (Paid)',
        'indigency' => 'Indigency Exemption (Free)',
        'ra_11261' => 'RA 11261 First-Time Jobseeker (Free)',
        'senior_citizen' => 'Senior Citizen Fee Waiver (RA 9994)',
        'pwd' => 'PWD Fee Waiver (RA 10754)',
    ];

    /**
     * @return array{eligible: bool, reason: ?string}
     */
    public function checkRa11261Eligibility(User $resident): array
    {
        if ($resident->ra11261_availed_at !== null) {
            return [
                'eligible' => false,
                'reason' => 'RA 11261 First-Time Jobseeker fee waiver can only be availed once per resident (already availed on ' . $resident->ra11261_availed_at->format('M d, Y') . ').',
            ];
        }

        $months = $resident->residencyMonths();
        if ($months === null || $months < 6) {
            return [
                'eligible' => false,
                'reason' => 'RA 11261 requires at least 6 months of verified residency in Barangay Cawit. Please verify your residency start date under My Profile.',
            ];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * @return array{assessed_fee: float, exemption_type: string, final_fee: float, payment_status: string}
     */
    public function calculateInitialFee(User $resident, DocumentType $docType, string $requestedExemption = 'none'): array
    {
        $assessed = (float) $docType->fee;

        if ($docType->is_ra11261) {
            return [
                'assessed_fee' => $assessed,
                'exemption_type' => 'ra_11261',
                'final_fee' => 0.00,
                'payment_status' => 'exempt',
            ];
        }

        if ($docType->is_fee_exempt_default || $assessed <= 0) {
            return [
                'assessed_fee' => $assessed,
                'exemption_type' => 'indigency',
                'final_fee' => 0.00,
                'payment_status' => 'exempt',
            ];
        }

        if ($requestedExemption === 'senior_citizen' && $resident->is_senior_citizen) {
            return [
                'assessed_fee' => $assessed,
                'exemption_type' => 'senior_citizen',
                'final_fee' => 0.00,
                'payment_status' => 'exempt',
            ];
        }

        if ($requestedExemption === 'pwd' && $resident->is_pwd) {
            return [
                'assessed_fee' => $assessed,
                'exemption_type' => 'pwd',
                'final_fee' => 0.00,
                'payment_status' => 'exempt',
            ];
        }

        return [
            'assessed_fee' => $assessed,
            'exemption_type' => 'none',
            'final_fee' => $assessed,
            'payment_status' => 'unpaid',
        ];
    }

    public function storeAttachment(UploadedFile $file): string
    {
        return $file->store('request_attachments', 'local');
    }

    public function getAttachmentPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->exists($path) ? $disk->path($path) : null;
    }
}