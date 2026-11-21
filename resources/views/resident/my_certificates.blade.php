@extends('layouts.resident')

@section('title', 'My E-Certificates')

@push('styles')
<style>
    .cert-no {
        font-weight: bold;
        color: #174b70;
    }

    .valid-pill {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        background: #dcfce7;
        color: #166534;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
    }

    .qr-box {
        display: inline-block;
        padding: 6px;
        background: white;
        border: 1px solid #d8edf8;
        border-radius: 10px;
        text-align: center;
    }

    .qr-box img {
        width: 82px;
        height: 82px;
        display: block;
    }

    .qr-label {
        font-size: 11px;
        color: #647f91;
        margin-top: 4px;
    }

    .verify-link {
        display: inline-block;
        padding: 8px 13px;
        border-radius: 8px;
        background: #e0f4ff;
        color: #174b70;
        font-size: 13px;
        font-weight: bold;
        text-decoration: none;
        transition: .2s;
        white-space: nowrap;
    }

    .verify-link:hover {
        background: #bae6fd;
    }

    .print-btn {
        display: inline-block;
        padding: 9px 14px;
        background: linear-gradient(120deg, #25866b, #15803d);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: bold;
        white-space: nowrap;
        transition: .2s;
    }

    .print-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(21, 128, 61, 0.22);
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Verified Digital Documents {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>My E-Certificates</h1>
        <p>Access, verify, and print your official QR-authenticated barangay certificates.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">🏅</div>
</section>

<div class="glass-card">
    @if ($certificates->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Certificate No.</th>
                        <th>Document</th>
                        <th>Reference No.</th>
                        <th>Date Issued</th>
                        <th>Status</th>
                        <th>QR Code</th>
                        <th>Verification</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $certificate)
                        @php
                            $verifyUrl = route('verify', ['token' => $certificate->verification_token]);
                            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($verifyUrl);
                        @endphp
                        <tr>
                            <td class="cert-no">{{ $certificate->certificate_no }}</td>
                            <td><strong>{{ $certificate->request->documentType->document_name }}</strong></td>
                            <td>{{ $certificate->request->reference_no }}</td>
                            <td>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('M d, Y') }}</td>
                            <td><span class="valid-pill">✓ Valid</span></td>
                            <td>
                                <div class="qr-box">
                                    <img src="{{ $qrUrl }}" alt="Certificate QR Code">
                                    <div class="qr-label">Scan to Verify</div>
                                </div>
                            </td>
                            <td>
                                <a class="verify-link" href="{{ $verifyUrl }}" target="_blank">🔍 Verify</a>
                            </td>
                            <td>
                                <a class="print-btn"
                                   href="{{ route('resident.certificates.print', $certificate->certificate_no) }}"
                                   target="_blank">
                                    🖨️ Print PDF
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">🏅</div>
            <h3>No Certificates Yet</h3>
            <p>Wala ka pang issued e-Certificate.</p>
            <p>Lalabas dito ang iyong certificate kapag na-issue na ng administrator.</p>
        </div>
    @endif
</div>
@endsection