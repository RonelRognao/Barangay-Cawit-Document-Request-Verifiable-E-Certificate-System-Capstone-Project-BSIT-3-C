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
                    <strong>Requested:</strong> {{ \Carbon\Carbon::parse($request->requested_at)->format('M d, Y h:i A') }}
                </div>
            </div>

            <form method="POST"
                  action="{{ route('admin.certificates.store', $request->id) }}"
                  onsubmit="return confirm('Are you sure you want to issue this certificate?');"
                  style="margin: 0;">
                @csrf
                <button class="btn-winter" type="submit">📜 Issue Certificate</button>
            </form>
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
                        <th>Date Issued</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($issued_certificates as $certificate)
                        <tr>
                            <td><strong style="color: #174b70;">{{ $certificate->certificate_no }}</strong></td>
                            <td>{{ $certificate->request->user->full_name }}</td>
                            <td>{{ $certificate->request->documentType->document_name }}</td>
                            <td>{{ $certificate->request->reference_no }}</td>
                            <td>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('M d, Y h:i A') }}</td>
                            <td>
                                @if ($certificate->revoked_at === null)
                                    <span class="status-pill pill-valid">✓ Valid</span>
                                @else
                                    <span class="status-pill pill-revoked">✕ Revoked</span>
                                @endif
                            </td>
                            <td>
                                @if ($certificate->revoked_at === null)
                                    <form method="POST"
                                          action="{{ route('admin.certificates.revoke', $certificate->id) }}"
                                          onsubmit="return confirm('WARNING: Are you sure you want to REVOKE this certificate? It will become INVALID and cannot be verified as authentic.');"
                                          style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-danger-winter">Revoke Certificate</button>
                                    </form>
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
@endsection