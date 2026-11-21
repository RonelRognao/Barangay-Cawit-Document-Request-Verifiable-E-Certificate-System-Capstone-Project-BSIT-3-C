@extends('layouts.resident')

@section('title', 'My Notifications')

@push('styles')
<style>
    .notif-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d8edf8;
        border-left: 5px solid #2586a8;
        border-radius: 14px;
        padding: 18px 24px;
        margin-bottom: 20px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.08);
    }

    .notif-summary strong {
        font-size: 26px;
        color: #174b70;
        margin-right: 10px;
    }

    .notif-item {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d8edf8;
        border-left: 5px solid #b8d3e3;
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 14px;
        box-shadow: 0 4px 14px rgba(63, 133, 170, 0.07);
        transition: .2s;
    }

    .notif-item.unread {
        border-left-color: #25866b;
        background: linear-gradient(120deg, #ffffff, #f3fbff);
    }

    .notif-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .notif-status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: bold;
        background: #e5eff6;
        color: #486b82;
    }

    .notif-item.unread .notif-status {
        background: #dcfce7;
        color: #166534;
    }

    .notif-item h3 {
        margin: 0 0 8px;
        font-size: 17px;
        color: #174b70;
    }

    .notif-item p {
        margin: 0 0 12px;
        line-height: 1.6;
        color: #334e61;
        overflow-wrap: anywhere;
    }

    .notif-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .notif-footer small {
        color: #647f91;
    }

    .read-btn {
        padding: 8px 14px;
        border: none;
        border-radius: 8px;
        background: #25866b;
        color: white;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        transition: .2s;
    }

    .read-btn:hover {
        background: #1a6651;
    }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Updates & Alerts {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>My Notifications</h1>
        <p>Stay informed about your document request statuses and issued e-certificates.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">🔔</div>
</section>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<div class="notif-summary">
    <div>
        <strong>{{ $unread_count }}</strong>
        <span>Unread {{ $unread_count === 1 ? 'Notification' : 'Notifications' }}</span>
    </div>
    <a href="{{ route('resident.dashboard') }}" class="btn-outline">← Back to Dashboard</a>
</div>

@forelse ($notifications as $notification)
    <div class="notif-item {{ !$notification->is_read ? 'unread' : '' }}">
        <div class="notif-head">
            <span class="notif-status">
                {{ $notification->is_read ? 'READ' : '● UNREAD' }}
            </span>
        </div>

        <h3>{{ $notification->title }}</h3>
        <p>{!! nl2br(e($notification->message)) !!}</p>

        <div class="notif-footer">
            <small>
                🕒 Received: {{ \Carbon\Carbon::parse($notification->created_at)->format('F d, Y - h:i A') }}
            </small>

            @if (!$notification->is_read)
                <form method="POST" action="{{ route('resident.notifications.read', $notification->id) }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="read-btn">✓ Mark as Read</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="glass-card empty-state">
        <div class="empty-icon">🔔</div>
        <h3>No Notifications Yet</h3>
        <p>You will see updates about your document requests and e-certificates here.</p>
    </div>
@endforelse
@endsection