<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\ECertificate;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\CertificateVerificationService;
use Illuminate\Support\Facades\Gate;
use App\Services\OfficialSignatoryService;
use App\Models\Purok;
use App\Services\KycStorageService;

class ResidentController extends Controller
{
    public function __construct(
        private readonly CertificateVerificationService $verificationService,
        private readonly OfficialSignatoryService $signatoryService,
        private readonly KycStorageService $kycStorage
    ) {
    }

    public function dashboard()
    {
        $user = Auth::user();

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return view('resident.dashboard', [
            'full_name' => $user->full_name,
            'unread_count' => $unreadCount,
        ]);
    }

    public function myRequests()
    {
        $requests = DocumentRequest::with('documentType')
            ->where('user_id', Auth::id())
            ->orderByDesc('requested_at')
            ->get();

        return view('resident.my_requests', compact('requests'));
    }

    public function myCertificates()
    {
        $certificates = ECertificate::with(['request.documentType'])
            ->whereNull('revoked_at')
            ->whereHas('request', fn ($q) => $q->where('user_id', Auth::id()))
            ->orderByDesc('issued_at')
            ->get();

        return view('resident.my_certificates', compact('certificates'));
    }

    public function printCertificate(string $certificateNo)
    {
        $certificate = ECertificate::with(['request.documentType', 'request.user', 'primaryOfficial'])
            ->where('certificate_no', $certificateNo)
            ->whereNull('revoked_at')
            ->firstOrFail();

        Gate::authorize('view', $certificate);

        $verifyUrl = $this->verificationService->buildQrVerifyUrl($certificate);
        $hashFingerprint = strtoupper(substr((string) $certificate->verification_hash, 0, 12));

        $primaryOfficial = $certificate->primaryOfficial ?: $this->signatoryService->getActivePrimarySignatory();
        $signatoryName = $certificate->signatory_name ?: ($primaryOfficial?->full_name ?? 'ERNISTO DAEN');
        $signatoryTitle = $certificate->signatory_title ?: ($primaryOfficial?->displayTitle() ?? 'Punong Barangay / Barangay Captain');
        $primarySignatureUri = $this->signatoryService->signatureDataUri($primaryOfficial);

        $attestingOfficial = $this->signatoryService->getActiveAttestingSignatory();
        $attestedByName = $certificate->attested_by_name;
        $attestedByTitle = $certificate->attested_by_title;
        $attestSignatureUri = ($attestedByName && $attestingOfficial && $attestingOfficial->full_name === $attestedByName)
            ? $this->signatoryService->signatureDataUri($attestingOfficial)
            : null;

        return view('resident.print_certificate', compact(
            'certificate',
            'verifyUrl',
            'hashFingerprint',
            'signatoryName',
            'signatoryTitle',
            'primarySignatureUri',
            'attestedByName',
            'attestedByTitle',
            'attestSignatureUri'
        ));
    }

    public function notifications()
    {
        $userId = Auth::id();

        $notifications = Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return view('resident.notifications', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markNotificationRead(Notification $notification)
    {
        // Make sure a resident can only mark THEIR OWN notification as read
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllNotificationsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function profile()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('purok');
        $puroks = Purok::where('is_active', true)->orderBy('name')->get();

        return view('resident.profile', compact('user', 'puroks'));
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'purok_id' => ['nullable', 'integer', Rule::exists('puroks', 'id')->where('is_active', true)],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', Rule::in(['Male', 'Female'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'residency_since' => ['nullable', 'date', 'before_or_equal:today'],
            'valid_id_type' => ['nullable', 'string', 'max:80'],
            'valid_id' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'proof_of_residency' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $validIdPath = $user->valid_id_path;
        $proofPath = $user->proof_of_residency_path;
        $uploadedNewId = false;

        if ($request->hasFile('valid_id')) {
            $validIdPath = $this->kycStorage->storeValidId($request->file('valid_id'), $validIdPath);
            $uploadedNewId = true;
        }

        if ($request->hasFile('proof_of_residency')) {
            $proofPath = $this->kycStorage->storeProofOfResidency($request->file('proof_of_residency'), $proofPath);
            $uploadedNewId = true;
        }

        $kycStatus = $user->kyc_status;
        $kycRemarks = $user->kyc_remarks;
        $kycVerifiedBy = $user->kyc_verified_by;
        $kycVerifiedAt = $user->kyc_verified_at;

        if ($user->kyc_status === 'rejected' || $uploadedNewId) {
            $kycStatus = 'pending';
            $kycRemarks = null;
            $kycVerifiedBy = null;
            $kycVerifiedAt = null;
        }

        $user->update([
            'full_name' => trim($validated['full_name']),
            'email' => strtolower(trim($validated['email'])),
            'contact_number' => $validated['contact_number'] ?? null,
            'purok_id' => $validated['purok_id'] ?? $user->purok_id,
            'address' => $validated['address'] ?? null,
            'birth_date' => $validated['birth_date'] ?? $user->birth_date,
            'sex' => $validated['sex'] ?? $user->sex,
            'civil_status' => $validated['civil_status'] ?? $user->civil_status,
            'residency_since' => $validated['residency_since'] ?? $user->residency_since,
            'is_registered_voter' => $request->boolean('is_registered_voter'),
            'is_senior_citizen' => $request->boolean('is_senior_citizen'),
            'is_pwd' => $request->boolean('is_pwd'),
            'valid_id_type' => $validated['valid_id_type'] ?? $user->valid_id_type,
            'valid_id_path' => $validIdPath,
            'proof_of_residency_path' => $proofPath,
            'kyc_status' => $kycStatus,
            'kyc_remarks' => $kycRemarks,
            'kyc_verified_by' => $kycVerifiedBy,
            'kyc_verified_at' => $kycVerifiedAt,
        ]);

        $msg = $uploadedNewId
            ? 'Profile & KYC documents updated! Your verification is now pending Barangay Hall review.'
            : 'Profile updated successfully!';

        return back()->with('success', $msg);
    }

    public function kycDocument(string $type)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rawPath = match ($type) {
            'valid_id' => $user->valid_id_path,
            'proof_of_residency' => $user->proof_of_residency_path,
            default => abort(404),
        };

        $fullPath = $this->kycStorage->getPrivateFilePath($rawPath);
        if (!$fullPath) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}