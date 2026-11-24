<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWalkInRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Purok;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FeeAndMandateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WalkInRequestController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly FeeAndMandateService $feeService
    ) {
    }

    public function create()
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (!$actor->canProcessRequests()) {
            abort(403);
        }

        $residents = User::with('purok')
            ->where('role', 'resident')
            ->where('kyc_status', 'verified')
            ->orderBy('full_name')
            ->get();

        $documents = DocumentType::where('is_active', true)->orderBy('document_name')->get();
        $puroks = Purok::where('is_active', true)->orderBy('name')->get();

        return view('admin.walk_in', compact('residents', 'documents', 'puroks'));
    }

    public function store(StoreWalkInRequest $request)
    {
        /** @var User $actor */
        $actor = Auth::user();
        $validated = $request->validated();

        $initialStatus = $validated['initial_status'];
        if ($initialStatus === 'Approved' && !$actor->canApproveRequests()) {
            return back()->withInput()->with('error', 'Barangay Clerks may only encode walk-in requests as Pending or Under Review.');
        }

        $docType = DocumentType::findOrFail($validated['document_type_id']);
        $paymentStatus = $validated['payment_status'];
        $exemptionType = $validated['fee_exemption_type'];

        if ($paymentStatus !== 'unpaid' && !$actor->canVerifyPayments()) {
            return back()->withInput()->with('error', 'Only Treasurer, Secretary, or Punong Barangay can record O.R. payments or fee waivers.');
        }

        $now = now('Asia/Manila');
        $referenceNo = 'BRG-' . $now->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        try {
            DB::transaction(function () use ($request, $validated, $docType, $initialStatus, $paymentStatus, $exemptionType, $now, $referenceNo, $actor) {
                if ($validated['resident_mode'] === 'new_walkin') {
                    $resident = User::create([
                        'full_name' => trim($validated['walkin_full_name']),
                        'email' => 'walkin.' . strtolower(Str::random(10)) . '@barangaycawit.local',
                        'password_hash' => Hash::make(Str::random(32)),
                        'role' => 'resident',
                        'contact_number' => trim((string) ($validated['walkin_contact_number'] ?? '')) ?: null,
                        'address' => trim($validated['walkin_address']),
                        'purok_id' => (int) $validated['walkin_purok_id'],
                        'residency_since' => $validated['walkin_residency_since'] ?? null,
                        'account_status' => 'active',
                        'kyc_status' => 'verified',
                        'kyc_remarks' => 'Walk-in counter verification by ' . $actor->full_name,
                        'kyc_verified_by' => $actor->id,
                        'kyc_verified_at' => $now,
                        'created_at' => $now,
                    ]);
                } else {
                    $resident = User::findOrFail($validated['user_id']);
                }

                if ($docType->is_ra11261) {
                    $eligibility = $this->feeService->checkRa11261Eligibility($resident);
                    if (!$eligibility['eligible']) {
                        throw new \RuntimeException((string) $eligibility['reason']);
                    }
                }

                $assessedFee = (float) $docType->fee;
                $finalFee = $paymentStatus === 'exempt' ? 0.00 : $assessedFee;
                $effectiveExemption = $paymentStatus === 'exempt' ? $exemptionType : ($docType->is_ra11261 ? 'ra_11261' : 'none');

                if ($docType->is_ra11261 || $docType->is_fee_exempt_default || $assessedFee <= 0) {
                    $paymentStatus = 'exempt';
                    $finalFee = 0.00;
                    $effectiveExemption = $docType->is_ra11261 ? 'ra_11261' : 'indigency';
                }

                $metadata = [];
                if ($docType->requires_business_details) {
                    $metadata = [
                        'business_name' => trim((string) ($validated['business_name'] ?? '')),
                        'business_address' => trim((string) ($validated['business_address'] ?? '')),
                        'nature_of_business' => trim((string) ($validated['nature_of_business'] ?? '')),
                        'application_type' => $validated['application_type'] ?? 'New',
                        'dti_sec_no' => trim((string) ($validated['dti_sec_no'] ?? '')) ?: null,
                        'ctc_no' => trim((string) ($validated['ctc_no'] ?? '')) ?: null,
                    ];
                } elseif ($docType->is_ra11261) {
                    $metadata = [
                        'oath_of_undertaking_accepted' => true,
                        'residency_months_at_request' => $resident->residencyMonths(),
                    ];
                    $resident->update(['ra11261_availed_at' => $now]);
                }

                $docRequest = DocumentRequest::create([
                    'reference_no' => $referenceNo,
                    'is_walk_in' => true,
                    'user_id' => $resident->id,
                    'document_type_id' => $docType->id,
                    'purpose_category' => trim($validated['purpose_category']),
                    'purpose' => trim($validated['purpose']),
                    'document_metadata' => !empty($metadata) ? $metadata : null,
                    'status' => $initialStatus,
                    'admin_remarks' => 'Walk-in request encoded at Barangay Hall.',
                    'processed_by' => $actor->id,
                    'encoded_by' => $actor->id,
                    'assessed_fee' => $assessedFee,
                    'fee_exemption_type' => $effectiveExemption,
                    'final_fee' => $finalFee,
                    'payment_status' => $paymentStatus,
                    'official_receipt_no' => $paymentStatus === 'paid' ? trim((string) $validated['official_receipt_no']) : null,
                    'official_receipt_date' => $paymentStatus === 'paid' ? $validated['official_receipt_date'] : null,
                    'payment_verified_by' => $paymentStatus !== 'unpaid' ? $actor->id : null,
                    'payment_verified_at' => $paymentStatus !== 'unpaid' ? $now : null,
                    'requested_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->activityLogger->log(
                    action: 'Walk-In Request Encoded',
                    description: "Walk-In Ref: {$referenceNo} | Resident: {$resident->full_name} | Document: {$docType->document_name} | Status: {$initialStatus}",
                    category: 'request',
                    subject: $docRequest,
                    newValues: ['reference_no' => $referenceNo, 'is_walk_in' => true, 'status' => $initialStatus],
                    request: $request
                );
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.requests.index', ['search' => $referenceNo])
            ->with('success', "Walk-in request recorded! Reference No.: {$referenceNo}");
    }
}