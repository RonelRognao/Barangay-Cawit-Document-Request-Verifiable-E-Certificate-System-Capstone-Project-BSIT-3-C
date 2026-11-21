<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DocumentRequestController extends Controller
{
    public function create()
    {
        $documents = DocumentType::where('is_active', true)
            ->orderBy('document_name', 'asc')
            ->get();

        return view('resident.request_document', compact('documents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type_id' => [
                'required',
                'integer',
                Rule::exists('document_types', 'id')->where('is_active', 1),
            ],
            'purpose' => ['required', 'string', 'max:2000'],
        ]);

        $userId = Auth::id();
        $selectedDocument = DocumentType::findOrFail($validated['document_type_id']);

        $referenceNo = 'BRG-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        // Replaces $pdo->beginTransaction() / commit() / rollBack()
        DB::transaction(function () use ($referenceNo, $userId, $selectedDocument, $validated) {
            DocumentRequest::create([
                'reference_no' => $referenceNo,
                'user_id' => $userId,
                'document_type_id' => $selectedDocument->id,
                'purpose' => trim($validated['purpose']),
            ]);

            Notification::create([
                'user_id' => $userId,
                'title' => 'Document Request Submitted',
                'message' => "Your request for {$selectedDocument->document_name} has been successfully submitted. "
                    . "Your Reference Number is {$referenceNo}. Your request is currently Pending.",
            ]);
        });

        return redirect()
            ->route('resident.request.create')
            ->with('success', 'Your document request has been submitted successfully!')
            ->with('reference_no', $referenceNo);
    }
}