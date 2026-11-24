<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficialRequest;
use App\Http\Requests\Admin\UpdateOfficialRequest;
use App\Models\BarangayOfficial;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OfficialSignatoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class OfficialController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly OfficialSignatoryService $signatoryService
    ) {
    }

    public function index()
    {
        Gate::authorize('viewAny', BarangayOfficial::class);

        $officials = BarangayOfficial::with('user')
            ->orderByDesc('is_active')
            ->orderByDesc('is_primary_signatory')
            ->orderByDesc('is_attest_signatory')
            ->orderBy('id')
            ->get();

        $staffAccounts = User::whereIn('role', User::STAFF_ROLES)
            ->where('account_status', 'active')
            ->orderBy('full_name')
            ->get();

        $counts = [
            'total' => $officials->count(),
            'active' => $officials->where('is_active', true)->count(),
            'with_signature' => $officials->whereNotNull('signature_path')->count(),
        ];

        return view('admin.officials.index', compact('officials', 'staffAccounts', 'counts'));
    }

    public function store(StoreOfficialRequest $request)
    {
        Gate::authorize('create', BarangayOfficial::class);

        $validated = $request->validated();
        $isPrimary = $request->boolean('is_primary_signatory');
        $isAttest = $request->boolean('is_attest_signatory');
        $isActive = ($isPrimary || $isAttest) ? true : $request->boolean('is_active', true);

        $signaturePath = $request->hasFile('signature')
            ? $this->signatoryService->storeSignature($request->file('signature'))
            : null;

        $official = DB::transaction(function () use ($validated, $isPrimary, $isAttest, $isActive, $signaturePath) {
            if ($isPrimary) {
                BarangayOfficial::where('is_primary_signatory', true)->update(['is_primary_signatory' => false]);
            }
            if ($isAttest) {
                BarangayOfficial::where('is_attest_signatory', true)->update(['is_attest_signatory' => false]);
            }

            return BarangayOfficial::create([
                'user_id' => $validated['user_id'] ?? null,
                'full_name' => trim($validated['full_name']),
                'position' => $validated['position'],
                'designation_title' => trim((string) ($validated['designation_title'] ?? '')) ?: null,
                'committee' => trim((string) ($validated['committee'] ?? '')) ?: null,
                'term_start' => $validated['term_start'] ?? null,
                'term_end' => $validated['term_end'] ?? null,
                'signature_path' => $signaturePath,
                'is_primary_signatory' => $isPrimary,
                'is_attest_signatory' => $isAttest,
                'is_active' => $isActive,
            ]);
        });

        $this->activityLogger->log(
            action: 'Barangay Official Added',
            description: "Added {$official->full_name} ({$official->displayTitle()}).",
            category: 'officials',
            subject: $official,
            newValues: [
                'full_name' => $official->full_name,
                'position' => $official->position,
                'is_primary_signatory' => $official->is_primary_signatory,
                'is_attest_signatory' => $official->is_attest_signatory,
            ],
            request: $request
        );

        return back()->with('success', "Official record for {$official->full_name} saved.");
    }

    public function update(UpdateOfficialRequest $request, BarangayOfficial $official)
    {
        Gate::authorize('update', $official);

        $validated = $request->validated();
        $isPrimary = $request->boolean('is_primary_signatory');
        $isAttest = $request->boolean('is_attest_signatory');
        $isActive = ($isPrimary || $isAttest) ? true : $request->boolean('is_active');

        $oldValues = [
            'full_name' => $official->full_name,
            'position' => $official->position,
            'designation_title' => $official->designation_title,
            'is_primary_signatory' => $official->is_primary_signatory,
            'is_attest_signatory' => $official->is_attest_signatory,
            'is_active' => $official->is_active,
        ];

        $signaturePath = $official->signature_path;
        if ($request->boolean('remove_signature') && !$request->hasFile('signature')) {
            $this->signatoryService->deleteSignature($signaturePath);
            $signaturePath = null;
        } elseif ($request->hasFile('signature')) {
            $signaturePath = $this->signatoryService->storeSignature($request->file('signature'), $signaturePath);
        }

        DB::transaction(function () use ($official, $validated, $isPrimary, $isAttest, $isActive, $signaturePath) {
            if ($isPrimary) {
                BarangayOfficial::whereKeyNot($official->id)
                    ->where('is_primary_signatory', true)
                    ->update(['is_primary_signatory' => false]);
            }
            if ($isAttest) {
                BarangayOfficial::whereKeyNot($official->id)
                    ->where('is_attest_signatory', true)
                    ->update(['is_attest_signatory' => false]);
            }

            $official->update([
                'user_id' => $validated['user_id'] ?? null,
                'full_name' => trim($validated['full_name']),
                'position' => $validated['position'],
                'designation_title' => trim((string) ($validated['designation_title'] ?? '')) ?: null,
                'committee' => trim((string) ($validated['committee'] ?? '')) ?: null,
                'term_start' => $validated['term_start'] ?? null,
                'term_end' => $validated['term_end'] ?? null,
                'signature_path' => $signaturePath,
                'is_primary_signatory' => $isPrimary,
                'is_attest_signatory' => $isAttest,
                'is_active' => $isActive,
            ]);
        });

        $this->activityLogger->log(
            action: 'Barangay Official Updated',
            description: "Updated official {$official->full_name} ({$official->displayTitle()}).",
            category: 'officials',
            subject: $official,
            oldValues: $oldValues,
            newValues: [
                'full_name' => $official->full_name,
                'position' => $official->position,
                'designation_title' => $official->designation_title,
                'is_primary_signatory' => $official->is_primary_signatory,
                'is_attest_signatory' => $official->is_attest_signatory,
                'is_active' => $official->is_active,
            ],
            request: $request
        );

        return back()->with('success', "Official {$official->full_name} updated successfully.");
    }

    public function setPrimary(Request $request, BarangayOfficial $official)
    {
        Gate::authorize('update', $official);

        DB::transaction(function () use ($official) {
            BarangayOfficial::where('is_primary_signatory', true)->update(['is_primary_signatory' => false]);
            $official->update([
                'is_primary_signatory' => true,
                'is_active' => true,
            ]);
        });

        $this->activityLogger->log(
            action: 'Primary Signatory Changed',
            description: "Set {$official->full_name} ({$official->displayTitle()}) as Primary E-Certificate Signatory.",
            category: 'officials',
            subject: $official,
            newValues: ['primary_signatory' => $official->full_name],
            request: $request
        );

        return back()->with('success', "{$official->full_name} is now the active Primary Signatory for E-Certificates.");
    }

    public function setAttesting(Request $request, BarangayOfficial $official)
    {
        Gate::authorize('update', $official);

        $turningOff = $official->is_attest_signatory;

        DB::transaction(function () use ($official, $turningOff) {
            BarangayOfficial::where('is_attest_signatory', true)->update(['is_attest_signatory' => false]);
            if (!$turningOff) {
                $official->update([
                    'is_attest_signatory' => true,
                    'is_active' => true,
                ]);
            }
        });

        $msg = $turningOff
            ? "Removed {$official->full_name} as Attesting Secretary."
            : "Set {$official->full_name} ({$official->displayTitle()}) as Attesting Signatory.";

        $this->activityLogger->log(
            action: 'Attesting Signatory Updated',
            description: $msg,
            category: 'officials',
            subject: $official,
            request: $request
        );

        return back()->with('success', $msg);
    }

    public function toggleActive(Request $request, BarangayOfficial $official)
    {
        Gate::authorize('update', $official);

        $oldActive = $official->is_active;
        $newActive = !$oldActive;

        $official->update([
            'is_active' => $newActive,
            'is_primary_signatory' => $newActive ? $official->is_primary_signatory : false,
            'is_attest_signatory' => $newActive ? $official->is_attest_signatory : false,
        ]);

        $stateLabel = $newActive ? 'Active' : 'Inactive';

        $this->activityLogger->log(
            action: 'Official Status Updated',
            description: "Marked {$official->full_name} ({$official->displayTitle()}) as {$stateLabel}.",
            category: 'officials',
            subject: $official,
            oldValues: ['is_active' => $oldActive],
            newValues: ['is_active' => $newActive],
            request: $request
        );

        return back()->with('success', "{$official->full_name} is now {$stateLabel}.");
    }

    public function signaturePreview(BarangayOfficial $official)
    {
        Gate::authorize('viewAny', BarangayOfficial::class);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (empty($official->signature_path) || !$disk->exists($official->signature_path)) {
            abort(404);
        }

        return response()->file($disk->path($official->signature_path), [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}