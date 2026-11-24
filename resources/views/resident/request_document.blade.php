@extends('layouts.resident')

@section('title', 'Request a Document')

@push('styles')
<style>
    .request-wrap {
        max-width: 720px;
        margin: 0 auto;
    }

    .doc-select-hint {
        margin-top: 8px;
        font-size: 12px;
        color: #647f91;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 24px;
    }

    .ref-box {
        display: inline-block;
        margin: 8px 0;
        padding: 8px 14px;
        background: white;
        border: 1px dashed #16a34a;
        border-radius: 8px;
        font-size: 16px;
        letter-spacing: 0.5px;
    }
    .dynamic-section {
        display: none;
        margin-top: 16px;
        padding: 16px 18px;
        border-radius: 12px;
        background: #f5fbff;
        border: 1px solid #cde4f7;
    }
    .dynamic-section.visible { display: block; }
    .dynamic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
    .ra-box { background: #ecfdf5; border-color: #a7f3d0; }
    .ra-blocked { background: #fef2f2; border-color: #fecaca; color: #991b1b; padding: 12px 14px; border-radius: 10px; font-size: 13px; margin-top: 10px; }
    @media (max-width: 600px) { .dynamic-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="request-wrap">
    <section class="page-banner">
        <div>
            <div class="eyebrow">Online Document Services {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
            <h1>Request a Barangay Document</h1>
            <p>Choose the document you need and describe its purpose below.</p>
        </div>
        <div class="banner-icon" aria-hidden="true">📄</div>
    </section>

    <div class="glass-card">
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        @if (session('error')) <div class="alert alert-error">{{ session('error') }}</div> @endif

        @if (session('success'))
            <div class="alert alert-success">
                <strong>{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</strong><br>
                <span>Your Reference Number:</span><br>
                <span class="ref-box">{{ session('reference_no') }}</span><br>
                <small>Please save this number or track it anytime under <strong>My Requests</strong>.</small>
            </div>
        @endif

        @if (!$kycVerified)
            <div class="alert alert-error" style="margin-bottom: 16px;">
                <strong>🔒 Resident KYC Verification Required</strong><br>
                @if ($resident->kyc_status === 'rejected')
                    Your verification was declined ({{ $resident->kyc_remarks ?: 'see profile' }}). Please update your Valid ID in <strong>My Profile</strong>.
                @else
                    Your account is currently <strong>Pending Verification</strong>. Barangay staff will verify your Valid ID and Purok residency before you can request documents.
                @endif
            </div>
            <div class="form-actions">
                <a href="{{ route('resident.dashboard') }}" class="btn-outline">← Back to Dashboard</a>
                <a href="{{ route('resident.profile') }}" class="btn-winter">Go to My Profile &amp; KYC</a>
            </div>
        @else
            <form method="POST" action="{{ route('resident.request.store') }}" enctype="multipart/form-data">
                @csrf

                <label for="document_type_id" class="form-label">Select Document *</label>
                <select name="document_type_id" id="document_type_id" class="form-control" required onchange="onDocumentTypeChange()">
                    <option value="">-- Choose a document --</option>
                    @foreach ($documents as $document)
                        @php
                            $docConfig = [
                                'id' => $document->id,
                                'name' => $document->document_name,
                                'description' => $document->description,
                                'fee' => (float) $document->fee,
                                'is_fee_exempt_default' => $document->is_fee_exempt_default,
                                'validity_days' => $document->validity_days,
                                'requires_business_details' => $document->requires_business_details,
                                'requires_supporting_doc' => $document->requires_supporting_doc,
                                'is_ra11261' => $document->is_ra11261,
                                'allowed_purposes' => $document->allowed_purposes ?? [],
                            ];
                        @endphp
                        <option value="{{ $document->id }}"
                                data-config="{{ json_encode($docConfig) }}"
                                {{ old('document_type_id') == $document->id ? 'selected' : '' }}>
                            {{ $document->document_name }}
                            @if ($document->fee > 0)
                                (₱{{ number_format($document->fee, 2) }})
                            @else
                                (Free)
                            @endif
                        </option>
                    @endforeach
                </select>

                <div id="docInfoBox" class="doc-select-hint" style="padding: 10px 12px; background: #f8fcff; border: 1px solid #d8edf8; border-radius: 9px; display: none;"></div>

                <label for="purpose_category" class="form-label">Standard Purpose Category *</label>
                <select name="purpose_category" id="purpose_category" class="form-control" required>
                    <option value="">-- Select a document first --</option>
                </select>

                <label for="purpose" class="form-label">Specific Purpose Details * <small style="font-weight: normal; color: #647f91;">(min. 10 characters)</small></label>
                <textarea
                    name="purpose"
                    id="purpose"
                    rows="3"
                    minlength="10"
                    maxlength="2000"
                    class="form-control"
                    placeholder="Specify exact recipient or purpose (e.g., Pre-employment requirement for ABC Company, Legazpi City)"
                    required
                >{{ old('purpose') }}</textarea>

                @if ($resident->is_senior_citizen || $resident->is_pwd)
                    <div id="exemptionBox" class="dynamic-section">
                        <label for="fee_exemption_claim" class="form-label" style="margin-top: 0;">Fee Waiver / Exemption Claim (RBI Tagged)</label>
                        <select name="fee_exemption_claim" id="fee_exemption_claim" class="form-control">
                            <option value="none" {{ old('fee_exemption_claim') === 'none' ? 'selected' : '' }}>Standard Fee (No Waiver)</option>
                            @if ($resident->is_senior_citizen)
                                <option value="senior_citizen" {{ old('fee_exemption_claim') === 'senior_citizen' ? 'selected' : '' }}>Senior Citizen Fee Waiver (RA 9994)</option>
                            @endif
                            @if ($resident->is_pwd)
                                <option value="pwd" {{ old('fee_exemption_claim') === 'pwd' ? 'selected' : '' }}>PWD Fee Waiver (RA 10754)</option>
                            @endif
                        </select>
                    </div>
                @endif

                <div id="businessFieldsBox" class="dynamic-section">
                    <strong style="color: #174b70;">🏢 Business Clearance Details</strong>
                    <div class="dynamic-grid">
                        <div>
                            <label class="form-label" for="business_name">Business / Trade Name *</label>
                            <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" class="form-control" placeholder="e.g., Dela Cruz Sari-Sari Store">
                        </div>
                        <div>
                            <label class="form-label" for="application_type">Application Type *</label>
                            <select id="application_type" name="application_type" class="form-control">
                                <option value="New" {{ old('application_type') === 'New' ? 'selected' : '' }}>New Business</option>
                                <option value="Renewal" {{ old('application_type') === 'Renewal' ? 'selected' : '' }}>Renewal</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="nature_of_business">Nature / Line of Business *</label>
                            <input type="text" id="nature_of_business" name="nature_of_business" value="{{ old('nature_of_business') }}" class="form-control" placeholder="e.g., Retail / Sari-Sari Store">
                        </div>
                        <div>
                            <label class="form-label" for="dti_sec_no">DTI / SEC Registration No.</label>
                            <input type="text" id="dti_sec_no" name="dti_sec_no" value="{{ old('dti_sec_no') }}" class="form-control" placeholder="Optional / If registered">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label" for="business_address">Business Address in Barangay Cawit *</label>
                            <input type="text" id="business_address" name="business_address" value="{{ old('business_address') }}" class="form-control" placeholder="Purok / Sitio, Barangay Cawit, Manito, Albay">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label" for="ctc_no">Community Tax Certificate (Cedula) No.</label>
                            <input type="text" id="ctc_no" name="ctc_no" value="{{ old('ctc_no') }}" class="form-control" placeholder="CTC / Cedula number (if already issued)">
                        </div>
                    </div>
                </div>

                <div id="ra11261Box" class="dynamic-section ra-box">
                    <strong style="color: #166534;">⚖️ RA 11261 — First-Time Jobseekers Assistance Act</strong>
                    @if ($ra11261Check['eligible'])
                        <p style="font-size: 13px; color: #166534; margin: 8px 0;">
                            ✓ Eligible! Verified residency: <strong>{{ $resident->residencyDurationLabel() }}</strong> (6+ months required). One-time lifetime fee waiver available.
                        </p>
                        <label class="form-label" for="school_or_qualification">School / Highest Educational Attainment (Optional)</label>
                        <input type="text" id="school_or_qualification" name="school_or_qualification" value="{{ old('school_or_qualification') }}" class="form-control" placeholder="e.g., BS Information Technology / Senior High School Graduate">

                        <label style="display: flex; align-items: start; gap: 8px; margin-top: 12px; font-size: 13px; color: #14532d; cursor: pointer;">
                            <input type="checkbox" id="oath_of_undertaking" name="oath_of_undertaking" value="1" {{ old('oath_of_undertaking') ? 'checked' : '' }} style="margin-top: 3px;">
                            <span><strong>Oath of Undertaking:</strong> I attest that I am a first-time jobseeker, have resided in Barangay Cawit for at least six (6) months, and have not previously availed of the RA 11261 fee waiver.</span>
                        </label>
                    @else
                        <div class="ra-blocked">
                            ✕ <strong>Not Eligible for RA 11261:</strong> {{ $ra11261Check['reason'] }}
                        </div>
                    @endif
                </div>

                <div id="attachmentBox" class="dynamic-section">
                    <label class="form-label" id="attachmentLabel" style="margin-top: 0;">Supporting Document Upload (JPG/PNG/PDF, max 2MB)</label>
                    <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                    <small style="color: #647f91; font-size: 12px;">Stored securely in private barangay storage for verification only.</small>
                </div>

                <div class="form-actions">
                    <a href="{{ route('resident.dashboard') }}" class="btn-outline">← Back to Dashboard</a>
                    <button type="submit" id="submitRequestBtn" class="btn-winter">{{ $activeTheme === 'christmas' ? '🎁 Submit Document Request' : 'Submit Document Request' }}</button>
                </div>
            </form>

            <script>
                const oldPurposeCategory = {{ \Illuminate\Support\Js::from(old('purpose_category', '')) }};
                const raEligible = {{ \Illuminate\Support\Js::from($ra11261Check['eligible']) }};

                function onDocumentTypeChange() {
                    const select = document.getElementById('document_type_id');
                    const selected = select.options[select.selectedIndex];
                    const rawConfig = selected ? selected.getAttribute('data-config') : null;

                    const infoBox = document.getElementById('docInfoBox');
                    const purposeSel = document.getElementById('purpose_category');
                    const bizBox = document.getElementById('businessFieldsBox');
                    const raBox = document.getElementById('ra11261Box');
                    const attBox = document.getElementById('attachmentBox');
                    const attInput = document.getElementById('attachment');
                    const attLabel = document.getElementById('attachmentLabel');
                    const exBox = document.getElementById('exemptionBox');
                    const submitBtn = document.getElementById('submitRequestBtn');

                    purposeSel.innerHTML = '';
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';

                    if (!rawConfig) {
                        infoBox.style.display = 'none';
                        bizBox.classList.remove('visible');
                        raBox.classList.remove('visible');
                        attBox.classList.remove('visible');
                        if (exBox) exBox.classList.remove('visible');
                        purposeSel.innerHTML = '<option value="">-- Select a document first --</option>';
                        return;
                    }

                    const cfg = JSON.parse(rawConfig);
                    const feeText = cfg.fee > 0 ? ('₱' + cfg.fee.toFixed(2)) : 'Free / Fee Exempt';
                    infoBox.style.display = 'block';
                    infoBox.innerHTML = (cfg.description ? cfg.description + ' &middot; ' : '') +
                        '<strong>Fee:</strong> ' + feeText + ' &middot; <strong>Validity:</strong> ' + cfg.validity_days + ' days';

                    const purposes = (cfg.allowed_purposes && cfg.allowed_purposes.length > 0)
                        ? cfg.allowed_purposes
                        : ['Official Personal Requirement', 'Employment / School Requirement', 'Government Transaction'];

                    purposes.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p;
                        opt.textContent = p;
                        if (oldPurposeCategory === p) opt.selected = true;
                        purposeSel.appendChild(opt);
                    });

                    // Toggle Business Clearance fields
                    bizBox.classList.toggle('visible', !!cfg.requires_business_details);
                    ['business_name', 'business_address', 'nature_of_business', 'application_type'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.required = !!cfg.requires_business_details;
                    });

                    // Toggle RA 11261 section
                    raBox.classList.toggle('visible', !!cfg.is_ra11261);
                    const oathEl = document.getElementById('oath_of_undertaking');
                    if (oathEl) oathEl.required = !!cfg.is_ra11261;
                    if (cfg.is_ra11261 && !raEligible) {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.5';
                    }

                    // Toggle Supporting Attachment
                    attBox.classList.add('visible');
                    attInput.required = !!cfg.requires_supporting_doc;
                    attLabel.textContent = cfg.requires_supporting_doc
                        ? 'Supporting Document Upload * (Required: DTI/SEC, Cedula, or ID — max 2MB)'
                        : 'Supporting Document Upload (Optional: Endorsement / Supporting Letter — max 2MB)';

                    // Toggle Senior/PWD waiver option
                    if (exBox) {
                        const showWaiver = cfg.fee > 0 && !cfg.is_fee_exempt_default && !cfg.is_ra11261;
                        exBox.classList.toggle('visible', showWaiver);
                    }
                }

                document.addEventListener('DOMContentLoaded', onDocumentTypeChange);
            </script>
        @endif
    </div>
</div>
@endsection