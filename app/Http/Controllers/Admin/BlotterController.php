<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlotterRequest;
use App\Http\Requests\Admin\UpdateBlotterRequest;
use App\Models\BlotterRecord;
use App\Models\Purok;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BlotterController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', BlotterRecord::class);

        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', 'all'));

        $query = BlotterRecord::with(['respondent', 'purok', 'recorder'])
            ->orderByDesc('incident_date')
            ->orderByDesc('id');

        if ($statusFilter === 'active_derogatory') {
            $query->whereIn('status', BlotterRecord::ACTIVE_DEROGATORY_STATUSES);
        } elseif ($statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                    ->orWhere('respondent_name', 'like', "%{$search}%")
                    ->orWhere('complainant_name', 'like', "%{$search}%")
                    ->orWhere('incident_type', 'like', "%{$search}%");
            });
        }

        $records = $query->paginate(15)->withQueryString();
        $residents = User::where('role', 'resident')->orderBy('full_name')->get(['id', 'full_name', 'purok_id']);
        $puroks = Purok::where('is_active', true)->orderBy('name')->get();

        $all = BlotterRecord::select('status')->get();
        $counts = [
            'total' => $all->count(),
            'active_derogatory' => $all->whereIn('status', BlotterRecord::ACTIVE_DEROGATORY_STATUSES)->count(),
            'settled_dismissed' => $all->whereIn('status', ['Amicably Settled', 'Dismissed'])->count(),
        ];

        return view('admin.blotter.index', compact(
            'records',
            'residents',
            'puroks',
            'counts',
            'search',
            'statusFilter'
        ));
    }

    public function store(StoreBlotterRequest $request)
    {
        Gate::authorize('create', BlotterRecord::class);

        $validated = $request->validated();
        $respondentUser = !empty($validated['respondent_user_id'])
            ? User::find($validated['respondent_user_id'])
            : null;

        $respondentName = trim((string) ($validated['respondent_name'] ?? ''))
            ?: ($respondentUser?->full_name ?? 'Unknown Respondent');
        $purokId = $validated['purok_id'] ?? $respondentUser?->purok_id;
        $caseNo = 'LUPON-' . now('Asia/Manila')->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        $settledAt = in_array($validated['status'], ['Amicably Settled', 'Dismissed'], true)
            ? now('Asia/Manila')
            : null;

        $record = BlotterRecord::create([
            'case_no' => $caseNo,
            'respondent_user_id' => $respondentUser?->id,
            'respondent_name' => $respondentName,
            'complainant_name' => trim($validated['complainant_name']),
            'incident_type' => $validated['incident_type'],
            'incident_date' => $validated['incident_date'],
            'purok_id' => $purokId,
            'summary' => trim($validated['summary']),
            'status' => $validated['status'],
            'recorded_by' => Auth::id(),
            'settled_at' => $settledAt,
        ]);

        $this->activityLogger->log(
            action: 'Blotter Case Recorded',
            description: "Recorded {$record->case_no} | Respondent: {$record->respondent_name} | Type: {$record->incident_type} | Status: {$record->status}.",
            category: 'blotter',
            subject: $record,
            newValues: ['case_no' => $record->case_no, 'status' => $record->status],
            request: $request
        );

        return back()->with('success', "Blotter record {$record->case_no} created successfully.");
    }

    public function update(UpdateBlotterRequest $request, BlotterRecord $blotter)
    {
        Gate::authorize('update', $blotter);

        $validated = $request->validated();
        $oldStatus = $blotter->status;
        $newStatus = $validated['status'];
        $notes = trim((string) ($validated['resolution_notes'] ?? ''));

        $settledAt = in_array($newStatus, ['Amicably Settled', 'Dismissed'], true)
            ? ($blotter->settled_at ?? now('Asia/Manila'))
            : null;

        $blotter->update([
            'status' => $newStatus,
            'resolution_notes' => $notes !== '' ? $notes : null,
            'settled_at' => $settledAt,
        ]);

        $this->activityLogger->log(
            action: 'Blotter Case Updated',
            description: "Updated {$blotter->case_no} ({$blotter->respondent_name}) from {$oldStatus} to {$newStatus}.",
            category: 'blotter',
            subject: $blotter,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus, 'resolution_notes' => $notes],
            request: $request
        );

        return back()->with('success', "Blotter case {$blotter->case_no} updated to {$newStatus}.");
    }
}