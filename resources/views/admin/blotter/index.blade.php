@extends('layouts.admin')

@section('title', 'Blotter & Lupon Records')

@push('styles')
<style>
    .blotter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .filter-bar {
        display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
        background: rgba(255, 255, 255, 0.95); border: 1px solid #d8edf8;
        border-radius: 14px; padding: 16px 20px; margin-bottom: 22px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.08);
    }
    .pill-derogatory { background: #fee2e2; color: #991b1b; }
    .pill-cleared { background: #dcfce7; color: #166534; }
    .modal-backdrop {
        display: none; position: fixed; inset: 0; background: rgba(18, 59, 93, 0.52);
        z-index: 10000; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-backdrop.open { display: flex; }
    .modal-card {
        width: 100%; max-width: 520px; background: #ffffff; border: 1px solid #d8edf8;
        border-radius: 16px; padding: 24px; box-shadow: 0 18px 40px rgba(18, 59, 93, 0.25);
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Lupong Tagapamayapa (RA 7160) {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Barangay Blotter &amp; Derogatory Records</h1>
        <p>Log barangay disputes and conciliation cases. Active cases automatically trigger a derogatory alert on document requests.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">⚖️</div>
</section>

@if (session('success'))
    <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<section class="mini-stats">
    <div class="mini-stat"><span>Total Blotter Cases</span><strong>{{ $counts['total'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #c9364b;"><span>Active Derogatory Hits</span><strong>{{ $counts['active_derogatory'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #16a34a;"><span>Settled / Dismissed</span><strong>{{ $counts['settled_dismissed'] }}</strong></div>
</section>

@if (auth()->user()->canProcessRequests())
    <section class="glass-card">
        <h2>➕ Log New Blotter / Lupon Case</h2>
        <form method="POST" action="{{ route('admin.blotter.store') }}">
            @csrf
            <div class="blotter-grid">
                <div>
                    <label class="form-label">Linked Registered Resident (Optional)</label>
                    <select name="respondent_user_id" class="form-control">
                        <option value="">— Walk-in / Unregistered Respondent —</option>
                        @foreach ($residents as $res)
                            <option value="{{ $res->id }}">{{ $res->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Respondent Name (if walk-in or override)</label>
                    <input type="text" name="respondent_name" value="{{ old('respondent_name') }}" class="form-control" placeholder="Full name of respondent">
                </div>
                <div>
                    <label class="form-label">Complainant Name *</label>
                    <input type="text" name="complainant_name" value="{{ old('complainant_name') }}" class="form-control" required placeholder="Full name of complainant">
                </div>
                <div>
                    <label class="form-label">Incident Type *</label>
                    <select name="incident_type" class="form-control" required>
                        @foreach (\App\Models\BlotterRecord::INCIDENT_TYPES as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Incident Date *</label>
                    <input type="date" name="incident_date" value="{{ old('incident_date', now('Asia/Manila')->format('Y-m-d')) }}" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Purok / Location</label>
                    <select name="purok_id" class="form-control">
                        <option value="">— Auto / Select Purok —</option>
                        @foreach ($puroks as $purok)
                            <option value="{{ $purok->id }}">{{ $purok->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Case Status *</label>
                    <select name="status" class="form-control" required>
                        @foreach (\App\Models\BlotterRecord::STATUSES as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label class="form-label">Incident Summary *</label>
            <textarea name="summary" rows="2" class="form-control" required placeholder="Brief factual summary of the complaint or incident...">{{ old('summary') }}</textarea>

            <div style="margin-top: 16px; text-align: right;">
                <button type="submit" class="btn-winter">Save Blotter Entry</button>
            </div>
        </form>
    </section>
@endif

<form method="GET" action="{{ route('admin.blotter.index') }}" class="filter-bar">
    <input type="text" name="search" value="{{ $search }}" class="form-control" style="flex: 1; min-width: 220px;" placeholder="Search Case No., Respondent, or Complainant...">
    <select name="status" class="form-control" style="width: 230px;">
        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Case Statuses</option>
        <option value="active_derogatory" {{ $statusFilter === 'active_derogatory' ? 'selected' : '' }}>Active Derogatory Only</option>
        @foreach (\App\Models\BlotterRecord::STATUSES as $st)
            <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ $st }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn-winter">🔍 Filter</button>
    <a href="{{ route('admin.blotter.index') }}" class="btn-outline">Reset</a>
</form>

<section class="glass-card">
    <h2>📂 Blotter &amp; Lupon Case Log</h2>
    @if ($records->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Case No.</th>
                        <th>Respondent</th>
                        <th>Complainant &amp; Incident</th>
                        <th>Date &amp; Purok</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        @php
                            $isHit = $record->isDerogatory();
                            $blotterPayload = [
                                'id' => $record->id,
                                'case_no' => $record->case_no,
                                'respondent_name' => $record->respondent_name,
                                'status' => $record->status,
                                'resolution_notes' => $record->resolution_notes,
                            ];
                        @endphp
                        <tr>
                            <td><strong>{{ $record->case_no }}</strong></td>
                            <td>
                                <strong>{{ $record->respondent_name }}</strong>
                                @if ($record->respondent)
                                    <div style="font-size: 11px; color: #25866b;">✓ Linked to RBI Resident</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $record->incident_type }}</strong>
                                <div style="font-size: 12px; color: #486b82;">Complainant: {{ $record->complainant_name }}</div>
                                <div style="font-size: 12px; color: #647f91; margin-top: 3px;">{{ \Illuminate\Support\Str::limit($record->summary, 90) }}</div>
                            </td>
                            <td style="white-space: nowrap; font-size: 13px;">
                                {{ $record->incident_date->format('M d, Y') }}
                                <div style="font-size: 11px; color: #647f91;">{{ $record->purok->name ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="status-pill {{ $isHit ? 'pill-derogatory' : 'pill-cleared' }}">
                                    {{ $isHit ? '⚠️ ' : '✓ ' }}{{ $record->status }}
                                </span>
                                @if ($record->resolution_notes)
                                    <div style="font-size: 11px; color: #486b82; margin-top: 4px;">{{ $record->resolution_notes }}</div>
                                @endif
                            </td>
                            <td>
                                @if (auth()->user()->canApproveRequests())
                                    <button type="button" class="btn-outline"
                                        data-blotter="{{ json_encode($blotterPayload) }}"
                                        onclick="openBlotterModal(this)">
                                        ✏️ Update
                                    </button>
                                @else
                                    <span style="font-size: 12px; color: #94a3b8;">Secretary / PB Only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $records->links() }}</div>
    @else
        <div class="empty-state">
            <div class="empty-icon">⚖️</div>
            <h3>No Blotter Records Found</h3>
            <p>All clear—no matching blotter cases recorded.</p>
        </div>
    @endif
</section>

<div id="blotterModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3 style="margin-top: 0; color: #174b70;">⚖️ Update Lupon Case <span id="bmCaseNo"></span></h3>
        <form id="blotterForm" method="POST" action="">
            @csrf
            <label class="form-label">Case Status *</label>
            <select id="bm_status" name="status" class="form-control" required>
                @foreach (\App\Models\BlotterRecord::STATUSES as $st)
                    <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </select>

            <label class="form-label">Resolution / Settlement Notes</label>
            <textarea id="bm_notes" name="resolution_notes" rows="3" class="form-control" placeholder="Record amicable settlement terms, dismissal note, or CFA details..."></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                <button type="button" class="btn-outline" onclick="closeBlotterModal()">Cancel</button>
                <button type="submit" class="btn-winter">Save Case Status</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openBlotterModal(btn) {
        const d = JSON.parse(btn.getAttribute('data-blotter'));
        document.getElementById('blotterForm').action = "{{ url('admin/blotter') }}/" + d.id;
        document.getElementById('bmCaseNo').textContent = d.case_no + ' (' + d.respondent_name + ')';
        document.getElementById('bm_status').value = d.status;
        document.getElementById('bm_notes').value = d.resolution_notes || '';
        document.getElementById('blotterModal').classList.add('open');
    }
    function closeBlotterModal() { document.getElementById('blotterModal').classList.remove('open'); }
    document.getElementById('blotterModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeBlotterModal(); });
</script>
@endsection