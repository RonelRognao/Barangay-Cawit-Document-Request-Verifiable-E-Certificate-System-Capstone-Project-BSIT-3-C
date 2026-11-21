<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\DocumentRequest;
use App\Models\ECertificate;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
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

    public function manageRequests()
    {
        $requests = DocumentRequest::with(['user', 'documentType'])
            ->orderByDesc('requested_at')
            ->get();

        return view('admin.manage_requests', compact('requests'));
    }

    public function updateRequest(Request $request, DocumentRequest $documentRequest)
    {
        $allowedStatuses = ['Pending', 'Under Review', 'Approved', 'Rejected', 'Completed'];

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'admin_remarks' => ['nullable', 'string'],
        ]);

        $newStatus = $validated['status'];
        $remarks = trim($validated['admin_remarks'] ?? '');
        $statusChanged = false;

        DB::transaction(function () use ($documentRequest, $newStatus, $remarks, &$statusChanged) {
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
                'updated_at' => now(),
            ]);

            if ($statusChanged) {
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

                AdminActivityLog::create([
                    'admin_id' => Auth::id(),
                    'action' => $action,
                    'description' => $description,
                ]);
            }
        });

        $msg = $statusChanged
            ? 'Request updated successfully! Resident notification has been sent.'
            : 'Request information saved successfully.';

        return back()->with('success', $msg);
    }

    public function issueCertificates()
    {
        $approvedRequests = DocumentRequest::with(['user', 'documentType'])
            ->where('status', 'Approved')
            ->doesntHave('certificate')
            ->orderBy('requested_at', 'asc')
            ->get();

        $issuedCertificates = ECertificate::with(['request.user', 'request.documentType'])
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

    public function issueCertificate(DocumentRequest $documentRequest)
    {
        try {
            $certificateNo = DB::transaction(function () use ($documentRequest) {
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

                $certificateNo = 'BRG-CERT-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $verificationToken = bin2hex(random_bytes(32));

                ECertificate::create([
                    'request_id' => $locked->id,
                    'certificate_no' => $certificateNo,
                    'verification_token' => $verificationToken,
                    'issued_by' => Auth::id(),
                    'issued_at' => now(),
                ]);

                AdminActivityLog::create([
                    'admin_id' => Auth::id(),
                    'action' => 'E-Certificate Issued',
                    'description' => "Certificate No.: {$certificateNo}"
                        . " | Reference No.: {$locked->reference_no}"
                        . " | Resident: {$locked->user->full_name}"
                        . " | Document: {$locked->documentType->document_name}"
                        . " | Status: Valid",
                ]);

                return $certificateNo;
            });

            return back()->with('success', "Certificate issued successfully! Certificate No.: {$certificateNo}");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function revokeCertificate(ECertificate $certificate)
    {
        try {
            $certificateNo = DB::transaction(function () use ($certificate) {
                $locked = ECertificate::with(['request.user', 'request.documentType'])
                    ->whereKey($certificate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->revoked_at !== null) {
                    throw new \RuntimeException('This certificate has already been revoked.');
                }

                $locked->update(['revoked_at' => now()]);

                AdminActivityLog::create([
                    'admin_id' => Auth::id(),
                    'action' => 'E-Certificate Revoked',
                    'description' => "Certificate No.: {$locked->certificate_no}"
                        . " | Reference No.: {$locked->request->reference_no}"
                        . " | Resident: {$locked->request->user->full_name}"
                        . " | Document: {$locked->request->documentType->document_name}"
                        . " | Status: Revoked",
                ]);

                return $locked->certificate_no;
            });

            return back()->with('success', "Certificate successfully revoked! Certificate No.: {$certificateNo}");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function certificateHistory()
    {
        $certificates = ECertificate::with(['request.user', 'request.documentType'])
            ->orderByDesc('issued_at')
            ->get();

        $totalCertificates = $certificates->count();
        $validCertificates = $certificates->whereNull('revoked_at')->count();
        $revokedCertificates = $certificates->whereNotNull('revoked_at')->count();

        return view('admin.certificate_history', compact(
            'certificates',
            'totalCertificates',
            'validCertificates',
            'revokedCertificates'
        ));
    }

    public function activityLogs()
    {
        $logs = AdminActivityLog::with('admin')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.activity_logs', compact('logs'));
    }

    public function toggleTheme()
    {
        $current = Setting::getValue('site_theme', 'christmas');
        $next = ($current === 'christmas') ? 'modern' : 'christmas';

        Setting::setValue('site_theme', $next);

        AdminActivityLog::create([
            'admin_id' => Auth::id(),
            'action' => 'Site Theme Updated',
            'description' => 'Changed system theme from ' . ucfirst($current) . ' to ' . ucfirst($next) . ' Mode.',
        ]);

        return back()->with('success', 'Site theme switched to ' . ucfirst($next) . ' Mode!');
    }
}