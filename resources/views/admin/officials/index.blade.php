@extends('layouts.admin')

@section('title', 'Barangay Officials & Signatories')

@push('styles')
<style>
    .official-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }
    .modal-card .official-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .official-grid input[type="file"].form-control {
        padding: 9px 11px;
    }
    @media (max-width: 980px) {
        .official-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 580px) {
        .official-grid,
        .modal-card .official-grid { grid-template-columns: 1fr; }
    }
    .check-row {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-top: 14px;
        padding: 12px 14px;
        background: #f5fbff;
        border: 1px solid #d8edf8;
        border-radius: 10px;
        font-size: 13px;
        color: #174b70;
        font-weight: bold;
    }
    .check-row label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .sig-thumb {
        max-height: 40px;
        max-width: 120px;
        object-fit: contain;
        background: #ffffff;
        border: 1px solid #d8edf8;
        border-radius: 6px;
        padding: 3px;
    }
    .signatory-tag {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 14px;
        font-size: 11px;
        font-weight: bold;
        margin-top: 4px;
    }
    .tag-primary { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .tag-attest { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
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
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #d8edf8;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 18px 40px rgba(18, 59, 93, 0.25);
    }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Signatory &amp; Template Authority {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Barangay Officials &amp; Digital Signatories</h1>
        <p>Manage active Punong Barangay, OIC Kagawad, and Barangay Secretary signatories and private e-signatures for E-Certificates.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">🏛️</div>
</section>

@if (session('success'))
    <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<section class="mini-stats">
    <div class="mini-stat">
        <span>Total Recorded Officials</span>
        <strong>{{ $counts['total'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Active Officials</span>
        <strong>{{ $counts['active'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #25866b;">
        <span>Uploaded E-Signatures</span>
        <strong>{{ $counts['with_signature'] }}</strong>
    </div>
</section>

<section class="glass-card">
    <h2>➕ Register Barangay Official / Signatory</h2>
    <form method="POST" action="{{ route('admin.officials.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="official-grid">
            <div>
                <label class="form-label" for="full_name">Official Full Name</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" class="form-control" required placeholder="e.g., HON. ERNISTO DAEN">
            </div>
            <div>
                <label class="form-label" for="position">Position</label>
                <select id="position" name="position" class="form-control" required>
                    @foreach (\App\Models\BarangayOfficial::POSITIONS as $pos)
                        <option value="{{ $pos }}" {{ old('position') === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="designation_title">Certificate Signatory Title</label>
                <input type="text" id="designation_title" name="designation_title" value="{{ old('designation_title') }}" class="form-control" placeholder="e.g., Punong Barangay / Barangay Captain">
            </div>
            <div>
                <label class="form-label" for="committee">Committee (Optional)</label>
                <input type="text" id="committee" name="committee" value="{{ old('committee') }}" class="form-control" placeholder="e.g., Peace and Order">
            </div>
            <div>
                <label class="form-label" for="term_start">Term Start</label>
                <input type="date" id="term_start" name="term_start" value="{{ old('term_start') }}" class="form-control">
            </div>
            <div>
                <label class="form-label" for="term_end">Term End</label>
                <input type="date" id="term_end" name="term_end" value="{{ old('term_end') }}" class="form-control">
            </div>
            <div>
                <label class="form-label" for="user_id">Linked Staff Account (Optional)</label>
                <select id="user_id" name="user_id" class="form-control">
                    <option value="">— Not Linked —</option>
                    @foreach ($staffAccounts as $st)
                        <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="signature">E-Signature Image (PNG/JPG, max 2MB)</label>
                <input type="file" id="signature" name="signature" accept="image/png,image/jpeg" class="form-control">
            </div>
        </div>

        <div class="check-row">
            <label><input type="checkbox" name="is_active" value="1" checked> Active Official</label>
            <label><input type="checkbox" name="is_primary_signatory" value="1"> Set as Primary Certificate Signatory</label>
            <label><input type="checkbox" name="is_attest_signatory" value="1"> Set as Attesting Secretary</label>
        </div>

        <div style="margin-top: 16px; text-align: right;">
            <button type="submit" class="btn-winter">Save Official</button>
        </div>
    </form>
</section>

<section class="glass-card">
    <h2>📜 Barangay Officials Roster</h2>
    <div class="table-responsive">
        <table class="winter-table">
            <thead>
                <tr>
                    <th>Official Name &amp; Title</th>
                    <th>Position</th>
                    <th>Term</th>
                    <th>E-Signature</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($officials as $official)
                    <tr>
                        <td>
                            <strong>{{ $official->full_name }}</strong>
                            <div style="font-size: 12px; color: #486b82;">{{ $official->displayTitle() }}</div>
                            @if ($official->is_primary_signatory)
                                <span class="signatory-tag tag-primary">★ Primary Signatory</span>
                            @endif
                            @if ($official->is_attest_signatory)
                                <span class="signatory-tag tag-attest">✍ Attesting Secretary</span>
                            @endif
                        </td>
                        <td>
                            {{ $official->position }}
                            @if ($official->committee)
                                <div style="font-size: 11px; color: #647f91;">{{ $official->committee }}</div>
                            @endif
                        </td>
                        <td style="white-space: nowrap; font-size: 12px;">
                            {{ $official->term_start ? $official->term_start->format('M Y') : '—' }}
                            &ndash;
                            {{ $official->term_end ? $official->term_end->format('M Y') : 'Present' }}
                        </td>
                        <td>
                            @if ($official->signature_path)
                                <img src="{{ route('admin.officials.signature', $official->id) }}" alt="Signature" class="sig-thumb">
                            @else
                                <span style="font-size: 12px; color: #94a3b8;">None uploaded</span>
                            @endif
                        </td>
                        <td>
                            @if ($official->is_active)
                                <span class="status-pill pill-valid">✓ Active</span>
                            @else
                                <span class="status-pill pill-revoked">✕ Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                @php
                                    $officialPayload = [
                                        'id' => $official->id,
                                        'user_id' => $official->user_id,
                                        'full_name' => $official->full_name,
                                        'position' => $official->position,
                                        'designation_title' => $official->designation_title,
                                        'committee' => $official->committee,
                                        'term_start' => $official->term_start?->format('Y-m-d'),
                                        'term_end' => $official->term_end?->format('Y-m-d'),
                                        'is_primary_signatory' => $official->is_primary_signatory,
                                        'is_attest_signatory' => $official->is_attest_signatory,
                                        'is_active' => $official->is_active,
                                    ];
                                @endphp
                                <button type="button" class="btn-outline"
                                    data-official="{{ json_encode($officialPayload) }}"
                                    onclick="openEditOfficialModal(this)">
                                    ✏️ Edit
                                </button>

                                @if (!$official->is_primary_signatory)
                                    <form method="POST" action="{{ route('admin.officials.primary', $official->id) }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-outline" title="Use as primary signature on certificates">★ Set Primary</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.officials.attest', $official->id) }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-outline">
                                        {{ $official->is_attest_signatory ? 'Unset Attest' : '✍ Set Attest' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.officials.toggle', $official->id) }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-danger-winter" style="padding: 8px 11px;">
                                        {{ $official->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="editOfficialModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3 style="margin-top: 0; color: #174b70;">✏️ Edit Barangay Official</h3>
        <form id="editOfficialForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            <div class="official-grid">
                <div>
                    <label class="form-label" for="eo_full_name">Full Name</label>
                    <input type="text" id="eo_full_name" name="full_name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label" for="eo_position">Position</label>
                    <select id="eo_position" name="position" class="form-control" required>
                        @foreach (\App\Models\BarangayOfficial::POSITIONS as $pos)
                            <option value="{{ $pos }}">{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="eo_designation_title">Signatory Title</label>
                    <input type="text" id="eo_designation_title" name="designation_title" class="form-control">
                </div>
                <div>
                    <label class="form-label" for="eo_committee">Committee</label>
                    <input type="text" id="eo_committee" name="committee" class="form-control">
                </div>
                <div>
                    <label class="form-label" for="eo_term_start">Term Start</label>
                    <input type="date" id="eo_term_start" name="term_start" class="form-control">
                </div>
                <div>
                    <label class="form-label" for="eo_term_end">Term End</label>
                    <input type="date" id="eo_term_end" name="term_end" class="form-control">
                </div>
            </div>

            <label class="form-label" for="eo_user_id">Linked Staff Account</label>
            <select id="eo_user_id" name="user_id" class="form-control">
                <option value="">— Not Linked —</option>
                @foreach ($staffAccounts as $st)
                    <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->roleLabel() }})</option>
                @endforeach
            </select>

            <label class="form-label" for="eo_signature">Replace E-Signature (PNG/JPG, optional)</label>
            <input type="file" id="eo_signature" name="signature" accept="image/png,image/jpeg" class="form-control">

            <div class="check-row">
                <label><input type="checkbox" id="eo_is_active" name="is_active" value="1"> Active</label>
                <label><input type="checkbox" id="eo_is_primary" name="is_primary_signatory" value="1"> Primary Signatory</label>
                <label><input type="checkbox" id="eo_is_attest" name="is_attest_signatory" value="1"> Attesting Secretary</label>
                <label><input type="checkbox" id="eo_remove_sig" name="remove_signature" value="1"> Remove Signature</label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-outline" onclick="closeEditOfficialModal()">Cancel</button>
                <button type="submit" class="btn-winter">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditOfficialModal(btn) {
        const d = JSON.parse(btn.getAttribute('data-official'));
        document.getElementById('editOfficialForm').action = "{{ url('admin/officials') }}/" + d.id;
        document.getElementById('eo_full_name').value = d.full_name || '';
        document.getElementById('eo_position').value = d.position || 'Punong Barangay';
        document.getElementById('eo_designation_title').value = d.designation_title || '';
        document.getElementById('eo_committee').value = d.committee || '';
        document.getElementById('eo_term_start').value = d.term_start || '';
        document.getElementById('eo_term_end').value = d.term_end || '';
        document.getElementById('eo_user_id').value = d.user_id || '';
        document.getElementById('eo_is_active').checked = !!d.is_active;
        document.getElementById('eo_is_primary').checked = !!d.is_primary_signatory;
        document.getElementById('eo_is_attest').checked = !!d.is_attest_signatory;
        document.getElementById('eo_remove_sig').checked = false;
        document.getElementById('eo_signature').value = '';
        document.getElementById('editOfficialModal').classList.add('open');
    }
    function closeEditOfficialModal() {
        document.getElementById('editOfficialModal').classList.remove('open');
    }
    document.getElementById('editOfficialModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditOfficialModal();
    });
</script>
@endsection