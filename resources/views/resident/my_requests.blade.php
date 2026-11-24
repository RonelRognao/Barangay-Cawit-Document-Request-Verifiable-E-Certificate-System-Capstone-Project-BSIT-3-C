@extends('layouts.resident')

@section('title', 'My Document Requests')

@push('styles')
<style>
    .mini-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .mini-stat {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid #d8edf8;
        border-left: 5px solid #2586a8;
        border-radius: 13px;
        padding: 16px 18px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.08);
    }

    .mini-stat span {
        display: block;
        font-size: 12px;
        color: #647f91;
        margin-bottom: 6px;
        font-weight: bold;
    }

    .mini-stat strong {
        font-size: 24px;
        color: #174b70;
    }

    .ref-code {
        font-weight: bold;
        color: #174b70;
    }

    .status-pill {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap; 
    }

    .remarks-note {
        margin-top: 5px;
        font-size: 12px;
        color: #486b82;
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Application Tracking {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>My Document Requests</h1>
        <p>Track the progress, status, and barangay remarks for your submitted requests.</p>
    </div>
    <a href="{{ route('resident.request.create') }}" class="btn-winter">+ New Request</a>
</section>

<div class="mini-stats">
    <div class="mini-stat">
        <span>Total Requests</span>
        <strong>{{ $requests->count() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #eab308;">
        <span>Pending / Review</span>
        <strong>{{ $requests->whereIn('status', ['Pending', 'Under Review'])->count() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Approved</span>
        <strong>{{ $requests->where('status', 'Approved')->count() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #2563eb;">
        <span>Completed</span>
        <strong>{{ $requests->where('status', 'Completed')->count() }}</strong>
    </div>
</div>

<div class="glass-card">
    @if ($requests->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>Reference Number</th>
                        <th>Document</th>
                        <th>Purpose & Remarks</th>
                        <th>Date Requested</th>
                        <th>Fee &amp; O.R.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        @php
                            [$bg, $fg] = match ($request->status) {
                                'Approved' => ['#dcfce7', '#166534'],
                                'Rejected' => ['#fee2e2', '#991b1b'],
                                'Completed' => ['#dbeafe', '#1e40af'],
                                'Under Review' => ['#e0f2fe', '#0369a1'],
                                default => ['#fef3c7', '#92400e'],
                            };
                        @endphp
                        <tr>
                            <td class="ref-code">{{ $request->reference_no }}</td>
                            <td>
                                <strong>{{ $request->documentType->document_name }}</strong>
                                @if (!empty($request->document_metadata['business_name']))
                                    <div style="font-size: 11px; color: #486b82;">🏢 {{ $request->document_metadata['business_name'] }} ({{ $request->document_metadata['application_type'] ?? 'New' }})</div>
                                @endif
                            </td>
                            <td>
                                @if ($request->purpose_category)
                                    <span style="display: inline-block; padding: 2px 7px; border-radius: 8px; background: #dff3ff; color: #174b70; font-size: 11px; font-weight: bold; margin-bottom: 3px;">
                                        {{ $request->purpose_category }}
                                    </span><br>
                                @endif
                                {{ $request->purpose }}
                                @if ($request->attachment_path)
                                    <div style="margin-top: 4px; font-size: 12px;">
                                        <a href="{{ route('resident.requests.attachment', $request->id) }}" target="_blank" style="color: #174b70; font-weight: bold;">📎 View Uploaded Requirement</a>
                                    </div>
                                @endif
                                @if (!empty($request->admin_remarks))
                                    <div class="remarks-note">
                                        <strong>Admin Remarks:</strong> {{ $request->admin_remarks }}
                                    </div>
                                @endif
                            </td>
                            <td style="font-size: 12px; white-space: nowrap;">
                                <strong>{{ (float) $request->final_fee > 0 ? '₱' . number_format((float) $request->final_fee, 2) : 'Free' }}</strong>
                                <div style="color: {{ $request->payment_status === 'paid' ? '#166534' : ($request->payment_status === 'exempt' ? '#1e40af' : '#92400e') }}; font-weight: bold;">
                                    {{ strtoupper($request->payment_status) }}
                                    {{ $request->official_receipt_no ? '(O.R. #' . $request->official_receipt_no . ')' : '' }}
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($request->requested_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td>
                                <span class="status-pill" style="background: {{ $bg }}; color: {{ $fg }};">
                                    {{ $request->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No Document Requests Yet</h3>
            <p>Your submitted document requests will appear here once you submit one.</p>
            <a href="{{ route('resident.request.create') }}" class="btn-winter" style="margin-top: 12px;">
                Request Your First Document
            </a>
        </div>
    @endif
</div>
@endsection