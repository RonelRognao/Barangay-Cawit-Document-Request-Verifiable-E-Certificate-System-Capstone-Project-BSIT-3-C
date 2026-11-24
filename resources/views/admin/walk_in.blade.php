@extends('layouts.admin')

@section('title', 'Walk-In Request Intake')

@push('styles')
<style>
    .wi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .mode-switch { display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 14px; background: #f5fbff; border: 1px solid #d8edf8; border-radius: 10px; margin-bottom: 14px; font-size: 13px; font-weight: bold; color: #174b70; }
    .mode-switch label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .section-box { padding: 16px; border: 1px solid #d8edf8; border-radius: 12px; background: #f9fcff; margin-top: 14px; }
    .hidden-box { display: none; }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Barangay Hall Front-Desk {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Walk-In Document Request Intake</h1>
        <p>Encode applications on behalf of walk-in, senior, or non-smartphone residents and optionally record O.R. payment on the spot.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">🚶</div>
</section>

@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<section class="glass-card">
    <form method="POST" action="{{ route('admin.walk_in.store') }}">
        @csrf

        <h2>1. Resident Selection</h2>
        <div class="mode-switch">
            <label>
                <input type="radio" name="resident_mode" value="existing" {{ old('resident_mode', 'existing') === 'existing' ? 'checked' : '' }} onchange="toggleResidentMode()">
                Select Existing Verified RBI Resident
            </label>
            <label>
                <input type="radio" name="resident_mode" value="new_walkin" {{ old('resident_mode') === 'new_walkin' ? 'checked' : '' }} onchange="toggleResidentMode()">
                Register New Walk-In Resident (Counter-Verified)
            </label>
        </div>

        <div id="existingResidentBox">
            <label class="form-label" for="user_id">Verified Resident *</label>
            <select id="user_id" name="user_id" class="form-control">
                <option value="">-- Select Registered Resident --</option>
                @foreach ($residents as $res)
                    <option value="{{ $res->id }}" {{ old('user_id') == $res->id ? 'selected' : '' }}>
                        {{ $res->full_name }} ({{ $res->purok->name ?? 'Unassigned' }}) &mdash; {{ $res->email }}
                    </option>
                @endforeach
            </select>
        </div>

        <div id="newWalkinBox" class="section-box hidden-box">
            <div class="wi-grid">
                <div>
                    <label class="form-label" for="walkin_full_name">Full Name *</label>
                    <input type="text" id="walkin_full_name" name="walkin_full_name" value="{{ old('walkin_full_name') }}" class="form-control" placeholder="Juan Dela Cruz">
                </div>
                <div>
                    <label class="form-label" for="walkin_purok_id">Purok / Sitio *</label>
                    <select id="walkin_purok_id" name="walkin_purok_id" class="form-control">
                        <option value="">-- Select Purok --</option>
                        @foreach ($puroks as $purok)
                            <option value="{{ $purok->id }}" {{ old('walkin_purok_id') == $purok->id ? 'selected' : '' }}>{{ $purok->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="walkin_contact_number">Contact Number</label>
                    <input type="text" id="walkin_contact_number" name="walkin_contact_number" value="{{ old('walkin_contact_number') }}" class="form-control" placeholder="09XX XXX XXXX">
                </div>
                <div>
                    <label class="form-label" for="walkin_residency_since">Residing Since (Optional)</label>
                    <input type="date" id="walkin_residency_since" name="walkin_residency_since" value="{{ old('walkin_residency_since') }}" class="form-control">
                </div>
            </div>
            <label class="form-label" for="walkin_address">Complete Address *</label>
            <input type="text" id="walkin_address" name="walkin_address" value="{{ old('walkin_address') }}" class="form-control" placeholder="Purok, Barangay Cawit, Manito, Albay">
        </div>

        <h2 style="margin-top: 24px;">2. Document &amp; Purpose</h2>
        <div class="wi-grid">
            <div>
                <label class="form-label" for="document_type_id">Document Type *</label>
                <select id="document_type_id" name="document_type_id" class="form-control" required onchange="onWiDocChange()">
                    <option value="">-- Select Document --</option>
                    @foreach ($documents as $doc)
                        @php
                            $cfg = [
                                'fee' => (float) $doc->fee,
                                'requires_business_details' => $doc->requires_business_details,
                                'is_fee_exempt_default' => $doc->is_fee_exempt_default,
                                'is_ra11261' => $doc->is_ra11261,
                                'allowed_purposes' => $doc->allowed_purposes ?? [],
                            ];
                        @endphp
                        <option value="{{ $doc->id }}" data-config="{{ json_encode($cfg) }}" {{ old('document_type_id') == $doc->id ? 'selected' : '' }}>
                            {{ $doc->document_name }} ({{ (float) $doc->fee > 0 ? '₱' . number_format((float) $doc->fee, 2) : 'Free' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="purpose_category">Standard Purpose Category *</label>
                <select id="purpose_category" name="purpose_category" class="form-control" required></select>
            </div>
            <div>
                <label class="form-label" for="initial_status">Initial Status *</label>
                <select id="initial_status" name="initial_status" class="form-control" required>
                    <option value="Pending" {{ old('initial_status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Under Review" {{ old('initial_status') === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                    @if (auth()->user()->canApproveRequests())
                        <option value="Approved" {{ old('initial_status') === 'Approved' ? 'selected' : '' }}>Approved (Ready to Issue)</option>
                    @endif
                </select>
            </div>
        </div>

        <label class="form-label" for="purpose">Specific Purpose Details * (min. 10 chars)</label>
        <textarea id="purpose" name="purpose" rows="2" minlength="10" class="form-control" required placeholder="Specific employment, medical, scholarship, or business transaction...">{{ old('purpose') }}</textarea>

        <div id="wiBusinessBox" class="section-box hidden-box">
            <strong style="color: #174b70;">🏢 Business Clearance Details</strong>
            <div class="wi-grid">
                <div>
                    <label class="form-label">Business Name *</label>
                    <input type="text" id="wi_biz_name" name="business_name" value="{{ old('business_name') }}" class="form-control">
                </div>
                <div>
                    <label class="form-label">Application Type *</label>
                    <select id="wi_biz_type" name="application_type" class="form-control">
                        <option value="New">New</option>
                        <option value="Renewal">Renewal</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Nature of Business *</label>
                    <input type="text" id="wi_biz_nature" name="nature_of_business" value="{{ old('nature_of_business') }}" class="form-control">
                </div>
                <div>
                    <label class="form-label">DTI / SEC No.</label>
                    <input type="text" name="dti_sec_no" value="{{ old('dti_sec_no') }}" class="form-control">
                </div>
                <div>
                    <label class="form-label">Business Address *</label>
                    <input type="text" id="wi_biz_addr" name="business_address" value="{{ old('business_address') }}" class="form-control">
                </div>
                <div>
                    <label class="form-label">Cedula (CTC) No.</label>
                    <input type="text" name="ctc_no" value="{{ old('ctc_no') }}" class="form-control">
                </div>
            </div>
        </div>

        <h2 style="margin-top: 24px;">3. Counter Payment / O.R. (Optional)</h2>
        <div class="wi-grid">
            <div>
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-control" required>
                    <option value="unpaid" {{ old('payment_status', 'unpaid') === 'unpaid' ? 'selected' : '' }}>Unpaid (Pay Later)</option>
                    @if (auth()->user()->canVerifyPayments())
                        <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid (With O.R.)</option>
                        <option value="exempt" {{ old('payment_status') === 'exempt' ? 'selected' : '' }}>Fee Exempt / Waived</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="form-label">Exemption Tag</label>
                <select name="fee_exemption_type" class="form-control" required>
                    @foreach (\App\Services\FeeAndMandateService::EXEMPTION_LABELS as $k => $lbl)
                        <option value="{{ $k }}" {{ old('fee_exemption_type', 'none') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">O.R. Number (if Paid)</label>
                <input type="text" name="official_receipt_no" value="{{ old('official_receipt_no') }}" class="form-control" placeholder="OR-2026-00001">
            </div>
            <div>
                <label class="form-label">O.R. Date (if Paid)</label>
                <input type="date" name="official_receipt_date" value="{{ old('official_receipt_date', now('Asia/Manila')->format('Y-m-d')) }}" class="form-control">
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 22px;">
            <a href="{{ route('admin.requests.index') }}" class="btn-outline">← Back to Requests</a>
            <button type="submit" class="btn-winter">Save Walk-In Request</button>
        </div>
    </form>
</section>

<script>
    const oldWiPurpose = {{ \Illuminate\Support\Js::from(old('purpose_category', '')) }};

    function toggleResidentMode() {
        const mode = document.querySelector('input[name="resident_mode"]:checked')?.value || 'existing';
        const isNew = (mode === 'new_walkin');
        document.getElementById('existingResidentBox').classList.toggle('hidden-box', isNew);
        document.getElementById('newWalkinBox').classList.toggle('hidden-box', !isNew);
        document.getElementById('user_id').required = !isNew;
        ['walkin_full_name', 'walkin_purok_id', 'walkin_address'].forEach(id => {
            document.getElementById(id).required = isNew;
        });
    }

    function onWiDocChange() {
        const sel = document.getElementById('document_type_id');
        const raw = sel.options[sel.selectedIndex]?.getAttribute('data-config');
        const purposeSel = document.getElementById('purpose_category');
        const bizBox = document.getElementById('wiBusinessBox');
        purposeSel.innerHTML = '';

        if (!raw) {
            bizBox.classList.add('hidden-box');
            purposeSel.innerHTML = '<option value="">-- Select document first --</option>';
            return;
        }
        const cfg = JSON.parse(raw);
        const purposes = (cfg.allowed_purposes && cfg.allowed_purposes.length)
            ? cfg.allowed_purposes
            : ['Official Personal Requirement', 'Employment / School Requirement'];
        purposes.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p;
            opt.textContent = p;
            if (oldWiPurpose === p) opt.selected = true;
            purposeSel.appendChild(opt);
        });

        bizBox.classList.toggle('hidden-box', !cfg.requires_business_details);
        ['wi_biz_name', 'wi_biz_type', 'wi_biz_nature', 'wi_biz_addr'].forEach(id => {
            document.getElementById(id).required = !!cfg.requires_business_details;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleResidentMode();
        onWiDocChange();
    });
</script>
@endsection