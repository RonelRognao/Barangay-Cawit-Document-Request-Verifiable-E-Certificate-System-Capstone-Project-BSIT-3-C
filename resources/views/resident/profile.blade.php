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
                <span class="role-badge" style="margin-left: 6px; background: {{ $user->isKycVerified() ? '#dcfce7' : ($user->kyc_status === 'rejected' ? '#fee2e2' : '#fef3c7') }}; color: {{ $user->isKycVerified() ? '#166534' : ($user->kyc_status === 'rejected' ? '#991b1b' : '#92400e') }};">
                    KYC: {{ ucfirst($user->kyc_status) }}
                </span>
            </div>
        </div>

        @if ($user->kyc_status === 'pending')
            <div class="alert" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                ⏳ <strong>Verification Pending:</strong> Barangay Staff is reviewing your RBI details and Valid ID. Document requests will unlock once verified.
            </div>
        @elseif ($user->kyc_status === 'rejected')
            <div class="alert alert-error">
                ✕ <strong>Verification Declined:</strong> {{ $user->kyc_remarks ?: 'Please re-upload a clear Valid ID below to re-submit for review.' }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('resident.profile.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">
                <div>
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}" required>
                </div>
                <div>
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                <div>
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $user->contact_number) }}" placeholder="09XX XXX XXXX">
                </div>
                <div>
                    <label class="form-label">Purok / Sitio</label>
                    <select name="purok_id" class="form-control">
                        <option value="">-- Select Purok --</option>
                        @foreach ($puroks as $purok)
                            <option value="{{ $purok->id }}" {{ old('purok_id', $user->purok_id) == $purok->id ? 'selected' : '' }}>{{ $purok->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="form-label">Sex</label>
                    <select name="sex" class="form-control">
                        <option value="">-- Select --</option>
                        <option value="Male" {{ old('sex', $user->sex) === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex', $user->sex) === 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Civil Status</label>
                    <select name="civil_status" class="form-control">
                        <option value="">-- Select --</option>
                        @foreach (['Single', 'Married', 'Widowed', 'Separated'] as $cs)
                            <option value="{{ $cs }}" {{ old('civil_status', $user->civil_status) === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Residing in Brgy. Cawit Since</label>
                    <input type="date" name="residency_since" class="form-control" value="{{ old('residency_since', $user->residency_since?->format('Y-m-d')) }}">
                </div>

                <div class="full-span">
                    <label class="form-label">Complete Address</label>
                    <textarea name="address" rows="2" class="form-control" placeholder="Purok, Barangay Cawit, Manito, Albay">{{ old('address', $user->address) }}</textarea>
                </div>

                <div class="full-span" style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 14px; font-size: 13px; font-weight: bold; color: #315b75;">
                    <label><input type="checkbox" name="is_registered_voter" value="1" {{ old('is_registered_voter', $user->is_registered_voter) ? 'checked' : '' }}> Registered Voter</label>
                    <label><input type="checkbox" name="is_senior_citizen" value="1" {{ old('is_senior_citizen', $user->is_senior_citizen) ? 'checked' : '' }}> Senior Citizen</label>
                    <label><input type="checkbox" name="is_pwd" value="1" {{ old('is_pwd', $user->is_pwd) ? 'checked' : '' }}> PWD</label>
                </div>

                <div>
                    <label class="form-label">Valid ID Type</label>
                    <select name="valid_id_type" class="form-control">
                        <option value="">-- Select ID Type --</option>
                        @foreach (['PhilSys National ID', "Voter's ID / Comelec Cert", "Driver's License", 'UMID / SSS / GSIS', 'Postal ID', 'Passport', 'School / Student ID', 'Senior Citizen / PWD ID'] as $idType)
                            <option value="{{ $idType }}" {{ old('valid_id_type', $user->valid_id_type) === $idType ? 'selected' : '' }}>{{ $idType }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">
                        Upload / Replace Valid ID
                        @if ($user->valid_id_path)
                            (<a href="{{ route('resident.profile.document', 'valid_id') }}" target="_blank">View Current</a>)
                        @endif
                    </label>
                    <input type="file" name="valid_id" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                </div>

                <div class="full-span">
                    <label class="form-label">
                        Proof of Residency (Optional)
                        @if ($user->proof_of_residency_path)
                            (<a href="{{ route('resident.profile.document', 'proof_of_residency') }}" target="_blank">View Current</a>)
                        @endif
                    </label>
                    <input type="file" name="proof_of_residency" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                    <small style="color: #647f91; font-size: 12px;">Note: Uploading a new ID or Proof of Residency sets your KYC status back to Pending for re-verification.</small>
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