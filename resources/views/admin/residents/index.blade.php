@extends('layouts.admin')

@section('title', 'Residents RBI & KYC Verification')

@push('styles')
<style>
    .filter-bar {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 10px;
        align-items: end;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d8edf8;
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 22px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.08);
    }
    .rbi-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
    .rbi-tag {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
        background: #e0f2fe;
        color: #0369a1;
    }
    .tag-hit { background: #fee2e2; color: #991b1b; }
    .kyc-pending { background: #fef3c7; color: #92400e; }
    .kyc-verified { background: #dcfce7; color: #166534; }
    .kyc-rejected { background: #fee2e2; color: #991b1b; }
    .purok-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
        margin-top: 14px;
    }
    .purok-chip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: #f8fcff;
        border: 1px solid #d8edf8;
        border-radius: 10px;
        font-size: 13px;
    }
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(18, 59, 93, 0.52);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-backdrop.open { display: flex; }
    .modal-card {
        width: 100%;
        max-width: 640px;
        max-height: 90vh;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #d8edf8;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 18px 40px rgba(18, 59, 93, 0.25);
    }
    .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .doc-preview-box {
        margin-top: 12px;
        padding: 10px;
        background: #f5fbff;
        border: 1px solid #d8edf8;
        border-radius: 10px;
        text-align: center;
    }
    .doc-preview-box img { max-width: 100%; max-height: 220px; border-radius: 8px; object-fit: contain; }
    @media (max-width: 820px) { .filter-bar, .modal-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">RA 7160 Sec. 394 &middot; RBI Registry {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Residents Registry (RBI) &amp; KYC Verification</h1>
        <p>Inspect resident Valid IDs, Proof of Residency, Purok assignments, and length of stay before approving document requests.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">🆔</div>
</section>

@if (session('success'))
    <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<section class="mini-stats">
    <div class="mini-stat"><span>Total Residents</span><strong>{{ $counts['total'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #eab308;"><span>Pending KYC Review</span><strong>{{ $counts['pending'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #16a34a;"><span>Verified Residents</span><strong>{{ $counts['verified'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #c9364b;"><span>Rejected / Needs Re-upload</span><strong>{{ $counts['rejected'] }}</strong></div>
</section>

<form method="GET" action="{{ route('admin.residents.index') }}" class="filter-bar">
    <div>
        <label class="form-label" style="margin-top: 0;">Search Resident</label>
        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Full name, email, or contact number...">
    </div>
    <div>
        <label class="form-label" style="margin-top: 0;">KYC Status</label>
        <select name="kyc_status" class="form-control">
            <option value="all" {{ $kycFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="pending" {{ $kycFilter === 'pending' ? 'selected' : '' }}>Pending Review</option>
            <option value="verified" {{ $kycFilter === 'verified' ? 'selected' : '' }}>Verified</option>
            <option value="rejected" {{ $kycFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </div>
    <div>
        <label class="form-label" style="margin-top: 0;">Purok / Sitio</label>
        <select name="purok_id" class="form-control">
            <option value="">All Puroks</option>
            @foreach ($puroks as $purok)
                <option value="{{ $purok->id }}" {{ (string) $purokFilter === (string) $purok->id ? 'selected' : '' }}>{{ $purok->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn-winter">Filter</button>
        <a href="{{ route('admin.residents.index') }}" class="btn-outline">Reset</a>
    </div>
</form>

<section class="glass-card">
    <h2>📋 Registered Barangay Residents</h2>

    @if ($residents->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Resident &amp; Tags</th>
                        <th>Purok &amp; Residency</th>
                        <th>Submitted KYC Docs</th>
                        <th>KYC Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($residents as $resident)
                        @php
                            $kycClass = match ($resident->kyc_status) {
                                'verified' => 'kyc-verified',
                                'rejected' => 'kyc-rejected',
                                default => 'kyc-pending',
                            };
                            $residentPayload = [
                                'id' => $resident->id,
                                'full_name' => $resident->full_name,
                                'email' => $resident->email,
                                'contact_number' => $resident->contact_number,
                                'address' => $resident->address,
                                'purok_id' => $resident->purok_id,
                                'birth_date' => $resident->birth_date?->format('M d, Y'),
                                'sex' => $resident->sex,
                                'civil_status' => $resident->civil_status,
                                'residency_since' => $resident->residency_since?->format('Y-m-d'),
                                'residency_label' => $resident->residencyDurationLabel(),
                                'is_registered_voter' => $resident->is_registered_voter,
                                'is_senior_citizen' => $resident->is_senior_citizen,
                                'is_pwd' => $resident->is_pwd,
                                'valid_id_type' => $resident->valid_id_type,
                                'valid_id_url' => $resident->valid_id_path ? route('admin.residents.document', [$resident->id, 'valid_id']) : null,
                                'proof_url' => $resident->proof_of_residency_path ? route('admin.residents.document', [$resident->id, 'proof_of_residency']) : null,
                                'kyc_status' => $resident->kyc_status,
                                'kyc_remarks' => $resident->kyc_remarks,
                            ];
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $resident->full_name }}</strong>
                                <div style="font-size: 12px; color: #486b82;">{{ $resident->email }} &middot; {{ $resident->contact_number ?: 'No contact' }}</div>
                                <div class="rbi-tags">
                                    @if ($resident->is_registered_voter)<span class="rbi-tag">Voter</span>@endif
                                    @if ($resident->is_senior_citizen)<span class="rbi-tag">Senior</span>@endif
                                    @if ($resident->is_pwd)<span class="rbi-tag">PWD</span>@endif
                                    @if ($resident->active_blotter_count > 0)
                                        <span class="rbi-tag tag-hit">⚠️ {{ $resident->active_blotter_count }} Active Lupon Case</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong>{{ $resident->purok->name ?? 'Unassigned Purok' }}</strong>
                                <div style="font-size: 12px; color: #486b82;">Stay: {{ $resident->residencyDurationLabel() }}</div>
                            </td>
                            <td style="font-size: 12px;">
                                @if ($resident->valid_id_path)
                                    <a href="{{ route('admin.residents.document', [$resident->id, 'valid_id']) }}" target="_blank" style="color: #174b70; font-weight: bold;">
                                        🆔 {{ $resident->valid_id_type ?: 'View Valid ID' }}
                                    </a>
                                @else
                                    <span style="color: #94a3b8;">No ID uploaded</span>
                                @endif
                                @if ($resident->proof_of_residency_path)
                                    <br>
                                    <a href="{{ route('admin.residents.document', [$resident->id, 'proof_of_residency']) }}" target="_blank" style="color: #25866b; font-weight: bold;">
                                        🏠 Proof of Residency
                                    </a>
                                @endif
                            </td>
                            <td>
                                <span class="status-pill {{ $kycClass }}">{{ ucfirst($resident->kyc_status) }}</span>
                                @if ($resident->kyc_remarks)
                                    <div style="font-size: 11px; color: #647f91; margin-top: 4px; max-width: 220px;">{{ $resident->kyc_remarks }}</div>
                                @endif
                            </td>
                            <td>
                                @if (auth()->user()->canApproveRequests())
                                    <button type="button" class="btn-winter" style="padding: 8px 13px; font-size: 12px;"
                                        data-resident="{{ json_encode($residentPayload) }}"
                                        onclick="openKycModal(this)">
                                        🔍 Review / Verify
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

        <div style="margin-top: 16px;">{{ $residents->links() }}</div>
    @else
        <div class="empty-state">
            <div class="empty-icon">🆔</div>
            <h3>No Residents Found</h3>
            <p>Try clearing your filters.</p>
        </div>
    @endif
</section>

@if (auth()->user()->canManageGovernance())
    <section class="glass-card">
        <h2>📍 Purok / Sitio Management</h2>
        <form method="POST" action="{{ route('admin.residents.puroks.store') }}" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
            @csrf
            <div style="flex: 1; min-width: 180px;">
                <label class="form-label" style="margin-top: 0;">Purok / Sitio Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Purok 8 - Centro" required>
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label class="form-label" style="margin-top: 0;">Purok Leader (Optional)</label>
                <input type="text" name="sitio_leader" class="form-control" placeholder="Name of Purok Leader">
            </div>
            <button type="submit" class="btn-winter">➕ Add Purok</button>
        </form>

        <div class="purok-grid">
            @foreach ($puroks as $purok)
                <div class="purok-chip">
                    <div>
                        <strong>{{ $purok->name }}</strong>
                        @if ($purok->sitio_leader)
                            <div style="font-size: 11px; color: #647f91;">{{ $purok->sitio_leader }}</div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.residents.puroks.toggle', $purok->id) }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn-outline" style="padding: 4px 9px; font-size: 11px;">
                            {{ $purok->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
@endif

<div id="kycModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3 style="margin-top: 0; color: #174b70;">🆔 Inspect Resident RBI &amp; KYC</h3>
        <div id="kycResidentSummary" style="font-size: 13px; color: #315b75; background: #f8fcff; padding: 12px; border-radius: 10px; border: 1px solid #d8edf8; margin-bottom: 12px;"></div>
        <div id="kycDocsArea" class="modal-grid"></div>

        <form id="kycForm" method="POST" action="">
            @csrf
            <div class="modal-grid">
                <div>
                    <label class="form-label">Verification Decision *</label>
                    <select id="kyc_status" name="kyc_status" class="form-control" required>
                        <option value="verified">✓ Verified (Allow Document Requests)</option>
                        <option value="pending">⏳ Pending Review</option>
                        <option value="rejected">✕ Rejected (Require Re-upload)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Assigned Purok</label>
                    <select id="kyc_purok_id" name="purok_id" class="form-control">
                        <option value="">— Unassigned —</option>
                        @foreach ($puroks as $purok)
                            <option value="{{ $purok->id }}">{{ $purok->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label class="form-label">Residing in Brgy. Cawit Since</label>
            <input type="date" id="kyc_residency_since" name="residency_since" class="form-control">

            <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px; font-size: 13px; font-weight: bold; color: #174b70;">
                <label><input type="checkbox" id="kyc_voter" name="is_registered_voter" value="1"> Registered Voter</label>
                <label><input type="checkbox" id="kyc_senior" name="is_senior_citizen" value="1"> Senior Citizen</label>
                <label><input type="checkbox" id="kyc_pwd" name="is_pwd" value="1"> PWD</label>
            </div>

            <label class="form-label">KYC Remarks (Required if Rejected)</label>
            <textarea id="kyc_remarks" name="kyc_remarks" rows="2" maxlength="500" class="form-control" placeholder="e.g., Valid ID matches Barangay Cawit address; or Blurry ID photo, please re-upload..."></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                <button type="button" class="btn-outline" onclick="closeKycModal()">Cancel</button>
                <button type="submit" class="btn-winter">Save Verification</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openKycModal(btn) {
        const d = JSON.parse(btn.getAttribute('data-resident'));
        document.getElementById('kycForm').action = "{{ url('admin/residents') }}/" + d.id + "/verify";

        document.getElementById('kycResidentSummary').innerHTML =
            '<strong>' + escapeHtml(d.full_name) + '</strong> (' + escapeHtml(d.email) + ')<br>' +
            'Address: ' + escapeHtml(d.address || 'Not provided') + '<br>' +
            'Birthdate: ' + escapeHtml(d.birth_date || '—') + ' | Sex: ' + escapeHtml(d.sex || '—') + ' | Civil Status: ' + escapeHtml(d.civil_status || '—');

        let docsHtml = '';
        if (d.valid_id_url) {
            docsHtml += '<div class="doc-preview-box"><strong>Valid ID (' + escapeHtml(d.valid_id_type || 'ID') + ')</strong><br><a href="' + d.valid_id_url + '" target="_blank"><img src="' + d.valid_id_url + '" alt="Valid ID" onerror="this.hidden=true"></a><br><a href="' + d.valid_id_url + '" target="_blank" style="font-size:12px;">Open Full File ↗</a></div>';
        } else {
            docsHtml += '<div class="doc-preview-box"><strong>Valid ID</strong><br><span style="color:#94a3b8;font-size:12px;">None uploaded</span></div>';
        }
        if (d.proof_url) {
            docsHtml += '<div class="doc-preview-box"><strong>Proof of Residency</strong><br><a href="' + d.proof_url + '" target="_blank"><img src="' + d.proof_url + '" alt="Proof" onerror="this.hidden=true"></a><br><a href="' + d.proof_url + '" target="_blank" style="font-size:12px;">Open Full File ↗</a></div>';
        } else {
            docsHtml += '<div class="doc-preview-box"><strong>Proof of Residency</strong><br><span style="color:#94a3b8;font-size:12px;">None uploaded</span></div>';
        }
        document.getElementById('kycDocsArea').innerHTML = docsHtml;

        document.getElementById('kyc_status').value = d.kyc_status || 'pending';
        document.getElementById('kyc_purok_id').value = d.purok_id || '';
        document.getElementById('kyc_residency_since').value = d.residency_since || '';
        document.getElementById('kyc_voter').checked = !!d.is_registered_voter;
        document.getElementById('kyc_senior').checked = !!d.is_senior_citizen;
        document.getElementById('kyc_pwd').checked = !!d.is_pwd;
        document.getElementById('kyc_remarks').value = d.kyc_remarks || '';
        document.getElementById('kycModal').classList.add('open');
    }
    function closeKycModal() { document.getElementById('kycModal').classList.remove('open'); }
    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }
    document.getElementById('kycModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeKycModal(); });
</script>
@endsection