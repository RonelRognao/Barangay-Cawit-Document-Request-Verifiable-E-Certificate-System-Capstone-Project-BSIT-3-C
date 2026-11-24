<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\RevokeCertificateRequest;
use App\Http\Requests\Admin\UpdateDocumentRequestRequest;
use App\Models\AdminActivityLog;
use App\Models\DocumentRequest;
use App\Models\ECertificate;
use App\Models\Notification;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\CertificateVerificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\OfficialSignatoryService;
use App\Services\DerogatoryCheckService;
use App\Http\Requests\Admin\RecordPaymentRequest;
use App\Services\FeeAndMandateService;

class AdminController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly CertificateVerificationService $verificationService,
        private readonly OfficialSignatoryService $signatoryService,
        private readonly DerogatoryCheckService $derogatoryCheckService,
        private readonly FeeAndMandateService $feeService
    ) {
    }

    public function dashboard()
    {
        $stats = DocumentRequest::selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'Under Review' THEN 1 ELSE 0 END) AS under_review,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
        ")->first();

        $requests = DocumentRequest::with(['user', 'documentType'])
            ->orderByDesc('requested_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'requests'));
    }

    public function manageRequests(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $allStatuses = DocumentRequest::select('status')->get();

        $query = DocumentRequest::with(['user.purok', 'documentType', 'paymentVerifier'])
            ->orderByDesc('requested_at');

        if ($statusFilter !== '' && $statusFilter !== 'All') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$search}%"));
            });
        }

        $requests = $query->get();

        $derogatoryByUser = [];
        foreach ($requests as $req) {
            if ($req->user && !isset($derogatoryByUser[$req->user_id])) {
                $derogatoryByUser[$req->user_id] = $this->derogatoryCheckService->checkResident($req->user);
            }
        }

        $counts = [
            'total' => $allStatuses->count(),
            'pending' => $allStatuses->where('status', 'Pending')->count(),
            'under_review' => $allStatuses->where('status', 'Under Review')->count(),
            'approved' => $allStatuses->where('status', 'Approved')->count(),
        ];

        return view('admin.manage_requests', compact(
            'requests',
            'search',
            'statusFilter',
            'counts',
            'derogatoryByUser'
        ));
    }

    public function updateRequest(UpdateDocumentRequestRequest $request, DocumentRequest $documentRequest)
    {
        Gate::authorize('update', $documentRequest);

        $validated = $request->validated();
        $newStatus = $validated['status'];
        $remarks = trim((string) ($validated['admin_remarks'] ?? ''));
        $statusChanged = false;
        $derogatoryOverride = $request->boolean('derogatory_override');
        $check = $this->derogatoryCheckService->checkResident($documentRequest->user);

        if ($newStatus === 'Approved' && $documentRequest->status !== 'Approved' && $check['has_derogatory']) {
            if (!$derogatoryOverride || $remarks === '') {
                return back()->with('error', 'Approval blocked: this resident has an active Lupon/Blotter case. Check "Derogatory Override" and enter justification remarks to proceed.');
            }
        }

        DB::transaction(function () use ($request, $documentRequest, $newStatus, $remarks, $derogatoryOverride, &$statusChanged) {
            $locked = DocumentRequest::with(['user', 'documentType'])
                ->whereKey($documentRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $locked->status;
            $oldRemarks = $locked->admin_remarks ?? '';

            $statusChanged = ($oldStatus !== $newStatus);
            $remarksChanged = ($oldRemarks !== $remarks);

            $locked->update([
                'status' => $newStatus,
                'admin_remarks' => $remarks,
                'processed_by' => Auth::id(),
                'updated_at' => now('Asia/Manila'),
            ]);

            if ($statusChanged) {
                
                if ($newStatus === 'Rejected' && $oldStatus !== 'Rejected' && $locked->documentType->is_ra11261) {
                $locked->user?->update(['ra11261_availed_at' => null]);
            }
                $docName = $locked->documentType->document_name;

                [$title, $body] = match ($newStatus) {
                    'Pending' => ['Request Status Updated', "Your request for {$docName} has been returned to Pending status."],
                    'Under Review' => ['Request Under Review', "Your request for {$docName} is now under review."],
                    'Approved' => ['Document Request Approved', "Congratulations! Your request for {$docName} has been approved."],
                    'Rejected' => ['Document Request Rejected', "Your request for {$docName} has been rejected."],
                    'Completed' => ['Document Request Completed', "Your request for {$docName} has been marked as completed."],
                };

                $body .= " Reference Number: {$locked->reference_no}.";
                if ($remarks !== '') {
                    $body .= " Admin Remarks: {$remarks}";
                }

                Notification::create([
                    'user_id' => $locked->user_id,
                    'title' => $title,
                    'message' => $body,
                ]);
            }

            if ($statusChanged || $remarksChanged) {
                $action = $statusChanged ? 'Request Status Updated' : 'Request Remarks Updated';
                $description = "Reference No.: {$locked->reference_no}"
                    . " | Resident: {$locked->user->full_name}"
                    . " | Document: {$locked->documentType->document_name}";

                if ($statusChanged) {
                    $description .= " | Previous Status: {$oldStatus} | New Status: {$newStatus}";
                }
                if ($remarksChanged && $remarks !== '') {
                    $description .= " | Admin Remarks: {$remarks}";
                }

                if ($derogatoryOverride && $newStatus === 'Approved') {
                  $description .= ' | [DEROGATORY OVERRIDE]';
              }

              $newVals = ['status' => $newStatus, 'admin_remarks' => $remarks];
              if ($derogatoryOverride && $newStatus === 'Approved') {
                  $newVals['derogatory_override'] = true;
              }

              $this->activityLogger->log(
                  action: $action,
                  description: $description,
                  category: 'request',
                  subject: $locked,
                  oldValues: ['status' => $oldStatus, 'admin_remarks' => $oldRemarks],
                  newValues: $newVals,
                  request: $request
              );
            }
        });

        $msg = $statusChanged
            ? 'Request updated successfully! Resident notification has been sent.'
            : 'Request information saved successfully.';

        return back()->with('success', $msg);
    }

    public function recordPayment(RecordPaymentRequest $request, DocumentRequest $documentRequest)
    {
        Gate::authorize('recordPayment', $documentRequest);

        $validated = $request->validated();
        $paymentStatus = $validated['payment_status'];
        $exemptionType = $validated['fee_exemption_type'];
        $orNo = trim((string) ($validated['official_receipt_no'] ?? ''));
        $orDate = $validated['official_receipt_date'] ?? null;

        if ($paymentStatus === 'exempt' && $exemptionType === 'none' && (float) $documentRequest->assessed_fee > 0) {
            return back()->with('error', 'Please choose a valid Fee Exemption Type (Indigency, RA 11261, Senior Citizen, or PWD).');
        }

        DB::transaction(function () use ($request, $documentRequest, $paymentStatus, $exemptionType, $orNo, $orDate) {
            $locked = DocumentRequest::with(['user', 'documentType'])
                ->whereKey($documentRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldSnapshot = [
                'payment_status' => $locked->payment_status,
                'fee_exemption_type' => $locked->fee_exemption_type,
                'official_receipt_no' => $locked->official_receipt_no,
            ];

            $finalFee = $paymentStatus === 'exempt' ? 0.00 : (float) $locked->assessed_fee;
            $effectiveExemption = $paymentStatus === 'exempt' ? $exemptionType : 'none';

            $locked->update([
                'fee_exemption_type' => $effectiveExemption,
                'final_fee' => $finalFee,
                'payment_status' => $paymentStatus,
                'official_receipt_no' => $paymentStatus === 'paid' ? $orNo : null,
                'official_receipt_date' => $paymentStatus === 'paid' ? $orDate : null,
                'payment_verified_by' => Auth::id(),
                'payment_verified_at' => now('Asia/Manila'),
            ]);

            if ($paymentStatus !== 'unpaid') {
                $detail = $paymentStatus === 'paid'
                    ? "Payment verified (PHP " . number_format($finalFee, 2) . ", O.R. No.: {$orNo})."
                    : "Fee waived under " . (FeeAndMandateService::EXEMPTION_LABELS[$effectiveExemption] ?? $effectiveExemption) . '.';

                Notification::create([
                    'user_id' => $locked->user_id,
                    'title' => 'Payment / Fee Status Updated',
                    'message' => "For {$locked->documentType->document_name} (Ref: {$locked->reference_no}): {$detail}",
                ]);
            }

            $this->activityLogger->log(
                action: 'Payment & O.R. Recorded',
                description: "Ref: {$locked->reference_no} | Resident: {$locked->user->full_name} | Status: {$paymentStatus}"
                    . ($paymentStatus === 'paid' ? " | O.R. No.: {$orNo}" : " | Exemption: {$effectiveExemption}"),
                category: 'finance',
                subject: $locked,
                oldValues: $oldSnapshot,
                newValues: [
                    'payment_status' => $paymentStatus,
                    'fee_exemption_type' => $effectiveExemption,
                    'final_fee' => $finalFee,
                    'official_receipt_no' => $paymentStatus === 'paid' ? $orNo : null,
                ],
                request: $request
            );
        });

        return back()->with('success', "Payment / Official Receipt record saved for {$documentRequest->reference_no}.");
    }

    public function viewRequestAttachment(DocumentRequest $documentRequest)
    {
        Gate::authorize('view', $documentRequest);

        $fullPath = $this->feeService->getAttachmentPath($documentRequest->attachment_path);
        if (!$fullPath) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function issueCertificates()
    {
        $approvedRequests = DocumentRequest::with(['user', 'documentType'])
            ->where('status', 'Approved')
            ->doesntHave('certificate')
            ->orderBy('requested_at', 'asc')
            ->get();

        $issuedCertificates = ECertificate::with(['request.user', 'request.documentType', 'issuer'])
            ->orderByDesc('issued_at')
            ->get();

        return view('admin.issue_certificate', [
            'approved_requests' => $approvedRequests,
            'issued_certificates' => $issuedCertificates,
            'total_available' => $approvedRequests->count(),
            'total_issued' => $issuedCertificates->count(),
            'total_valid' => $issuedCertificates->whereNull('revoked_at')->count(),
            'total_revoked' => $issuedCertificates->whereNotNull('revoked_at')->count(),
        ]);
    }

    public function issueCertificate(Request $request, DocumentRequest $documentRequest)
    {
        Gate::authorize('issueCertificate', $documentRequest);

        try {
            $certificateNo = DB::transaction(function () use ($request, $documentRequest) {
                $locked = DocumentRequest::with(['user', 'documentType'])
                    ->whereKey($documentRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== 'Approved') {
                    throw new \RuntimeException('Only approved requests can receive certificates.');
                }

                if (ECertificate::where('request_id', $locked->id)->exists()) {
                    throw new \RuntimeException('A certificate has already been issued for this request.');
                }
                
                if (!$locked->isPaidOrExempt()) {
                throw new \RuntimeException('COA Guardrail: Record Official Receipt (O.R.) or Fee Exemption before issuing a certificate for a paid document.');
                }

                $issuedAt = now('Asia/Manila')->startOfSecond();
                $certificateNo = 'BRG-CERT-' . $issuedAt->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $verificationToken = bin2hex(random_bytes(32));

                $primaryOfficial = $this->signatoryService->getActivePrimarySignatory();
                $attestingOfficial = $this->signatoryService->getActiveAttestingSignatory();
                
                
                $validityDays = max(1, (int) ($locked->documentType->validity_days ?? 180));

                $certificate = ECertificate::create([
                    'request_id' => $locked->id,
                    'certificate_no' => $certificateNo,
                    'verification_token' => $verificationToken,
                    'issued_by' => Auth::id(),
                    'primary_official_id' => $primaryOfficial?->id,
                    'signatory_name' => $primaryOfficial?->full_name ?? 'ERNISTO DAEN',
                    'signatory_title' => $primaryOfficial?->displayTitle() ?? 'Punong Barangay / Barangay Captain',
                    'attested_by_name' => $attestingOfficial?->full_name,
                    'attested_by_title' => $attestingOfficial?->displayTitle(),
                    'issued_at' => $issuedAt,
                    'expires_at' => $issuedAt->copy()->addDays($validityDays),
                ]);

                $certificate->update([
                    'verification_hash' => $this->verificationService->computeHash($certificate),
                ]);

                $oldStatus = $locked->status;
                $locked->update([
                    'status' => 'Completed',
                    'processed_by' => Auth::id(),
                    'updated_at' => $issuedAt,
                ]);

                Notification::create([
                    'user_id' => $locked->user_id,
                    'title' => 'E-Certificate Issued',
                    'message' => "Your official {$locked->documentType->document_name} e-certificate is now ready! "
                        . "Certificate No.: {$certificateNo} (Reference No.: {$locked->reference_no}). "
                        . "You can view and print it under My E-Certificates.",
                ]);

                $this->activityLogger->log(
                    action: 'E-Certificate Issued',
                    description: "Certificate No.: {$certificateNo}"
                        . " | Reference No.: {$locked->reference_no}"
                        . " | Resident: {$locked->user->full_name}"
                        . " | Document: {$locked->documentType->document_name}"
                        . " | Status: Valid",
                    category: 'certificate',
                    subject: $certificate,
                    oldValues: ['request_status' => $oldStatus],
                    newValues: ['request_status' => 'Completed', 'certificate_no' => $certificateNo],
                    request: $request
                );

                return $certificateNo;
            });

            return back()->with('success', "Certificate issued successfully! Certificate No.: {$certificateNo}");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function revokeCertificate(RevokeCertificateRequest $request, ECertificate $certificate)
    {
        Gate::authorize('revoke', $certificate);

        $reason = trim((string) ($request->validated('revocation_reason') ?? ''));

        try {
            $certificateNo = DB::transaction(function () use ($request, $certificate, $reason) {
                $locked = ECertificate::with(['request.user', 'request.documentType'])
                    ->whereKey($certificate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->revoked_at !== null) {
                    throw new \RuntimeException('This certificate has already been revoked.');
                }

                $revokedAt = now('Asia/Manila')->startOfSecond();

                $locked->update([
                    'revoked_at' => $revokedAt,
                    'revoked_by' => Auth::id(),
                    'revocation_reason' => $reason !== '' ? $reason : null,
                ]);

                $desc = "Certificate No.: {$locked->certificate_no}"
                    . " | Reference No.: {$locked->request->reference_no}"
                    . " | Resident: {$locked->request->user->full_name}"
                    . " | Document: {$locked->request->documentType->document_name}"
                    . " | Status: Revoked";

                if ($reason !== '') {
                    $desc .= " | Reason: {$reason}";
                }

                $this->activityLogger->log(
                    action: 'E-Certificate Revoked',
                    description: $desc,
                    category: 'certificate',
                    subject: $locked,
                    oldValues: ['status' => 'Valid', 'revoked_at' => null],
                    newValues: [
                        'status' => 'Revoked',
                        'revoked_at' => $revokedAt->format('Y-m-d H:i:s'),
                        'revocation_reason' => $reason !== '' ? $reason : null,
                    ],
                    request: $request
                );

                $notifMsg = "Your {$locked->request->documentType->document_name} e-certificate ({$locked->certificate_no}) "
                    . "has been revoked by the barangay administrator and is no longer valid.";
                if ($reason !== '') {
                    $notifMsg .= " Reason: {$reason}";
                }

                Notification::create([
                    'user_id' => $locked->request->user_id,
                    'title' => 'E-Certificate Revoked',
                    'message' => $notifMsg,
                ]);

                return $locked->certificate_no;
            });

            return back()->with('success', "Certificate successfully revoked! Certificate No.: {$certificateNo}");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function certificateHistory(Request $request)
{
    $search = trim((string) $request->query('search', ''));
    $statusFilter = trim((string) $request->query('status', 'all'));

    $query = ECertificate::with(['request.user', 'request.documentType'])
        ->orderByDesc('issued_at');

    if ($statusFilter === 'valid') {
        $query->whereNull('revoked_at')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now('Asia/Manila')));
    } elseif ($statusFilter === 'revoked') {
        $query->whereNotNull('revoked_at');
    } elseif ($statusFilter === 'expired') {
        $query->whereNull('revoked_at')->whereNotNull('expires_at')->where('expires_at', '<=', now('Asia/Manila'));
    }

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('certificate_no', 'like', "%{$search}%")
                ->orWhereHas('request', fn ($r) => $r->where('reference_no', 'like', "%{$search}%"))
                ->orWhereHas('request.user', fn ($u) => $u->where('full_name', 'like', "%{$search}%"));
        });
    }

    $certificates = $query->paginate(15)->withQueryString();
    $allCerts = ECertificate::select('revoked_at', 'expires_at')->get();

    $totalCertificates = $allCerts->count();
    $revokedCertificates = $allCerts->whereNotNull('revoked_at')->count();
    $expiredCertificates = $allCerts->whereNull('revoked_at')->filter(fn ($c) => $c->expires_at && Carbon::parse($c->expires_at)->isPast())->count();
    $validCertificates = $totalCertificates - $revokedCertificates - $expiredCertificates;

    return view('admin.certificate_history', compact(
        'certificates',
        'totalCertificates',
        'validCertificates',
        'revokedCertificates',
        'expiredCertificates',
        'search',
        'statusFilter'
    ));
}

public function printCertificate(string $certificateNo)
{
    $certificate = ECertificate::with(['request.documentType', 'request.user', 'primaryOfficial'])
        ->where('certificate_no', $certificateNo)
        ->firstOrFail();

    Gate::authorize('view', $certificate);

    if ($certificate->revoked_at !== null) {
        return back()->with('error', "Certificate {$certificateNo} is revoked and cannot be printed.");
    }

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

    public function activityLogs(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', 'operational_only')),
            'action' => trim((string) $request->query('action', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $logs = $this->buildActivityLogsQuery($filters)->paginate(20)->withQueryString();
        $availableActions = AdminActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $operationalCount = AdminActivityLog::where('log_category', '!=', 'ui_preference')->count();
        $totalCount = AdminActivityLog::count();

        return view('admin.activity_logs', compact(
            'logs',
            'filters',
            'availableActions',
            'operationalCount',
            'totalCount'
        ));
    }

    public function exportActivityLogs(Request $request): StreamedResponse
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', 'operational_only')),
            'action' => trim((string) $request->query('action', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $logs = $this->buildActivityLogsQuery($filters)->get();

        $this->activityLogger->log(
            action: 'Activity Logs Exported',
            description: 'Exported ' . $logs->count() . ' activity log record(s) to CSV.',
            category: 'security',
            request: $request
        );

        $filename = 'barangay-cawit-activity-logs-' . now('Asia/Manila')->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Log ID', 'Category', 'Actor', 'Role', 'Action', 'Description',
                'Old Values', 'New Values', 'IP Address', 'Timestamp (Asia/Manila)',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->log_category ?? 'operational',
                    $this->escapeCsvCell($log->actor_name ?? $log->admin?->full_name ?? 'System'),
                    $this->escapeCsvCell($log->actor_role ?? $log->admin?->role ?? 'system'),
                    $this->escapeCsvCell($log->action),
                    $this->escapeCsvCell($log->description),
                    $this->escapeCsvCell($log->old_values ? json_encode($log->old_values) : ''),
                    $this->escapeCsvCell($log->new_values ? json_encode($log->new_values) : ''),
                    $log->ip_address ?? '',
                    $log->created_at ? Carbon::parse($log->created_at)->format('Y-m-d h:i:s A') : '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function toggleTheme(Request $request)
    {
        $current = Setting::getValue('site_theme', 'christmas');
        $next = ($current === 'christmas') ? 'modern' : 'christmas';

        Setting::setValue('site_theme', $next);

        $this->activityLogger->log(
            action: 'Site Theme Updated',
            description: 'Changed system theme from ' . ucfirst($current) . ' to ' . ucfirst($next) . ' Mode.',
            category: 'ui_preference',
            oldValues: ['site_theme' => $current],
            newValues: ['site_theme' => $next],
            request: $request
        );

        return back()->with('success', 'Site theme switched to ' . ucfirst($next) . ' Mode!');
    }

    private function buildActivityLogsQuery(array $filters): Builder
    {
        $query = AdminActivityLog::with('admin')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($filters['category'] === 'operational_only') {
            $query->where('log_category', '!=', 'ui_preference');
        } elseif ($filters['category'] !== '' && $filters['category'] !== 'all') {
            $query->where('log_category', $filters['category']);
        }

        if ($filters['action'] !== '') {
            $query->where('action', $filters['action']);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('admin', fn (Builder $u) => $u->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function escapeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}