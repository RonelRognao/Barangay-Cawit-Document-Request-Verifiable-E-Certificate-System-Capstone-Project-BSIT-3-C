<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\ActivityLogger;
use App\Models\Notification;
use App\Models\Purok;
use App\Services\KycStorageService;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly KycStorageService $kycStorage
    ) {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Auth::attempt checks email, verifies password_hash, AND checks account_status = 'active'
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'account_status' => 'active',
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate(); // Replaces session_regenerate_id(true)

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->isResident()) {
                return redirect()->route('resident.dashboard');
            }

            if ($user->isStaff()) {
                $this->activityLogger->log(
                    action: 'Staff Login',
                    description: "{$user->full_name} ({$user->roleLabel()}) signed in to the Admin Portal.",
                    category: 'security',
                    subject: $user,
                    request: $request
                );

                return redirect()->route('admin.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid email or password, or your account is inactive.');
    }

    public function showRegister()
    {
        $puroks = Purok::where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('puroks'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'purok_id' => ['required', 'integer', Rule::exists('puroks', 'id')->where('is_active', true)],
            'address' => ['required', 'string', 'max:500'],
            'birth_date' => ['required', 'date', 'before:today'],
            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'civil_status' => ['required', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'residency_since' => ['required', 'date', 'before_or_equal:today'],
            'valid_id_type' => ['required', 'string', 'max:80'],
            'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'proof_of_residency' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validIdPath = $this->kycStorage->storeValidId($request->file('valid_id'));
        $proofPath = $request->hasFile('proof_of_residency')
            ? $this->kycStorage->storeProofOfResidency($request->file('proof_of_residency'))
            : null;

        $resident = User::create([
            'full_name' => trim($validated['full_name']),
            'email' => strtolower(trim($validated['email'])),
            'password_hash' => Hash::make($validated['password']),
            'role' => 'resident',
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => trim($validated['address']),
            'purok_id' => (int) $validated['purok_id'],
            'birth_date' => $validated['birth_date'],
            'sex' => $validated['sex'],
            'civil_status' => $validated['civil_status'],
            'residency_since' => $validated['residency_since'],
            'is_registered_voter' => $request->boolean('is_registered_voter'),
            'is_senior_citizen' => $request->boolean('is_senior_citizen'),
            'is_pwd' => $request->boolean('is_pwd'),
            'valid_id_type' => $validated['valid_id_type'],
            'valid_id_path' => $validIdPath,
            'proof_of_residency_path' => $proofPath,
            'account_status' => 'active',
            'kyc_status' => 'pending',
            'created_at' => now('Asia/Manila'),
        ]);

        Notification::create([
            'user_id' => $resident->id,
            'title' => 'Account Verification Pending',
            'message' => 'Thank you for registering! Barangay Cawit staff are reviewing your Valid ID and residency details. You can check your verification status under My Profile.',
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Registration submitted! Please log in. Your Valid ID is pending Barangay Hall verification before requesting documents.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}