<?php

namespace App\Services;

use App\Models\ECertificate;
use Carbon\Carbon;

class CertificateVerificationService
{
    public function computeHash(ECertificate $certificate): string
    {
        $issuedAt = $certificate->issued_at instanceof Carbon
            ? $certificate->issued_at->copy()->timezone('Asia/Manila')
            : Carbon::parse($certificate->issued_at, 'Asia/Manila');

        $payload = implode('|', [
            $certificate->certificate_no,
            $certificate->request_id,
            $certificate->verification_token,
            $issuedAt->format('Y-m-d H:i:s'),
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function computeQrSignature(ECertificate $certificate): string
    {
        return substr(
            hash_hmac('sha256', $certificate->certificate_no . '|' . $certificate->verification_token, (string) config('app.key')),
            0,
            16
        );
    }

    public function buildQrVerifyUrl(ECertificate $certificate): string
    {
        return route('verify', [
            'token' => $certificate->verification_token,
            'sig' => $this->computeQrSignature($certificate),
        ]);
    }

    public function verifyIntegrity(ECertificate $certificate, ?string $qrSignature = null): bool
    {
        if (empty($certificate->verification_hash)) {
            return false;
        }

        if (!hash_equals($this->computeHash($certificate), $certificate->verification_hash)) {
            return false;
        }

        if ($qrSignature !== null && $qrSignature !== '') {
            if (!hash_equals($this->computeQrSignature($certificate), strtolower($qrSignature))) {
                return false;
            }
        }

        return true;
    }

    public function maskFullName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        $masked = array_map(function (string $part): string {
            $len = mb_strlen($part);
            if ($len <= 1) {
                return '*';
            }
            if ($len === 2) {
                return mb_substr($part, 0, 1) . '*';
            }

            $visible = $len >= 5 ? 2 : 1;

            return mb_substr($part, 0, $visible) . str_repeat('*', max(2, $len - $visible));
        }, $parts);

        return implode(' ', $masked);
    }

    public function maskReferenceNo(string $referenceNo): string
    {
        $segments = explode('-', $referenceNo);
        if (count($segments) === 3 && strlen($segments[2]) >= 4) {
            $segments[2] = str_repeat('*', strlen($segments[2]) - 4) . substr($segments[2], -4);

            return implode('-', $segments);
        }

        $len = strlen($referenceNo);

        return $len <= 4
            ? str_repeat('*', $len)
            : substr($referenceNo, 0, 4) . str_repeat('*', $len - 8) . substr($referenceNo, -4);
    }
}