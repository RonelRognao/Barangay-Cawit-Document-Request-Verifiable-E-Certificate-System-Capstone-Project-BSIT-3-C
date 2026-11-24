@extends('layouts.admin')

@section('title', 'Barangay Staff & RBAC')

@push('styles')
<style>
    .staff-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 14px;
    }
    .role-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: bold;
        background: #e0f2fe;
        color: #0369a1;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .role-admin, .role-punong_barangay { background: #ede9fe; color: #5b21b6; }
    .role-secretary { background: #dcfce7; color: #166534; }
    .role-treasurer { background: #fef3c7; color: #92400e; }
    .role-clerk { background: #e0f2fe; color: #0369a1; }
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
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #d8edf8;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 18px 40px rgba(18, 59, 93, 0.25);
    }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
</style>
@endpush

@section('content')
<section class="page-banner">
    <div>
        <div class="eyebrow">Access Control &amp; Governance {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Barangay Staff &amp; Role Management</h1>
        <p>Separate Barangay Secretary, Treasurer, Clerk, and Punong Barangay permissions with individual audit attribution.</p>
    </div>
    <div class="banner-icon" aria-hidden="true">👥</div>
</section>

@if (session('success'))
    <div class="alert alert-success">{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<section class="mini-stats">
    <div class="mini-stat">
        <span>Total Staff Accounts</span>
        <strong>{{ $counts['total'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #16a34a;">
        <span>Active Staff</span>
        <strong>{{ $counts['active'] }}</strong>
    </div>
    <div class="mini-stat" style="border-left-color: #c9364b;">
        <span>Inactive / Suspended</span>
        <strong>{{ $counts['inactive'] }}</strong>
    </div>
</section>

<section class="glass-card">
    <h2>➕ Add Barangay Staff Account</h2>
    <form method="POST" action="{{ route('admin.staff.store') }}">
        @csrf
        <div class="staff-grid">
            <div>
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" class="form-control" required placeholder="e.g., Maria Clara Santos">
            </div>
            <div>
                <label class="form-label" for="email">Official Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" required placeholder="secretary@barangaycawit.gov.ph">
            </div>
            <div>
                <label class="form-label" for="role">Barangay Role (RBAC)</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="secretary" {{ old('role') === 'secretary' ? 'selected' : '' }}>Barangay Secretary (Full Operations)</option>
                    <option value="treasurer" {{ old('role') === 'treasurer' ? 'selected' : '' }}>Barangay Treasurer (Finance &amp; Audit)</option>
                    <option value="clerk" {{ old('role') === 'clerk' ? 'selected' : '' }}>Barangay Clerk (Front-Desk Intake)</option>
                    <option value="punong_barangay" {{ old('role') === 'punong_barangay' ? 'selected' : '' }}>Punong Barangay / OIC</option>
                    @if (auth()->user()->role === 'admin')
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>System Administrator</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="form-label" for="position_title">Custom Title (Optional)</label>
                <input type="text" id="position_title" name="position_title" value="{{ old('position_title') }}" class="form-control" placeholder="e.g., Assistant Secretary">
            </div>
            <div>
                <label class="form-label" for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" class="form-control" placeholder="09XX-XXX-XXXX">
            </div>
            <div>
                <label class="form-label" for="account_status">Account Status</label>
                <select id="account_status" name="account_status" class="form-control" required>
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="password">Password (min 8 chars)</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8">
            </div>
            <div>
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
            </div>
        </div>
        <div style="margin-top: 18px; text-align: right;">
            <button type="submit" class="btn-winter">Create Staff Account</button>
        </div>
    </form>
</section>

<section class="glass-card">
    <h2>🛡️ Barangay Hall Staff Accounts</h2>
    <div class="table-responsive">
        <table class="winter-table">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Email</th>
                    <th>Role / Designation</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffMembers as $member)
                    @php
                        $canEdit = auth()->user()->can('updateStaff', $member);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $member->full_name }}</strong>
                            @if ($member->id === auth()->id())
                                <span style="font-size: 11px; color: #25866b; font-weight: bold;">(You)</span>
                            @endif
                        </td>
                        <td>{{ $member->email }}</td>
                        <td>
                            <span class="role-badge role-{{ $member->role }}">{{ $member->roleLabel() }}</span>
                        </td>
                        <td>{{ $member->contact_number ?: '—' }}</td>
                        <td>
                            @if ($member->account_status === 'active')
                                <span class="status-pill pill-valid">✓ Active</span>
                            @else
                                <span class="status-pill pill-revoked">✕ Inactive</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            @if ($canEdit)
                                @php
                                    $staffPayload = $member->only([
                                        'id',
                                        'full_name',
                                        'email',
                                        'role',
                                        'position_title',
                                        'contact_number',
                                        'account_status',
                                    ]);
                                @endphp
                                <button type="button" class="btn-outline"
                                    data-staff="{{ json_encode($staffPayload) }}"
                                    onclick="openEditStaffModal(this)">
                                    ✏️ Edit
                                </button>
                                @if ($member->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.staff.toggle', $member->id) }}" style="display: inline-block; margin: 0 0 0 6px;">
                                        @csrf
                                        <button type="submit" class="btn-danger-winter" style="padding: 8px 12px;">
                                            {{ $member->account_status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span style="font-size: 12px; color: #94a3b8;">Protected</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="editStaffModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h3 style="margin-top: 0; color: #174b70;">✏️ Edit Staff Account</h3>
        <form id="editStaffForm" method="POST" action="">
            @csrf
            <label class="form-label" for="edit_full_name">Full Name</label>
            <input type="text" id="edit_full_name" name="full_name" class="form-control" required>

            <label class="form-label" for="edit_email">Email</label>
            <input type="email" id="edit_email" name="email" class="form-control" required>

            <div class="staff-grid">
                <div>
                    <label class="form-label" for="edit_role">Role</label>
                    <select id="edit_role" name="role" class="form-control" required>
                        <option value="secretary">Barangay Secretary</option>
                        <option value="treasurer">Barangay Treasurer</option>
                        <option value="clerk">Barangay Clerk</option>
                        <option value="punong_barangay">Punong Barangay / OIC</option>
                        @if (auth()->user()->role === 'admin')
                            <option value="admin">System Administrator</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label" for="edit_status">Status</label>
                    <select id="edit_status" name="account_status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <label class="form-label" for="edit_position_title">Custom Title</label>
            <input type="text" id="edit_position_title" name="position_title" class="form-control">

            <label class="form-label" for="edit_contact_number">Contact Number</label>
            <input type="text" id="edit_contact_number" name="contact_number" class="form-control">

            <div class="staff-grid">
                <div>
                    <label class="form-label" for="edit_password">New Password (Optional)</label>
                    <input type="password" id="edit_password" name="password" class="form-control" minlength="8">
                </div>
                <div>
                    <label class="form-label" for="edit_password_confirmation">Confirm New Password</label>
                    <input type="password" id="edit_password_confirmation" name="password_confirmation" class="form-control" minlength="8">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-outline" onclick="closeEditStaffModal()">Cancel</button>
                <button type="submit" class="btn-winter">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditStaffModal(btn) {
        const data = JSON.parse(btn.getAttribute('data-staff'));
        document.getElementById('editStaffForm').action = "{{ url('admin/staff') }}/" + data.id;
        document.getElementById('edit_full_name').value = data.full_name || '';
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_role').value = data.role || 'clerk';
        document.getElementById('edit_status').value = data.account_status || 'active';
        document.getElementById('edit_position_title').value = data.position_title || '';
        document.getElementById('edit_contact_number').value = data.contact_number || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_password_confirmation').value = '';
        document.getElementById('editStaffModal').classList.add('open');
    }
    function closeEditStaffModal() {
        document.getElementById('editStaffModal').classList.remove('open');
    }
    document.getElementById('editStaffModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditStaffModal();
    });
</script>
@endsection