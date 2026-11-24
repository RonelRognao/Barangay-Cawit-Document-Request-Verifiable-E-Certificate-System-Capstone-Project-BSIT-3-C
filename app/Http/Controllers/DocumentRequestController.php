<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resident\StoreDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Notification;
use App\Services\FeeAndMandateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DocumentRequestController extends Controller
{
    public function __construct(
        private readonly FeeAndMandateService $feeService
    ) {
    }

    public function create()
    {
        /** @var \App\Models\User $resident */
        $resident = Auth::user();

        $documents = DocumentType::where('is_active', true)
            ->orderBy('document_name', 'asc')
            ->get();

        $kycVerified = $resident->isKycVerified();
        $ra11261Check = $this->feeService->checkRa11261Eligibility($resident);

        return view('resident.request_document', compact('documents', 'resident', 'kycVerified', 'ra11261Check'));
    }

    public function store(StoreDocumentRequestRequest $request)
    {
        /** @var \App\Models\User $resident */
        $resident = Auth::user();

        if (!$resident->isKycVerified()) {
            return redirect()
                ->route('resident.request.create')
                ->with('error', 'Your Barangay RBI / KYC verification must be approved before submitting document requests.');
        }

        $validated = $request->validated();
        $selectedDocument = DocumentType::findOrFail($validated['document_type_id']);

        if ($selectedDocument->is_ra11261) {
            $eligibility = $this->feeService->checkRa11261Eligibility($resident);
            if (!$eligibility['eligible']) {
                return back()->withInput()->with('error', $eligibility['reason']);
            }
        }

        $feeData = $this->feeService->calculateInitialFee(
            $resident,
            $selectedDocument,
            (string) ($validated['fee_exemption_claim'] ?? 'none')
        );

        $attachmentPath = $request->hasFile('attachment')
            ? $this->feeService->storeAttachment($request->file('attachment'))
            : null;

        $metadata = [];
        if ($selectedDocument->requires_business_details) {
            $metadata = [
                'business_name' => trim((string) ($validated['business_name'] ?? '')),
                'business_address' => trim((string) ($validated['business_address'] ?? '')),
                'nature_of_business' => trim((string) ($validated['nature_of_business'] ?? '')),
                'application_type' => $validated['application_type'] ?? 'New',
                'dti_sec_no' => trim((string) ($validated['dti_sec_no'] ?? '')) ?: null,
                'ctc_no' => trim((string) ($validated['ctc_no'] ?? '')) ?: null,
            ];
        } elseif ($selectedDocument->is_ra11261) {
            $metadata = [
                'oath_of_undertaking_accepted' => true,
                'residency_months_at_request' => $resident->residencyMonths(),
                'school_or_qualification' => trim((string) ($validated['school_or_qualification'] ?? '')) ?: null,
            ];
        }

        $now = now('Asia/Manila');
        $referenceNo = 'BRG-' . $now->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        DB::transaction(function () use ($referenceNo, $resident, $selectedDocument, $validated, $metadata, $attachmentPath, $feeData, $now) {
            DocumentRequest::create([
                'reference_no' => $referenceNo,
                'user_id' => $resident->id,
                'document_type_id' => $selectedDocument->id,
                'purpose_category' => trim($validated['purpose_category']),
                'purpose' => trim($validated['purpose']),
                'document_metadata' => !empty($metadata) ? $metadata : null,
                'attachment_path' => $attachmentPath,
                'attachment_label' => $attachmentPath ? ($selectedDocument->requires_business_details ? 'DTI / Business Requirement' : 'Supporting Document') : null,
                'assessed_fee' => $feeData['assessed_fee'],
                'fee_exemption_type' => $feeData['exemption_type'],
                'final_fee' => $feeData['final_fee'],
                'payment_status' => $feeData['payment_status'],
                'requested_at' => $now,
            ]);

            if ($selectedDocument->is_ra11261) {
                $resident->update(['ra11261_availed_at' => $now]);
            }

            $feeNote = $feeData['final_fee'] > 0
                ? ' Assessed Fee: PHP ' . number_format($feeData['final_fee'], 2) . ' (payable at Barangay Treasurer for O.R.).'
                : ' Fee Waived / Exempt.';

            Notification::create([
                'user_id' => $resident->id,
                'title' => 'Document Request Submitted',
                'message' => "Your request for {$selectedDocument->document_name} has been submitted (Ref: {$referenceNo}).{$feeNote}",
            ]);
        });

        return redirect()
            ->route('resident.request.create')
            ->with('success', 'Your document request has been submitted successfully!')
            ->with('reference_no', $referenceNo);
    }

    public function viewAttachment(DocumentRequest $documentRequest)
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
}