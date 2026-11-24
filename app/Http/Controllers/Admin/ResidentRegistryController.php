<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePurokRequest;
use App\Http\Requests\Admin\VerifyResidentKycRequest;
use App\Models\BlotterRecord;
use App\Models\Notification;
use App\Models\Purok;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\KycStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ResidentRegistryController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly KycStorageService $kycStorage
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAnyResident', User::class);

        $search = trim((string) $request->query('search', ''));
        $kycFilter = trim((string) $request->query('kyc_status', 'all'));
        $purokFilter = trim((string) $request->query('purok_id', ''));

        $query = User::with(['purok', 'kycVerifier'])
            ->withCount([
                'blotterRecords as active_blotter_count' => fn ($q) => $q->whereIn('status', BlotterRecord::ACTIVE_DEROGATORY_STATUSES),
            ])
            ->where('role', 'resident')
            ->orderByRaw("CASE kyc_status WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 WHEN 'verified' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at');

        if ($kycFilter !== '' && $kycFilter !== 'all') {
            $query->where('kyc_status', $kycFilter);
        }

        if ($purokFilter !== '') {
            $query->where('purok_id', (int) $purokFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $residents = $query->paginate(15)->withQueryString();
        $puroks = Purok::orderBy('name')->get();

        $allResidents = User::where('role', 'resident')->select('kyc_status')->get();
        $counts = [
            'total' => $allResidents->count(),
            'pending' => $allResidents->where('kyc_status', 'pending')->count(),
            'verified' => $allResidents->where('kyc_status', 'verified')->count(),
            'rejected' => $allResidents->where('kyc_status', 'rejected')->count(),
        ];

        return view('admin.residents.index', compact(
            'residents',
            'puroks',
            'counts',
            'search',
            'kycFilter',
            'purokFilter'
        ));
    }

    public function verifyKyc(VerifyResidentKycRequest $request, User $resident)
    {
        Gate::authorize('verifyResident', $resident);

        $validated = $request->validated();
        $oldStatus = $resident->kyc_status;
        $newStatus = $validated['kyc_status'];
        $remarks = trim((string) ($validated['kyc_remarks'] ?? ''));

        $resident->update([
            'kyc_status' => $newStatus,
            'purok_id' => $validated['purok_id'] ?? $resident->purok_id,
            'residency_since' => $validated['residency_since'] ?? $resident->residency_since,
            'is_registered_voter' => $request->boolean('is_registered_voter'),
            'is_senior_citizen' => $request->boolean('is_senior_citizen'),
            'is_pwd' => $request->boolean('is_pwd'),
            'kyc_remarks' => $remarks !== '' ? $remarks : null,
            'kyc_verified_by' => $newStatus === 'verified' ? Auth::id() : null,
            'kyc_verified_at' => $newStatus === 'verified' ? now('Asia/Manila') : null,
        ]);

        if ($oldStatus !== $newStatus) {
            [$title, $msg] = match ($newStatus) {
                'verified' => [
                    'Resident Account Verified',
                    'Your Barangay Cawit RBI/KYC verification has been approved. You may now request barangay documents online.',
                ],
                'rejected' => [
                    'KYC Verification Requires Attention',
                    "Your account verification was declined. Reason: {$remarks}. Please update your Valid ID or details under My Profile.",
                ],
                default => [
                    'KYC Status Set to Pending',
                    'Your resident verification has been returned to Pending review.',
                ],
            };

            Notification::create([
                'user_id' => $resident->id,
                'title' => $title,
                'message' => $msg,
            ]);
        }

        $this->activityLogger->log(
            action: 'Resident KYC Updated',
            description: "Updated RBI/KYC for {$resident->full_name} ({$resident->email}) from {$oldStatus} to {$newStatus}."
                . ($remarks !== '' ? " | Remarks: {$remarks}" : ''),
            category: 'residents',
            subject: $resident,
            oldValues: ['kyc_status' => $oldStatus],
            newValues: ['kyc_status' => $newStatus, 'purok_id' => $resident->purok_id, 'kyc_remarks' => $remarks],
            request: $request
        );

        return back()->with('success', "Resident {$resident->full_name} KYC status updated to " . ucfirst($newStatus) . '.');
    }

    public function storePurok(StorePurokRequest $request)
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (!$actor->canManageGovernance()) {
            abort(403);
        }

        $validated = $request->validated();

        $purok = Purok::create([
            'name' => trim($validated['name']),
            'sitio_leader' => trim((string) ($validated['sitio_leader'] ?? '')) ?: null,
            'is_active' => true,
        ]);

        $this->activityLogger->log(
            action: 'Purok Created',
            description: "Added {$purok->name}" . ($purok->sitio_leader ? " (Leader: {$purok->sitio_leader})" : '') . '.',
            category: 'residents',
            subject: $purok,
            request: $request
        );

        return back()->with('success', "{$purok->name} added successfully.");
    }

    public function togglePurok(Request $request, Purok $purok)
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (!$actor->canManageGovernance()) {
            abort(403);
        }

        $old = $purok->is_active;
        $purok->update(['is_active' => !$old]);

        $this->activityLogger->log(
            action: 'Purok Status Updated',
            description: "Marked {$purok->name} as " . ($purok->is_active ? 'Active' : 'Inactive') . '.',
            category: 'residents',
            subject: $purok,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $purok->is_active],
            request: $request
        );

        return back()->with('success', "{$purok->name} status updated.");
    }

    public function viewDocument(User $resident, string $type)
    {
        Gate::authorize('viewAnyResident', User::class);

        $rawPath = match ($type) {
            'valid_id' => $resident->valid_id_path,
            'proof_of_residency' => $resident->proof_of_residency_path,
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