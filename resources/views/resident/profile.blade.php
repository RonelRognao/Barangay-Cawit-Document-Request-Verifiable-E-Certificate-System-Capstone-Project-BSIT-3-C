@extends('layouts.resident')

@section('title', 'My Profile')

@push('styles')
<style>
    .profile-wrap {
        max-width: 780px;
        margin: 0 auto;
    }

    .profile-top {
        display: flex;
        align-items: center;
        gap: 18px;
        padding-bottom: 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e2f1f8;
    }

    .profile-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(120deg, #2586a8, #174b70);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: bold;
        flex-shrink: 0;
        border: 3px solid #d8edf8;
    }

    .profile-top h2 {
        margin: 0 0 5px;
        color: #174b70;
        font-size: 20px;
    }

    .role-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 15px;
        background: #dff3ff;
        color: #174b70;
        font-size: 12px;
        font-weight: bold;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 18px;
    }

    .full-span {
        grid-column: 1 / -1;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 24px;
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="profile-wrap">
    <section class="page-banner">
        <div>
            <div class="eyebrow">Account Settings {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
            <h1>My Resident Profile</h1>
            <p>Keep your contact details and address up to date for faster document processing.</p>
        </div>
        <div class="banner-icon" aria-hidden="true">👤</div>
    </section>

    <div class="glass-card">
        <div class="profile-top">
            <div class="profile-avatar">
                {{ strtoupper(substr($user->full_name, 0, 1)) }}
            </div>
            <div>
                <h2>{{ $user->full_name }}</h2>
                <span class="role-badge">Resident Account • {{ ucfirst($user->account_status) }}</span>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('resident.profile.update') }}">
            @csrf

            <div class="form-grid">
                <div>
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control"
                           value="{{ old('full_name', $user->full_name) }}" required>
                </div>

                <div>
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="full-span">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control"
                           value="{{ old('contact_number', $user->contact_number) }}"
                           placeholder="09XX XXX XXXX">
                </div>

                <div class="full-span">
                    <label class="form-label">Complete Address</label>
                    <textarea name="address" rows="3" class="form-control"
                              placeholder="Purok, Barangay Cawit, Manito, Albay">{{ old('address', $user->address) }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('resident.dashboard') }}" class="btn-outline">← Back to Dashboard</a>
                <button type="submit" class="btn-winter">💾 Save Profile Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection