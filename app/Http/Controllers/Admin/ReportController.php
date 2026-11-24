<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\ECertificate;
use App\Models\Purok;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FeeAndMandateService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index(Request $request)
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (!$actor->canViewAuditLogs()) {
            abort(403);
        }

        $filters = $this->extractFilters($request);
        $requests = $this->buildRequestsQuery($filters)->get();

        $certificatesIssued = ECertificate::with(['request.documentType', 'request.user'])
            ->whereDate('issued_at', '>=', $filters['date_from'])
            ->whereDate('issued_at', '<=', $filters['date_to'])
            ->when($filters['document_type_id'] !== '', fn ($q) => $q->whereHas('request', fn ($r) => $r->where('document_type_id', (int) $filters['document_type_id'])))
            ->when($filters['purok_id'] !== '', fn ($q) => $q->whereHas('request.user', fn ($u) => $u->where('purok_id', (int) $filters['purok_id'])))
            ->get();

        $summary = [
            'total_requests' => $requests->count(),
            'completed' => $requests->where('status', 'Completed')->count(),
            'walk_in_count' => $requests->where('is_walk_in', true)->count(),
            'certs_issued' => $certificatesIssued->count(),
            'certs_revoked' => $certificatesIssued->whereNotNull('revoked_at')->count(),
            'total_revenue' => (float) $requests->where('payment_status', 'paid')->sum('final_fee'),
            'paid_count' => $requests->where('payment_status', 'paid')->count(),
            'exempt_count' => $requests->where('payment_status', 'exempt')->count(),
            'waived_value' => (float) $requests->where('payment_status', 'exempt')->sum('assessed_fee'),
        ];

        $byDocumentType = $requests->groupBy(fn ($r) => $r->documentType->document_name ?? 'Unknown')
            ->map(function ($group, $docName) {
                return [
                    'document_name' => $docName,
                    'total' => $group->count(),
                    'completed' => $group->where('status', 'Completed')->count(),
                    'paid_count' => $group->where('payment_status', 'paid')->count(),
                    'exempt_count' => $group->where('payment_status', 'exempt')->count(),
                    'revenue' => (float) $group->where('payment_status', 'paid')->sum('final_fee'),
                ];
            })
            ->values();

        $paidLedger = $requests->where('payment_status', 'paid')->values();
        $exemptLedger = $requests->where('payment_status', 'exempt')->values();

        $documentTypes = DocumentType::orderBy('document_name')->get();
        $puroks = Purok::orderBy('name')->get();

        return view('admin.reports.index', compact(
            'filters',
            'summary',
            'byDocumentType',
            'paidLedger',
            'exemptLedger',
            'documentTypes',
            'puroks'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (!$actor->canViewAuditLogs()) {
            abort(403);
        }

        $filters = $this->extractFilters($request);
        $rows = $this->buildRequestsQuery($filters)->get();

        $this->activityLogger->log(
            action: 'DILG / COA Report Exported',
            description: "Exported {$rows->count()} request/collection records ({$filters['date_from']} to {$filters['date_to']}).",
            category: 'finance',
            request: $request
        );

        $filename = "barangay-cawit-dilg-coa-report-{$filters['date_from']}-to-{$filters['date_to']}.csv";

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Reference No.', 'Channel', 'Resident Name', 'Purok', 'Document Type',
                'Purpose Category', 'Status', 'Assessed Fee', 'Exemption Type', 'Payable/Paid',
                'Payment Status', 'O.R. Number', 'O.R. Date', 'Certificate No.', 'Date Requested (PHT)',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->reference_no,
                    $row->is_walk_in ? 'Walk-In' : 'Online',
                    $this->escapeCsv($row->user?->full_name ?? '—'),
                    $this->escapeCsv($row->user?->purok?->name ?? 'Unassigned'),
                    $this->escapeCsv($row->documentType?->document_name ?? '—'),
                    $this->escapeCsv((string) ($row->purpose_category ?? '')),
                    $row->status,
                    number_format((float) $row->assessed_fee, 2, '.', ''),
                    FeeAndMandateService::EXEMPTION_LABELS[$row->fee_exemption_type] ?? $row->fee_exemption_type,
                    number_format((float) $row->final_fee, 2, '.', ''),
                    strtoupper($row->payment_status),
                    $this->escapeCsv((string) ($row->official_receipt_no ?? '')),
                    $row->official_receipt_date?->format('Y-m-d') ?? '',
                    $row->certificate?->certificate_no ?? '',
                    $row->requested_at ? Carbon::parse($row->requested_at)->timezone('Asia/Manila')->format('Y-m-d h:i A') : '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_from' => trim((string) $request->query('date_from', now('Asia/Manila')->startOfMonth()->format('Y-m-d'))),
            'date_to' => trim((string) $request->query('date_to', now('Asia/Manila')->format('Y-m-d'))),
            'document_type_id' => trim((string) $request->query('document_type_id', '')),
            'purok_id' => trim((string) $request->query('purok_id', '')),
        ];
    }

    private function buildRequestsQuery(array $filters): Builder
    {
        return DocumentRequest::with(['user.purok', 'documentType', 'certificate', 'paymentVerifier'])
            ->whereDate('requested_at', '>=', $filters['date_from'])
            ->whereDate('requested_at', '<=', $filters['date_to'])
            ->when($filters['document_type_id'] !== '', fn (Builder $q) => $q->where('document_type_id', (int) $filters['document_type_id']))
            ->when($filters['purok_id'] !== '', fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->where('purok_id', (int) $filters['purok_id'])))
            ->orderByDesc('requested_at');
    }

    private function escapeCsv(string $value): string
    {
        return ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $value : $value;
    }
}