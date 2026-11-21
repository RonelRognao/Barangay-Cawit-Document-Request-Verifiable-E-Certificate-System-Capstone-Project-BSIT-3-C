@extends('layouts.admin')

@section('title', 'Manage Document Requests')

@push('styles')
<style>
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
        <strong>{{ $requests->count() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #eab308;">
        <span>Pending</span>
        <strong>{{ $requests->where('status', 'Pending')->count() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #0284c7;">
        <span>Under Review</span>
        <strong>{{ $requests->where('status', 'Under Review')->count() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Approved</span>
        <strong>{{ $requests->where('status', 'Approved')->count() }}</strong>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

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
            <h2>📄 {{ $request->documentType->document_name }}</h2>
            <span class="status-pill" style="background: {{ $bg }}; color: {{ $fg }};">
                {{ $request->status }}
            </span>
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
                <span>{{ \Carbon\Carbon::parse($request->requested_at)->format('M d, Y h:i A') }}</span>
            </div>
            <div class="detail-box" style="grid-column: 1 / -1;">
                <strong>Purpose of Request</strong>
                <span>{{ $request->purpose }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.requests.update', $request->id) }}" class="update-panel">
            @csrf
            <div class="update-grid">
                <div>
                    <label class="form-label" style="margin-top: 0;">Update Status</label>
                    <select name="status" class="form-control" required>
                        @foreach (['Pending', 'Under Review', 'Approved', 'Rejected', 'Completed'] as $status)
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

            <div style="margin-top: 14px; text-align: right;">
                <button type="submit" class="btn-winter">💾 Save Changes</button>
            </div>
        </form>
    </div>
@empty
    <div class="glass-card empty-state">
        <div class="empty-icon">📋</div>
        <h3>No Document Requests Yet</h3>
        <p>Wala pang document requests mula sa mga resident.</p>
    </div>
@endforelse
@endsection