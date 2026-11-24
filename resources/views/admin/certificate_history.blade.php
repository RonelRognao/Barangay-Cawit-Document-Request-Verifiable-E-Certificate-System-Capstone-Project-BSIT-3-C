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

    .privacy-banner {
         display: flex;
         align-items: center;
         justify-content: space-between;
         flex-wrap: wrap;
         gap: 8px;
         background: #eff6ff;
         border: 1px solid #bfdbfe;
         color: #1e3a8a;
         padding: 10px 14px;
         border-radius: 10px;
         font-size: 12px;
         margin-bottom: 18px;
     }
     .reset-link { display: inline-block; margin-top: 18px; color: #123b68; font-size: 13px; font-weight: bold; text-decoration: none; }
     .reset-link:hover { text-decoration: underline; }
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
    <div class="mini-stat" style="border-left-color: #eab308;">
        <span>Expired Certificates</span>
        <strong>{{ $expiredCertificates }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #c9364b;">
        <span>Revoked Certificates</span>
        <strong>{{ $revokedCertificates }}</strong>
    </div>
</div>

<form method="GET" action="{{ route('admin.certificates.history') }}" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; background: rgba(255,255,255,0.95); border: 1px solid #d8edf8; border-radius: 14px; padding: 14px 18px; margin-bottom: 20px;">
    <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" style="flex: 1; min-width: 220px;" placeholder="Search Certificate No., Reference No., or Resident...">
    <select name="status" class="form-control" style="width: 190px;">
        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
        <option value="valid" {{ ($statusFilter ?? '') === 'valid' ? 'selected' : '' }}>Valid Only</option>
        <option value="expired" {{ ($statusFilter ?? '') === 'expired' ? 'selected' : '' }}>Expired Only</option>
        <option value="revoked" {{ ($statusFilter ?? '') === 'revoked' ? 'selected' : '' }}>Revoked Only</option>
    </select>
    <button type="submit" class="btn-winter">🔍 Filter</button>
    <a href="{{ route('admin.certificates.history') }}" class="btn-outline">Reset</a>
</form>

<div class="glass-card">
    <h2>📋 All Issued Certificates</h2>

        @if ($certificates->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Certificate No.</th>
                        <th>Resident</th>
                        <th>Document</th>
                        <th>Reference No.</th>
                        <th>Date Issued (PHT)</th>
                        <th>Valid Until</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $certificate)
                        @php
                            $isRevoked = $certificate->revoked_at !== null;
                            $isExpired = !$isRevoked && $certificate->isExpired();
                            $verifyUrl = route('verify', ['token' => $certificate->verification_token]);
                        @endphp
                        <tr>
                            <td><strong style="color: #174b70;">{{ $certificate->certificate_no }}</strong></td>
                            <td>{{ $certificate->request->user->full_name }}</td>
                            <td>{{ $certificate->request->documentType->document_name }}</td>
                            <td>{{ $certificate->request->reference_no }}</td>
                            <td>{{ \Carbon\Carbon::parse($certificate->issued_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td>
                                @if ($isRevoked)
                                    <span style="color: #991b1b; font-size: 12px;">Revoked {{ \Carbon\Carbon::parse($certificate->revoked_at)->timezone('Asia/Manila')->format('M d, Y') }}</span>
                                @elseif ($certificate->expires_at)
                                    {{ \Carbon\Carbon::parse($certificate->expires_at)->timezone('Asia/Manila')->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($isRevoked)
                                    <span class="status-pill pill-revoked">✕ Revoked</span>
                                @elseif ($isExpired)
                                    <span class="status-pill" style="background: #fef3c7; color: #92400e;">⌛ Expired</span>
                                @else
                                    <span class="status-pill pill-valid">✓ Valid</span>
                                @endif
                            </td>
                            <td style="white-space: nowrap;">
                                <a class="verify-link" href="{{ $verifyUrl }}" target="_blank">🔍 Verify</a>
                                @if (!$isRevoked)
                                    <a class="verify-link" style="background: #dcfce7; color: #166534; margin-left: 4px;"
                                       href="{{ route('admin.certificates.print', $certificate->certificate_no) }}" target="_blank">🖨️ Print / PDF</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $certificates->links() }}</div>
    @else
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3>No Certificate Records Found</h3>
            <p>Wala pang naitalang electronic certificates.</p>
        </div>
    @endif
</div>
@endsection