@extends('layouts.admin')

@section('title', 'Issue E-Certificates')

@push('styles')
<style>
    .approved-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding: 18px 20px;
        background: #f7fbff;
        border: 1px solid #d8edf8;
        border-left: 5px solid #25866b;
        border-radius: 12px;
        margin-bottom: 14px;
    }

    .approved-item > div:first-child {
        flex: 1;
        min-width: 280px;
    }

    .approved-item h3 {
        margin: 0 0 8px;
        color: #174b70;
        font-size: 18px;
    }

    .approved-meta {
        font-size: 13px;
        color: #486b82;
        line-height: 1.7;
    }

    .no-action {
        color: #94a3b8;
        font-size: 13px;
        font-style: italic;
    }

    .hash-badge {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 8px;
        border-radius: 6px;
        background: #eef6ff;
        border: 1px solid #cde4f7;
        color: #174b70;
        font-family: monospace;
        font-size: 11px;
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

</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Certificate Issuance {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Issue and Manage E-Certificates</h1>
        <p>Generate QR-verifiable electronic certificates for approved requests or revoke invalid ones.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">📜</div>
</section>

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

<section class="mini-stats">
    <div class="mini-stat" style="border-left-color: #25866b;">
        <span>Ready to Issue</span>
        <strong>{{ $total_available }}</strong>
    </div>
    <div class="mini-stat">
        <span>Total Issued</span>
        <strong>{{ $total_issued }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Valid Certificates</span>
        <strong>{{ $total_valid }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #c9364b;">
        <span>Revoked Certificates</span>
        <strong>{{ $total_revoked }}</strong>
    </div>
</section>

<section class="glass-card">
    <h2>✨ Approved Requests Ready for Certificate</h2>

    @forelse ($approved_requests as $request)
        <div class="approved-item">
            <div>
                <h3>{{ $request->documentType->document_name }}</h3>
                <div class="approved-meta">
                    <strong>Resident:</strong> {{ $request->user->full_name }} &nbsp;|&nbsp;
                    <strong>Reference No.:</strong> {{ $request->reference_no }} &nbsp;|&nbsp;
                    <strong>Requested:</strong> {{ \Carbon\Carbon::parse($request->requested_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                    &nbsp;|&nbsp;
                    <strong>COA Payment:</strong>
                    @if ($request->isPaidOrExempt())
                        <span style="color: #166534; font-weight: bold;">
                            ✓ {{ strtoupper($request->payment_status) }}
                            {{ $request->official_receipt_no ? '(O.R. #' . $request->official_receipt_no . ')' : '' }}
                        </span>
                    @else
                        <span style="color: #991b1b; font-weight: bold;">⚠️ Unpaid (Record O.R. in Manage Requests first)</span>
                    @endif
                </div>
            </div>

            @if ($request->isPaidOrExempt())
                <form method="POST"
                      action="{{ route('admin.certificates.store', $request->id) }}"
                      onsubmit="return confirm('Are you sure you want to issue this certificate?');"
                      style="margin: 0;">
                    @csrf
                    <button class="btn-winter" type="submit">📜 Issue Certificate</button>
                </form>
            @else
                <a href="{{ route('admin.requests.index', ['search' => $request->reference_no]) }}"
                   class="btn-outline"
                   style="border-color: #f59e0b; color: #92400e; background: #fffbeb; white-space: nowrap;">
                    💳 Record O.R. First &rarr;
                </a>
            @endif
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-icon">📜</div>
            <h3>No Approved Requests Available</h3>
            <p>Wala pang approved request na maaaring bigyan ng bagong certificate.</p>
        </div>
    @endforelse
</section>

<section class="glass-card">
    <h2>🏅 Issued Certificates</h2>

    @if ($total_issued > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Certificate No.</th>
                        <th>Resident</th>
                        <th>Document</th>
                        <th>Reference No.</th>
                        <th>Issued By</th>
                        <th>Date Issued (PHT)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($issued_certificates as $certificate)
                        <tr>
                            <td>
                                <strong style="color: #174b70;">{{ $certificate->certificate_no }}</strong>
                                @if ($certificate->verification_hash)
                                    <br><span class="hash-badge">🔐 {{ strtoupper(substr($certificate->verification_hash, 0, 10)) }}</span>
                                @endif
                            </td>
                            <td>{{ $certificate->request->user->full_name }}</td>
                            <td>{{ $certificate->request->documentType->document_name }}</td>
                            <td>{{ $certificate->request->reference_no }}</td>
                            <td>{{ $certificate->issuer->full_name ?? 'Administrator' }}</td>
                            <td>{{ \Carbon\Carbon::parse($certificate->issued_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td>
                                @if ($certificate->revoked_at === null)
                                    <span class="status-pill pill-valid">✓ Valid</span>
                                @else
                                    <span class="status-pill pill-revoked">✕ Revoked</span>
                                    @if ($certificate->revocation_reason)
                                        <div style="font-size: 11px; color: #991b1b; margin-top: 4px;">
                                            {{ $certificate->revocation_reason }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($certificate->revoked_at === null)
                                    <div style="display: flex; gap: 6px; align-items: center; white-space: nowrap;">
                                        <a href="{{ route('admin.certificates.print', $certificate->certificate_no) }}"
                                           target="_blank"
                                           class="btn-outline"
                                           style="padding: 8px 12px;">
                                            🖨️ Print / PDF
                                        </a>
                                        <button type="button" class="btn-danger-winter" style="padding: 8px 12px;"
                                            onclick="openRevokeModal('{{ route('admin.certificates.revoke', $certificate->id) }}', '{{ $certificate->certificate_no }}')">
                                            Revoke
                                        </button>
                                    </div>
                                @else
                                    <span class="no-action">Already Revoked</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">🏅</div>
            <h3>No Certificates Issued Yet</h3>
            <p>Wala pang na-issue na e-Certificate.</p>
        </div>
    @endif
</section>

<div id="revokeModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="revokeModalTitle">
    <div class="modal-card">
        <h3 id="revokeModalTitle">⚠️ Revoke E-Certificate</h3>
        <p style="font-size: 13px; color: #486b82; line-height: 1.5; margin-top: 0;">
            You are about to revoke <strong id="revokeCertNo"></strong>. Once revoked, any QR verification scan will mark it as <strong>INVALID / REVOKED</strong>.
        </p>
        <form id="revokeForm" method="POST" action="">
            @csrf
            <label for="revocation_reason" class="form-label">Reason for Revocation (Optional, logged in audit trail)</label>
            <textarea id="revocation_reason" name="revocation_reason" rows="3" maxlength="255" class="form-control"
                placeholder="e.g., Clerical typo, resident blotter conflict, superseded issuance..."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-outline" onclick="closeRevokeModal()">Cancel</button>
                <button type="submit" class="btn-danger-winter">Confirm Revocation</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRevokeModal(actionUrl, certNo) {
        document.getElementById('revokeForm').action = actionUrl;
        document.getElementById('revokeCertNo').textContent = certNo;
        document.getElementById('revocation_reason').value = '';
        document.getElementById('revokeModal').classList.add('open');
    }
    function closeRevokeModal() {
        document.getElementById('revokeModal').classList.remove('open');
    }
    document.getElementById('revokeModal').addEventListener('click', function (e) {
        if (e.target === this) closeRevokeModal();
    });
</script>
@endsection