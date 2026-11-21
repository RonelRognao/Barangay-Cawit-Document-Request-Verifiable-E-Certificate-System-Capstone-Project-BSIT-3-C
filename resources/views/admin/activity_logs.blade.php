@extends('layouts.admin')

@section('title', 'Admin Activity Logs')

@push('styles')
<style>
    .action-badge {
        display: inline-block;
        background: #dff3ff;
        color: #174b70;
        border: 1px solid #bce4fa;
        padding: 5px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">System Monitoring {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Admin Activity Logs</h1>
        <p>Monitor administrative actions performed across document requests and e-certificates.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">📝</div>
</section>

<section class="mini-stats">
    <div class="mini-stat" style="border-left-color: #25866b; max-width: 300px;">
        <span>Total Activity Records</span>
        <strong>{{ $logs->count() }}</strong>
    </div>
</section>

<section class="glass-card">
    <h2>🕒 Activity History</h2>

    @if ($logs->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Administrator</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Date and Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td><strong>#{{ $log->id }}</strong></td>
                            <td>{{ $log->admin->full_name ?? 'Unknown Administrator' }}</td>
                            <td><span class="action-badge">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td style="white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No Activity Records Yet</h3>
            <p>Wala pang recorded administrator activities.</p>
        </div>
    @endif
</section>
@endsection