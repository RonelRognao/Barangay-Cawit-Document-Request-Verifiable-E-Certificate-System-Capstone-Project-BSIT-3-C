@extends('layouts.admin')

@section('title', 'Document Types & Fee Schedule')

@push('styles')
<style>
    .doc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .flag-row {
        display: flex; flex-wrap: wrap; gap: 16px; margin-top: 14px; padding: 12px 14px;
        background: #f5fbff; border: 1px solid #d8edf8; border-radius: 10px;
        font-size: 13px; color: #174b70; font-weight: bold;
    }
    .flag-row label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .spec-pill {
        display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px;
        font-weight: bold; background: #e0f2fe; color: #0369a1; margin: 2px 3px 2px 0;
    }
    .spec-ra { background: #dcfce7; color: #166534; }
    .spec-biz { background: #fef3c7; color: #92400e; }
    .modal-backdrop {
        display: none; position: fixed; inset: 0; background: rgba(18, 59, 93, 0.52);
        z-index: 10000; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-backdrop.open { display: flex; }
    .modal-card {
        width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto;
        background: #ffffff; border: 1px solid #d8edf8; border-radius: 16px;
        padding: 24px; box-shadow: 0 18px 40px rgba(18, 59, 93, 0.25);
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Document &amp; COA Fee Configuration {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Document Types &amp; Fee Schedule</h1>
        <p>Configure official barangay fees, exemption rules (RA 11261 / Indigency), validity days, and standardized purposes.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">📄</div>
</section>

@if (session('success'))
    <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<section class="mini-stats">
    <div class="mini-stat"><span>Total Document Types</span><strong>{{ $counts['total'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #16a34a;"><span>Active Types</span><strong>{{ $counts['active'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #25866b;"><span>Free / Fee-Exempt Defaults</span><strong>{{ $counts['free_or_exempt'] }}</strong></div>
</section>

<section class="glass-card">
    <h2>➕ Add New Document Type</h2>
    <form method="POST" action="{{ route('admin.document_types.store') }}">
        @csrf
        <div class="doc-grid">
            <div>
                <label class="form-label">Document Name *</label>
                <input type="text" name="document_name" value="{{ old('document_name') }}" class="form-control" required placeholder="e.g., Barangay Solo Parent Certification">
            </div>
            <div>
                <label class="form-label">System Code (Optional)</label>
                <input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="Auto-generated if blank">
            </div>
            <div>
                <label class="form-label">Standard Fee (PHP) *</label>
                <input type="number" step="0.01" min="0" name="fee" value="{{ old('fee', '50.00') }}" class="form-control" required>
            </div>
            <div>
                <label class="form-label">Validity Period (Days) *</label>
                <input type="number" min="1" max="3650" name="validity_days" value="{{ old('validity_days', 180) }}" class="form-control" required>
            </div>
        </div>

        <label class="form-label">Description</label>
        <input type="text" name="description" value="{{ old('description') }}" class="form-control" placeholder="Short explanation shown to residents">

        <label class="form-label">Allowed Standardized Purposes (One per line)</label>
        <textarea name="allowed_purposes_text" rows="3" class="form-control" placeholder="Local Employment&#10;Scholarship Application&#10;Government ID Requirement">{{ old('allowed_purposes_text') }}</textarea>

        <div class="flag-row">
            <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <label><input type="checkbox" name="is_fee_exempt_default" value="1"> Fee-Exempt by Default</label>
            <label><input type="checkbox" name="requires_business_details" value="1"> Requires Business Details</label>
            <label><input type="checkbox" name="requires_supporting_doc" value="1"> Requires Attachment Upload</label>
            <label><input type="checkbox" name="is_ra11261" value="1"> RA 11261 (First-Time Jobseeker)</label>
        </div>

        <div style="margin-top: 16px; text-align: right;">
            <button type="submit" class="btn-winter">Save Document Type</button>
        </div>
    </form>
</section>

<section class="glass-card">
    <h2>📑 Configured Document Types</h2>
    <div class="table-responsive">
        <table class="winter-table">
            <thead>
                <tr>
                    <th>Document Name &amp; Rules</th>
                    <th>Fee</th>
                    <th>Validity</th>
                    <th>Allowed Purposes</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documentTypes as $doc)
                    @php
                        $purposesText = is_array($doc->allowed_purposes) ? implode("\n", $doc->allowed_purposes) : '';
                        $docPayload = [
                            'id' => $doc->id,
                            'document_name' => $doc->document_name,
                            'code' => $doc->code,
                            'description' => $doc->description,
                            'fee' => (string) $doc->fee,
                            'validity_days' => $doc->validity_days,
                            'is_fee_exempt_default' => $doc->is_fee_exempt_default,
                            'requires_business_details' => $doc->requires_business_details,
                            'requires_supporting_doc' => $doc->requires_supporting_doc,
                            'is_ra11261' => $doc->is_ra11261,
                            'allowed_purposes_text' => $purposesText,
                            'is_active' => $doc->is_active,
                        ];
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $doc->document_name }}</strong>
                            <div style="font-size: 11px; color: #647f91;">{{ $doc->code ?: '—' }}</div>
                            <div style="margin-top: 4px;">
                                @if ($doc->is_ra11261)<span class="spec-pill spec-ra">RA 11261</span>@endif
                                @if ($doc->is_fee_exempt_default)<span class="spec-pill spec-ra">Exempt Default</span>@endif
                                @if ($doc->requires_business_details)<span class="spec-pill spec-biz">Business Fields</span>@endif
                                @if ($doc->requires_supporting_doc)<span class="spec-pill spec-biz">Attachment Required</span>@endif
                            </div>
                        </td>
                        <td><strong>{{ (float) $doc->fee > 0 ? '₱' . number_format((float) $doc->fee, 2) : 'Free' }}</strong></td>
                        <td>{{ $doc->validity_days }} days</td>
                        <td style="font-size: 12px; color: #486b82;">
                            @if (!empty($doc->allowed_purposes))
                                {{ count($doc->allowed_purposes) }} standardized option(s)
                            @else
                                Any specific purpose
                            @endif
                        </td>
                        <td>
                            @if ($doc->is_active)
                                <span class="status-pill pill-valid">✓ Active</span>
                            @else
                                <span class="status-pill pill-revoked">✕ Inactive</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            <button type="button" class="btn-outline" data-doc="{{ json_encode($docPayload) }}" onclick="openDocModal(this)">✏️ Edit</button>
                            <form method="POST" action="{{ route('admin.document_types.toggle', $doc->id) }}" style="display: inline-block; margin: 0 0 0 6px;">
                                @csrf
                                <button type="submit" class="btn-danger-winter" style="padding: 8px 11px;">
                                    {{ $doc->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="editDocModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3 style="margin-top: 0; color: #174b70;">✏️ Edit Document Type</h3>
        <form id="editDocForm" method="POST" action="">
            @csrf
            <div class="doc-grid">
                <div>
                    <label class="form-label">Document Name *</label>
                    <input type="text" id="ed_name" name="document_name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">System Code</label>
                    <input type="text" id="ed_code" name="code" class="form-control">
                </div>
                <div>
                    <label class="form-label">Fee (PHP) *</label>
                    <input type="number" step="0.01" min="0" id="ed_fee" name="fee" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Validity (Days) *</label>
                    <input type="number" min="1" max="3650" id="ed_validity" name="validity_days" class="form-control" required>
                </div>
            </div>

            <label class="form-label">Description</label>
            <input type="text" id="ed_desc" name="description" class="form-control">

            <label class="form-label">Allowed Standardized Purposes (One per line)</label>
            <textarea id="ed_purposes" name="allowed_purposes_text" rows="4" class="form-control"></textarea>

            <div class="flag-row">
                <label><input type="checkbox" id="ed_active" name="is_active" value="1"> Active</label>
                <label><input type="checkbox" id="ed_exempt" name="is_fee_exempt_default" value="1"> Fee-Exempt Default</label>
                <label><input type="checkbox" id="ed_biz" name="requires_business_details" value="1"> Business Fields</label>
                <label><input type="checkbox" id="ed_doc" name="requires_supporting_doc" value="1"> Attachment Required</label>
                <label><input type="checkbox" id="ed_ra" name="is_ra11261" value="1"> RA 11261</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                <button type="button" class="btn-outline" onclick="closeDocModal()">Cancel</button>
                <button type="submit" class="btn-winter">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDocModal(btn) {
        const d = JSON.parse(btn.getAttribute('data-doc'));
        document.getElementById('editDocForm').action = "{{ url('admin/document-types') }}/" + d.id;
        document.getElementById('ed_name').value = d.document_name || '';
        document.getElementById('ed_code').value = d.code || '';
        document.getElementById('ed_fee').value = d.fee || '0.00';
        document.getElementById('ed_validity').value = d.validity_days || 180;
        document.getElementById('ed_desc').value = d.description || '';
        document.getElementById('ed_purposes').value = d.allowed_purposes_text || '';
        document.getElementById('ed_active').checked = !!d.is_active;
        document.getElementById('ed_exempt').checked = !!d.is_fee_exempt_default;
        document.getElementById('ed_biz').checked = !!d.requires_business_details;
        document.getElementById('ed_doc').checked = !!d.requires_supporting_doc;
        document.getElementById('ed_ra').checked = !!d.is_ra11261;
        document.getElementById('editDocModal').classList.add('open');
    }
    function closeDocModal() { document.getElementById('editDocModal').classList.remove('open'); }
    document.getElementById('editDocModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeDocModal(); });
</script>
@endsection