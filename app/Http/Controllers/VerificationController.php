<?php

namespace App\Http\Controllers;

use App\Models\ECertificate;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $certificate = null;
        $error = '';

        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $error = 'Invalid verification link.';
        } else {
            $certificate = ECertificate::with(['request.user', 'request.documentType'])
                ->where('verification_token', $token)
                ->first();

            if (!$certificate) {
                $error = 'Certificate not found. The verification token is invalid.';
            } elseif ($certificate->revoked_at !== null) {
                $error = 'This certificate has been revoked.';
                $certificate = null;
            }
        }

        return view('verification.index', compact('certificate', 'error'));
    }
}