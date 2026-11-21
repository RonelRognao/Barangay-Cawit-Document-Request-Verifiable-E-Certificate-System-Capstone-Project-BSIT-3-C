<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\ECertificate;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ResidentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return view('resident.dashboard', [
            'full_name' => $user->full_name,
            'unread_count' => $unreadCount,
        ]);
    }

    public function myRequests()
    {
        $requests = DocumentRequest::with('documentType')
            ->where('user_id', Auth::id())
            ->orderByDesc('requested_at')
            ->get();

        return view('resident.my_requests', compact('requests'));
    }

    public function myCertificates()
    {
        $certificates = ECertificate::with(['request.documentType'])
            ->whereNull('revoked_at')
            ->whereHas('request', fn ($q) => $q->where('user_id', Auth::id()))
            ->orderByDesc('issued_at')
            ->get();

        return view('resident.my_certificates', compact('certificates'));
    }

    public function printCertificate(string $certificateNo)
    {
        $certificate = ECertificate::with(['request.documentType', 'request.user'])
            ->where('certificate_no', $certificateNo)
            ->whereNull('revoked_at')
            ->whereHas('request', fn ($q) => $q->where('user_id', Auth::id()))
            ->firstOrFail();

        return view('resident.print_certificate', compact('certificate'));
    }

    public function notifications()
    {
        $userId = Auth::id();

        $notifications = Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return view('resident.notifications', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markNotificationRead(Notification $notification)
    {
        // Make sure a resident can only mark THEIR OWN notification as read
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function profile()
    {
        return view('resident.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $user->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return back()->with('success', 'Profile updated successfully!');
    }
}