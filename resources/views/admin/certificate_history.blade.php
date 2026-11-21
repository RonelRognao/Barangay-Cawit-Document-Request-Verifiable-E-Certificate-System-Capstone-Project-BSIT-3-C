@extends('layouts.admin')

@section('title', 'Certificate History')

@push('styles')
<style>
    .verify-link {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 8px;
        background: #e0f4ff;
        color: #174b70;
        font-size: 12px;
        font-weight: bold;
        text-decoration: none;
        transition: .2s;
        white-space: nowrap;
    }

    .verify-link:hover {
        background: #bae6fd;
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Audit & Records {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Certificate History</h1>
        <p>View and monitor all electronic certificates issued by Barangay Cawit, including valid and revoked records.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">📚</div>
</section>

<div class="mini-stats">
    <div class="mini-stat">
        <span>Total Certificates</span>
        <strong>{{ $totalCertificates }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Valid Certificates</span>
        <strong>{{ $validCertificates }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #c9364b;">
        <span>Revoked Certificates</span>
        <strong>{{ $revokedCertificates }}</strong>
    </div>
</div>

<div class="glass-card">
    <h2>📋 All Issued Certificates</h2>

    @if ($totalCertificates > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Certificate No.</th>
                        <th>Resident</th>
                        <th>Document</th>
                        <th>Reference No.</th>
                        <th>Date Issued</th>
                        <th>Date Revoked</th>
                        <th>Status</th>
                        <th>Verification</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $certificate)
                        @php
                            $isRevoked = $certificate->revoked_at !== null;
                            $verifyUrl = route('verify', ['token' => $certificate->verification_token]);
                        @endphp
                        <tr>
                            <td><strong style="color: #174b70;">{{ $certificate->certificate_no }}</strong></td>
                            <td>{{ $certificate->request->user->full_name }}</td>
                            <td>{{ $certificate->request->documentType->document_name }}</td>
                            <td>{{ $certificate->request->reference_no }}</td>
                            <td>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('M d, Y h:i A') }}</td>
                            <td>{{ $isRevoked ? \Carbon\Carbon::parse($certificate->revoked_at)->format('M d, Y h:i A') : '—' }}</td>
                            <td>
                                @if ($isRevoked)
                                    <span class="status-pill pill-revoked">✕ Revoked</span>
                                @else
                                    <span class="status-pill pill-valid">✓ Valid</span>
                                @endif
                            </td>
                            <td>
                                <a class="verify-link" href="{{ $verifyUrl }}" target="_blank">🔍 Verify</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3>No Certificate Records Found</h3>
            <p>Wala pang naitalang electronic certificates.</p>
        </div>
    @endif
</div>
@endsection