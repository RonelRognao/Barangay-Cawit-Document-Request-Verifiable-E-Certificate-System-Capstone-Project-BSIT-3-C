@extends('layouts.admin')

@section('title', 'Manage Document Requests')

@push('styles')
<style>
    .check-banner {
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        padding: 11px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 14px;
    }
    .check-clear { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .check-alert { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .override-box {
        margin-top: 10px; padding: 10px 12px; background: #fff7ed;
        border: 1px solid #fed7aa; border-radius: 8px; font-size: 12px; color: #9a3412;
    }

    .filter-bar 
    {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d8edf8;
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 22px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.08);
    }

    .filter-bar input {
        flex: 1;
        min-width: 220px;
    }

    .filter-bar select {
        width: 200px;
    }
    .request-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #d8edf8;
        border-left: 5px solid #2586a8;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 5px 18px rgba(63, 133, 170, 0.09);
    }

    .request-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding-bottom: 14px;
        border-bottom: 1px solid #e6f2f8;
        margin-bottom: 16px;
    }

    .request-card-head h2 {
        margin: 0;
        color: #174b70;
        font-size: 20px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .detail-box {
        background: #f5fbff;
        border: 1px solid #e1f1fa;
        padding: 12px 14px;
        border-radius: 10px;
        overflow-wrap: break-word;
    }

    .detail-box strong {
        display: block;
        color: #647f91;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 5px;
    }

    .detail-box span {
        font-size: 14px;
        color: #1e293b;
    }

    .update-panel {
        background: #f8fcff;
        border: 1px solid #d8edf8;
        border-radius: 12px;
        padding: 16px 18px;
    }

    .update-grid {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 16px;
        align-items: start;
    }

    @media (max-width: 720px) {
        .update-grid {
            grid-template-columns: 1fr;
        }
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
        max-width: 480px;
        background: #ffffff;
        border: 1px solid #d8edf8;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 18px 40px rgba(18, 59, 93, 0.25);
    }
    .modal-card h3 { margin: 0 0 8px; color: #991b1b; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }

    .finance-panel {
        background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 12px;
        padding: 14px 18px; margin-bottom: 14px;
    }
    .finance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; align-items: end; }
    .meta-box {
        background: #fefce8; border: 1px solid #fde047; border-radius: 10px;
        padding: 12px 14px; margin-bottom: 14px; font-size: 13px; color: #713f12;
    }

</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Request Processing {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Manage Document Requests</h1>
        <p>Review resident applications, update statuses, and send official remarks.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">📋</div>
</section>

<div class="mini-stats">
    <div class="mini-stat">
        <span>Total Requests</span>
        <strong>{{ $counts['total'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #eab308;">
        <span>Pending</span>
        <strong>{{ $counts['pending'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #0284c7;">
        <span>Under Review</span>
        <strong>{{ $counts['under_review'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Approved</span>
        <strong>{{ $counts['approved'] }}</strong>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('admin.requests.index') }}" class="filter-bar">
    <input
        type="text"
        name="search"
        value="{{ $search ?? '' }}"
        class="form-control"
        placeholder="Search by Reference No. or Resident Name..."
    >

    <select name="status" class="form-control">
        <option value="All">All Statuses</option>
        @foreach (['Pending', 'Under Review', 'Approved', 'Rejected', 'Completed'] as $st)
            <option value="{{ $st }}" {{ ($statusFilter ?? '') === $st ? 'selected' : '' }}>
                {{ $st }}
            </option>
        @endforeach
    </select>

    <button type="submit" class="btn-winter">🔍 Filter</button>

    @if (auth()->user()->canProcessRequests())
        <a href="{{ route('admin.walk_in.create') }}" class="btn-outline" style="margin-left: auto;">🚶 + Encode Walk-In</a>
    @endif

    @if (($search ?? '') !== '' || (($statusFilter ?? '') !== '' && ($statusFilter ?? '') !== 'All'))
        <a href="{{ route('admin.requests.index') }}" class="btn-outline">Reset</a>
    @endif
</form>

@forelse ($requests as $request)
    @php
        [$bg, $fg] = match ($request->status) {
            'Approved' => ['#dcfce7', '#166534'],
            'Rejected' => ['#fee2e2', '#991b1b'],
            'Completed' => ['#dbeafe', '#1e40af'],
            'Under Review' => ['#e0f2fe', '#0369a1'],
            default => ['#fef3c7', '#92400e'],
        };
    @endphp

    <div class="request-card">
        <div class="request-card-head">
            <h2>
                📄 {{ $request->documentType->document_name }}
                @if ($request->is_walk_in)
                    <span style="font-size: 11px; padding: 3px 8px; border-radius: 10px; background: #ede9fe; color: #5b21b6; margin-left: 6px;">🚶 Walk-In</span>
                @endif
            </h2>
            <span class="status-pill" style="background: {{ $bg }}; color: {{ $fg }};">
                {{ $request->status }}
            </span>
        </div>

    @php
            $resCheck = $derogatoryByUser[$request->user_id] ?? ['has_derogatory' => false, 'active_cases' => collect(), 'settled_cases' => collect()];
            $kycVerified = $request->user->isKycVerified();
        @endphp

        <div class="check-banner {{ $resCheck['has_derogatory'] ? 'check-alert' : 'check-clear' }}">
            <div>
                <strong>KYC / RBI:</strong>
                {{ $kycVerified ? '✓ Verified' : '⚠️ ' . ucfirst($request->user->kyc_status) }}
                &middot; <strong>Purok:</strong> {{ $request->user->purok->name ?? 'Unassigned' }}
                &middot; <strong>Residency:</strong> {{ $request->user->residencyDurationLabel() }}
            </div>
            <div>
                @if ($resCheck['has_derogatory'])
                    <strong>⚠️ ACTIVE LUPON / BLOTTER HIT ({{ $resCheck['active_cases']->count() }}):</strong>
                    @foreach ($resCheck['active_cases'] as $hit)
                        <span>{{ $hit->case_no }} ({{ $hit->status }})</span>@if(!$loop->last), @endif
                    @endforeach
                    &nbsp;<a href="{{ route('admin.blotter.index', ['search' => $request->user->full_name]) }}" style="color: #991b1b; font-weight: bold;">Inspect &rarr;</a>
                @else
                    <strong>✓ Lupon / Blotter Clear</strong>
                    @if ($resCheck['settled_cases']->count() > 0)
                        <span style="font-size: 11px;">({{ $resCheck['settled_cases']->count() }} settled case(s))</span>
                    @endif
                @endif
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-box">
                <strong>Reference Number</strong>
                <span>{{ $request->reference_no }}</span>
            </div>
            <div class="detail-box">
                <strong>Resident Name</strong>
                <span>{{ $request->user->full_name }}</span>
            </div>
            <div class="detail-box">
                <strong>Email Address</strong>
                <span>{{ $request->user->email }}</span>
            </div>
            <div class="detail-box">
                <strong>Contact Number</strong>
                <span>{{ $request->user->contact_number ?? 'Not provided' }}</span>
            </div>
            <div class="detail-box">
                <strong>Complete Address</strong>
                <span>{{ $request->user->address ?? 'Not provided' }}</span>
            </div>
            <div class="detail-box">
                <strong>Date Requested</strong>
                <span>{{ \Carbon\Carbon::parse($request->requested_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</span>
            </div>
            <div class="detail-box" style="grid-column: 1 / -1;">
                <strong>Purpose of Request</strong>
                @if ($request->purpose_category)
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; background: #dff3ff; color: #174b70; font-size: 12px; font-weight: bold; margin-bottom: 4px;">
                        {{ $request->purpose_category }}
                    </span><br>
                @endif
                <span>{{ $request->purpose }}</span>
            </div>
        </div>

        @if (!empty($request->document_metadata) || $request->attachment_path)
            <div class="meta-box">
                <strong>📎 Document-Specific Details &amp; Supporting Requirements:</strong>
                <div style="margin-top: 6px; line-height: 1.7;">
                    @if (!empty($request->document_metadata['business_name']))
                        <strong>Business Name:</strong> {{ $request->document_metadata['business_name'] }}
                        ({{ $request->document_metadata['application_type'] ?? 'New' }}) &middot;
                        <strong>Nature:</strong> {{ $request->document_metadata['nature_of_business'] ?? '—' }}<br>
                        <strong>Business Address:</strong> {{ $request->document_metadata['business_address'] ?? '—' }} &middot;
                        <strong>DTI/SEC No.:</strong> {{ $request->document_metadata['dti_sec_no'] ?? '—' }} &middot;
                        <strong>Cedula/CTC:</strong> {{ $request->document_metadata['ctc_no'] ?? '—' }}<br>
                    @endif
                    @if (!empty($request->document_metadata['oath_of_undertaking_accepted']))
                        <strong>RA 11261 First-Time Jobseeker:</strong> ✓ Oath of Undertaking Accepted &middot;
                        <strong>Verified Residency:</strong> {{ $request->document_metadata['residency_months_at_request'] ?? '—' }} month(s)
                        @if (!empty($request->document_metadata['school_or_qualification']))
                            &middot; <strong>School/Course:</strong> {{ $request->document_metadata['school_or_qualification'] }}
                        @endif
                        <br>
                    @endif
                    @if ($request->attachment_path)
                        <a href="{{ route('admin.requests.attachment', $request->id) }}" target="_blank" style="color: #174b70; font-weight: bold;">
                            📄 Inspect Uploaded {{ $request->attachment_label ?: 'Attachment' }} ↗
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="finance-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                <strong style="color: #0f766e;">💳 COA Fee &amp; Official Receipt (O.R.) Status</strong>
                <span class="status-pill" style="background: {{ $request->payment_status === 'paid' ? '#dcfce7' : ($request->payment_status === 'exempt' ? '#dbeafe' : '#fef3c7') }}; color: {{ $request->payment_status === 'paid' ? '#166534' : ($request->payment_status === 'exempt' ? '#1e40af' : '#92400e') }};">
                    {{ strtoupper($request->payment_status) }}
                    @if ($request->payment_status === 'paid' && $request->official_receipt_no)
                        &middot; O.R. #{{ $request->official_receipt_no }}
                    @elseif ($request->payment_status === 'exempt')
                        &middot; {{ \App\Services\FeeAndMandateService::EXEMPTION_LABELS[$request->fee_exemption_type] ?? 'Exempt' }}
                    @endif
                </span>
            </div>

            <div style="font-size: 12px; color: #134e4a; margin-bottom: 10px;">
                Assessed: <strong>₱{{ number_format((float) $request->assessed_fee, 2) }}</strong> &middot;
                Payable: <strong>₱{{ number_format((float) $request->final_fee, 2) }}</strong>
                @if ($request->paymentVerifier)
                    &middot; Verified by: <strong>{{ $request->paymentVerifier->full_name }}</strong>
                @endif
            </div>

            @if (auth()->user()->canVerifyPayments())
                <form method="POST" action="{{ route('admin.requests.payment', $request->id) }}" class="finance-grid">
                    @csrf
                    <div>
                        <label class="form-label" style="margin-top: 0;">Payment Status</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="unpaid" {{ $request->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ $request->payment_status === 'paid' ? 'selected' : '' }}>Paid (With O.R.)</option>
                            <option value="exempt" {{ $request->payment_status === 'exempt' ? 'selected' : '' }}>Fee Exempt / Waived</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="margin-top: 0;">Exemption Tag</label>
                        <select name="fee_exemption_type" class="form-control" required>
                            @foreach (\App\Services\FeeAndMandateService::EXEMPTION_LABELS as $key => $label)
                                <option value="{{ $key }}" {{ $request->fee_exemption_type === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="margin-top: 0;">Official Receipt (O.R.) No.</label>
                        <input type="text" name="official_receipt_no" value="{{ $request->official_receipt_no }}" class="form-control" placeholder="e.g., OR-2026-00123">
                    </div>
                    <div>
                        <label class="form-label" style="margin-top: 0;">O.R. Date</label>
                        <input type="date" name="official_receipt_date" value="{{ $request->official_receipt_date?->format('Y-m-d') ?? now('Asia/Manila')->format('Y-m-d') }}" class="form-control">
                    </div>
                    <div>
                        <button type="submit" class="btn-winter" style="width: 100%;">💳 Record O.R.</button>
                    </div>
                </form>
            @endif
        </div>

        @if (auth()->user()->canProcessRequests())
          @php
              $allowedStatuses = auth()->user()->canApproveRequests()
                  ? ['Pending', 'Under Review', 'Approved', 'Rejected', 'Completed']
                  : ['Pending', 'Under Review'];
          @endphp
          <form method="POST" action="{{ route('admin.requests.update', $request->id) }}" class="update-panel">
              @csrf
              <div class="update-grid">
                  <div>
                      <label class="form-label" style="margin-top: 0;">Update Status</label>
                      <select name="status" class="form-control" required>
                          @if (!in_array($request->status, $allowedStatuses, true))
                              <option value="{{ $request->status }}" selected disabled>{{ $request->status }} (Locked for Clerk)</option>
                          @endif
                          @foreach ($allowedStatuses as $status)
                              <option value="{{ $status }}" {{ $request->status === $status ? 'selected' : '' }}>
                                  {{ $status }}
                              </option>
                          @endforeach
                      </select>
                  </div>

                  <div>
                      <label class="form-label" style="margin-top: 0;">Admin Remarks</label>
                      <textarea name="admin_remarks" rows="2" class="form-control"
                                placeholder="Enter remarks or reason for decision...">{{ $request->admin_remarks ?? '' }}</textarea>
                  </div>
              </div>

              @if ($resCheck['has_derogatory'] && auth()->user()->canApproveRequests())
                    <div class="override-box">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold;">
                            <input type="checkbox" name="derogatory_override" value="1">
                            Derogatory Override: Resident has an active Lupon/Blotter case. Check this box and write justification in Admin Remarks if approving anyway.
                        </label>
                    </div>
                @endif

              <div style="margin-top: 14px; text-align: right;">
                  <button type="submit" class="btn-winter">💾 Save Changes</button>
              </div>
          </form>
        @else
            <div class="update-panel" style="color: #486b82; font-size: 13px;">
                👁️ <strong>Read-Only View ({{ auth()->user()->roleLabel() }}):</strong>
                Status remarks: <em>{{ $request->admin_remarks ?: 'None' }}</em>
            </div>
        @endif
    </div>
@empty
    <div class="glass-card empty-state">
        <div class="empty-icon">📋</div>
        <h3>No Document Requests Yet</h3>
        <p>Wala pang document requests mula sa mga resident.</p>
    </div>
@endforelse
@endsection