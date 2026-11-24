<?php

namespace App\Http\Controllers;

use App\Models\ECertificate;
use App\Services\CertificateVerificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function __construct(
        private readonly CertificateVerificationService $verificationService
    ) {
    }

    public function index(Request $request, ?string $code = null)
    {
        $token = trim((string) $request->query('token', ''));
        $qrSig = trim((string) $request->query('sig', ''));
        $searchCode = trim((string) ($request->query('code', '') ?: ($code ?? '')));

        $certificate = null;
        $revokedCertificate = null;
        $expiredCertificate = null;
        $integrityVerified = false;
        $error = '';
        $searched = ($token !== '' || $searchCode !== '');

        if ($token !== '') {
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                $error = 'Invalid verification token format.';
            } else {
                $certificate = ECertificate::with(['request.user', 'request.documentType'])
                    ->where('verification_token', strtolower($token))
                    ->first();

                if (!$certificate) {
                    $error = 'Certificate not found. The verification token is invalid.';
                }
            }
        } elseif ($searchCode !== '') {
            $certificate = ECertificate::with(['request.user', 'request.documentType'])
                ->where(function ($q) use ($searchCode) {
                    $q->where('certificate_no', strtoupper($searchCode))
                        ->orWhere('verification_token', strtolower($searchCode));
                })
                ->first();

            if (!$certificate) {
                $error = 'Certificate not found. Please check the Certificate Number or token and try again.';
            }
        }

        if ($certificate) {
            $integrityVerified = $this->verificationService->verifyIntegrity(
                $certificate,
                $qrSig !== '' ? $qrSig : null
            );

            if (!$integrityVerified) {
                $error = 'Integrity check failed. This certificate record or QR signature could not be cryptographically verified.';
                $certificate = null;
            } elseif ($certificate->revoked_at !== null) {
                $revokedDate = Carbon::parse($certificate->revoked_at)
                    ->timezone('Asia/Manila')
                    ->format('F d, Y h:i A');
                $error = "This certificate was revoked on {$revokedDate} and is no longer valid.";
                $revokedCertificate = $certificate;
                $certificate = null;
            } elseif ($certificate->isExpired()) {
                $expiredDate = Carbon::parse($certificate->expires_at)
                    ->timezone('Asia/Manila')
                    ->format('F d, Y');
                $error = "This certificate expired on {$expiredDate} and is no longer valid.";
                $expiredCertificate = $certificate;
                $certificate = null;
            }
        }

        $activeCert = $certificate ?? $revokedCertificate ?? $expiredCertificate;
        /** @var \App\Models\User|null $viewer */
        $viewer = Auth::user();
        $showFullDetails = false;

        if ($viewer && $activeCert) {
            $showFullDetails = $viewer->isStaff()
                || ($activeCert->request && $activeCert->request->user_id === $viewer->id);
        }

        $displayDetails = null;
        if ($activeCert && $activeCert->request) {
            $holderName = $activeCert->request->user->full_name;
            $refNo = $activeCert->request->reference_no;

            $displayDetails = [
                'certificate_no' => $activeCert->certificate_no,
                'holder_name' => $showFullDetails
                    ? $holderName
                    : $this->verificationService->maskFullName($holderName),
                'document_name' => $activeCert->request->documentType->document_name,
                'reference_no' => $showFullDetails
                    ? $refNo
                    : $this->verificationService->maskReferenceNo($refNo),
                'issued_at' => Carbon::parse($activeCert->issued_at)
                    ->timezone('Asia/Manila')
                    ->format('F d, Y h:i A'),
                'expires_at' => $activeCert->expires_at
                    ? Carbon::parse($activeCert->expires_at)->timezone('Asia/Manila')->format('F d, Y')
                    : 'No Expiry',
                'revoked_at' => $activeCert->revoked_at
                    ? Carbon::parse($activeCert->revoked_at)->timezone('Asia/Manila')->format('F d, Y h:i A')
                    : null,
                'revocation_reason' => $showFullDetails ? $activeCert->revocation_reason : null,
                'hash_fingerprint' => strtoupper(substr((string) $activeCert->verification_hash, 0, 12)),
            ];
        }

        return view('verification.index', [
            'certificate' => $certificate,
            'revokedCertificate' => $revokedCertificate,
            'expiredCertificate' => $expiredCertificate,
            'displayDetails' => $displayDetails,
            'integrityVerified' => $integrityVerified,
            'showFullDetails' => $showFullDetails,
            'error' => $error,
            'code' => $searchCode,
            'searched' => $searched,
        ]);
    }
}