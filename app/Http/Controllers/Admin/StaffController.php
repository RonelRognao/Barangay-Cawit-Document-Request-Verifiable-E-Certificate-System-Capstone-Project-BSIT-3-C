<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index()
    {
        Gate::authorize('viewAnyStaff', User::class);

        $staffMembers = User::whereIn('role', User::STAFF_ROLES)
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'punong_barangay' THEN 2 WHEN 'secretary' THEN 3 WHEN 'treasurer' THEN 4 WHEN 'clerk' THEN 5 ELSE 6 END")
            ->orderBy('full_name')
            ->get();

        $counts = [
            'total' => $staffMembers->count(),
            'active' => $staffMembers->where('account_status', 'active')->count(),
            'inactive' => $staffMembers->where('account_status', 'inactive')->count(),
        ];

        return view('admin.staff.index', compact('staffMembers', 'counts'));
    }

    public function store(StoreStaffRequest $request)
    {
        Gate::authorize('createStaff', User::class);

        $validated = $request->validated();

        $staff = User::create([
            'full_name' => trim($validated['full_name']),
            'email' => strtolower(trim($validated['email'])),
            'password_hash' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'position_title' => trim((string) ($validated['position_title'] ?? '')) ?: null,
            'contact_number' => trim((string) ($validated['contact_number'] ?? '')) ?: null,
            'address' => 'Barangay Cawit Hall, Manito, Albay',
            'account_status' => $validated['account_status'],
            'created_at' => now('Asia/Manila'),
        ]);

        $this->activityLogger->log(
            action: 'Staff Account Created',
            description: "Created staff account for {$staff->full_name} ({$staff->email}) with role {$staff->roleLabel()}.",
            category: 'security',
            subject: $staff,
            newValues: [
                'full_name' => $staff->full_name,
                'role' => $staff->role,
                'account_status' => $staff->account_status,
            ],
            request: $request
        );

        return back()->with('success', "Staff account for {$staff->full_name} created successfully.");
    }

    public function update(UpdateStaffRequest $request, User $staff)
    {
        Gate::authorize('updateStaff', $staff);

        $validated = $request->validated();

        if ($staff->id === Auth::id() && $validated['account_status'] !== 'active') {
            return back()->with('error', 'You cannot deactivate your own currently logged-in account.');
        }

        if ($staff->id === Auth::id() && !in_array($validated['role'], ['admin', 'punong_barangay', 'secretary'], true)) {
            return back()->with('error', 'You cannot demote your own governance role while logged in.');
        }

        $oldValues = [
            'full_name' => $staff->full_name,
            'email' => $staff->email,
            'role' => $staff->role,
            'position_title' => $staff->position_title,
            'account_status' => $staff->account_status,
        ];

        $updateData = [
            'full_name' => trim($validated['full_name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'position_title' => trim((string) ($validated['position_title'] ?? '')) ?: null,
            'contact_number' => trim((string) ($validated['contact_number'] ?? '')) ?: null,
            'account_status' => $validated['account_status'],
        ];

        $passwordChanged = !empty($validated['password']);
        if ($passwordChanged) {
            $updateData['password_hash'] = Hash::make($validated['password']);
        }

        $staff->update($updateData);

        $newValues = [
            'full_name' => $staff->full_name,
            'email' => $staff->email,
            'role' => $staff->role,
            'position_title' => $staff->position_title,
            'account_status' => $staff->account_status,
        ];

        if ($passwordChanged) {
            $newValues['password'] = 'updated';
        }

        $this->activityLogger->log(
            action: 'Staff Account Updated',
            description: "Updated staff account {$staff->full_name} ({$staff->email}) — Role: {$staff->roleLabel()}, Status: {$staff->account_status}.",
            category: 'security',
            subject: $staff,
            oldValues: $oldValues,
            newValues: $newValues,
            request: $request
        );

        return back()->with('success', "Staff account for {$staff->full_name} updated.");
    }

    public function toggleStatus(Request $request, User $staff)
    {
        Gate::authorize('updateStaff', $staff);

        if ($staff->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own currently logged-in account.');
        }

        $oldStatus = $staff->account_status;
        $newStatus = $oldStatus === 'active' ? 'inactive' : 'active';

        $staff->update(['account_status' => $newStatus]);

        $this->activityLogger->log(
            action: 'Staff Status Toggled',
            description: "Changed account status of {$staff->full_name} ({$staff->email}) from {$oldStatus} to {$newStatus}.",
            category: 'security',
            subject: $staff,
            oldValues: ['account_status' => $oldStatus],
            newValues: ['account_status' => $newStatus],
            request: $request
        );

        return back()->with('success', "{$staff->full_name} is now {$newStatus}.");
    }
}