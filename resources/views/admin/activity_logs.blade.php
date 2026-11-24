@extends('layouts.admin')

@section('title', 'Admin Activity Logs')

@push('styles')
<style>
    .filter-bar {
        display: grid;
        grid-template-columns: 2fr 1.2fr 1.2fr 1fr 1fr auto;
        gap: 10px;
        align-items: end;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d8edf8;
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 22px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.08);
    }
    .filter-group label {
        display: block;
        font-size: 11px;
        font-weight: bold;
        color: #486b82;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .filter-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .action-badge {
        display: inline-block;
        background: #dff3ff;
        color: #174b70;
        border: 1px solid #bce4fa;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
    }
    .category-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .cat-request { background: #e0f2fe; color: #0369a1; }
    .cat-certificate { background: #dcfce7; color: #166534; }
    .cat-security { background: #fef3c7; color: #92400e; }
    .cat-ui_preference { background: #f1f5f9; color: #64748b; }
    .cat-operational { background: #ede9fe; color: #5b21b6; }
    .cat-officials { background: #fce7f3; color: #9d174d; }
    .diff-box {
        margin-top: 6px;
        padding: 6px 10px;
        background: #f8fcff;
        border: 1px solid #d8edf8;
        border-radius: 8px;
        font-size: 12px;
        color: #315b75;
    }
    .ip-chip {
        font-family: monospace;
        font-size: 12px;
        background: #f1f5f9;
        color: #334155;
        padding: 3px 7px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .card-header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
    .card-header-flex h2 { margin: 0; }
    .pagination-wrap { margin-top: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 13px; color: #486b82; }
    .pagination-wrap nav svg { width: 16px; height: 16px; }
    @media (max-width: 980px) { .filter-bar { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 600px) { .filter-bar { grid-template-columns: 1fr; } }

    .cat-residents { background: #cffafe; color: #155e75; }
    .cat-blotter { background: #fee2e2; color: #991b1b; }
    .cat-finance { background: #ccfbf1; color: #115e59; } 
    .cat-document_types { background: #fef3c7; color: #92400e; }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">System Monitoring & Audit Trail {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Admin Activity Logs</h1>
        <p>Audit staff actions, before/after changes, and IP metadata across requests and e-certificates (RA 10173).</p>
    </div>
    <div class="banner-icon" aria-hidden="true">📝</div>
</section>

<section class="mini-stats">
    <div class="mini-stat" style="border-left-color: #25866b;">
        <span>Operational Audit Records</span>
        <strong>{{ $operationalCount }}</strong>
    </div>
    <div class="mini-stat">
        <span>Showing Matching Results</span>
        <strong>{{ $logs->total() }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #64748b;">
        <span>Total Stored (Incl. UI Events)</span>
        <strong>{{ $totalCount }}</strong>
    </div>
</section>

<form method="GET" action="{{ route('admin.activity_logs') }}" class="filter-bar">
    <div class="filter-group">
        <label for="search">Search Actor / Details / IP</label>
        <input type="text" id="search" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Ref no., resident, staff name, IP...">
    </div>

    <div class="filter-group">
        <label for="category">Log Category</label>
        <select id="category" name="category" class="form-control">
            <option value="operational_only" {{ $filters['category'] === 'operational_only' ? 'selected' : '' }}>Operational Only (Hide Theme Noise)</option>
            <option value="all" {{ $filters['category'] === 'all' ? 'selected' : '' }}>All Categories</option>
            <option value="request" {{ $filters['category'] === 'request' ? 'selected' : '' }}>Document Requests</option>
            <option value="certificate" {{ $filters['category'] === 'certificate' ? 'selected' : '' }}>E-Certificates</option>
            <option value="security" {{ $filters['category'] === 'security' ? 'selected' : '' }}>Security & Exports</option>
            <option value="officials" {{ $filters['category'] === 'officials' ? 'selected' : '' }}>Barangay Officials</option>
            <option value="residents" {{ $filters['category'] === 'residents' ? 'selected' : '' }}>Residents &amp; KYC</option>
            <option value="blotter" {{ $filters['category'] === 'blotter' ? 'selected' : '' }}>Blotter / Lupon</option>
            <option value="ui_preference" {{ $filters['category'] === 'ui_preference' ? 'selected' : '' }}>UI / Theme Events</option>
            <option value="finance" {{ $filters['category'] === 'finance' ? 'selected' : '' }}>Payments &amp; O.R.</option>
            <option value="document_types" {{ $filters['category'] === 'document_types' ? 'selected' : '' }}>Document Types</option>
        </select>
    </div>

    <div class="filter-group">
        <label for="action">Action</label>
        <select id="action" name="action" class="form-control">
            <option value="">All Actions</option>
            @foreach ($availableActions as $act)
                <option value="{{ $act }}" {{ $filters['action'] === $act ? 'selected' : '' }}>{{ $act }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label for="date_from">From</label>
        <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
    </div>

    <div class="filter-group">
        <label for="date_to">To</label>
        <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
    </div>

    <div class="filter-actions">
        <button type="submit" class="btn-winter">Filter</button>
        <a href="{{ route('admin.activity_logs') }}" class="btn-outline">Reset</a>
    </div>
</form>

<section class="glass-card">
    <div class="card-header-flex">
        <h2>🕒 Audit Trail History</h2>
        <a href="{{ route('admin.activity_logs.export', request()->query()) }}" class="btn-outline">📥 Export CSV</a>
    </div>

    @if ($logs->count() > 0)
        <div class="table-responsive">
            <table class="winter-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Staff / Actor</th>
                        <th>Category</th>
                        <th>Action</th>
                        <th>Details & Changes</th>
                        <th>IP Address</th>
                        <th>Date & Time (PHT)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        @php
                            $cat = $log->log_category ?? 'operational';
                            $actor = $log->actor_name ?? $log->admin->full_name ?? 'System';
                            $actorRole = $log->actor_role ?? $log->admin->role ?? 'admin';
                        @endphp
                        <tr>
                            <td><strong>#{{ $log->id }}</strong></td>
                            <td>
                                <strong>{{ $actor }}</strong>
                                <div style="font-size: 11px; color: #647f91; text-transform: uppercase;">{{ $actorRole }}</div>
                            </td>
                            <td><span class="category-pill cat-{{ $cat }}">{{ str_replace('_', ' ', $cat) }}</span></td>
                            <td><span class="action-badge">{{ $log->action }}</span></td>
                            <td>
                                <div>{{ $log->description }}</div>
                                @if (!empty($log->old_values) || !empty($log->new_values))
                                    <div class="diff-box">
                                        @if (!empty($log->old_values))
                                            <strong>Before:</strong>
                                            @foreach ($log->old_values as $k => $v)
                                                <span>{{ $k }}: <em>{{ is_scalar($v) ? ($v === '' ? '—' : $v) : json_encode($v) }}</em></span>@if(!$loop->last), @endif
                                            @endforeach
                                        @endif
                                        @if (!empty($log->old_values) && !empty($log->new_values)) &rarr; @endif
                                        @if (!empty($log->new_values))
                                            <strong>After:</strong>
                                            @foreach ($log->new_values as $k => $v)
                                                <span>{{ $k }}: <em>{{ is_scalar($v) ? ($v === '' ? '—' : $v) : json_encode($v) }}</em></span>@if(!$loop->last), @endif
                                            @endforeach
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td><span class="ip-chip">{{ $log->ip_address ?: '—' }}</span></td>
                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    @else
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No Matching Activity Records</h3>
            <p>Try clearing your filters or changing the selected log category.</p>
        </div>
    @endif
</section>
@endsection