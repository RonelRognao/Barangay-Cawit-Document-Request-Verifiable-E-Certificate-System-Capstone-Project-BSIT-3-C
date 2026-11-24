<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentTypeRequest;
use App\Http\Requests\Admin\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DocumentTypeController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index()
    {
        Gate::authorize('viewAny', DocumentType::class);

        $documentTypes = DocumentType::orderByDesc('is_active')
            ->orderBy('document_name')
            ->get();

        $counts = [
            'total' => $documentTypes->count(),
            'active' => $documentTypes->where('is_active', true)->count(),
            'free_or_exempt' => $documentTypes->filter(fn ($d) => (float) $d->fee <= 0 || $d->is_fee_exempt_default || $d->is_ra11261)->count(),
        ];

        return view('admin.document_types.index', compact('documentTypes', 'counts'));
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        Gate::authorize('create', DocumentType::class);

        $validated = $request->validated();
        $code = trim((string) ($validated['code'] ?? ''));
        if ($code === '') {
            $code = Str::upper(Str::slug($validated['document_name'], '_'));
        } else {
            $code = Str::upper(Str::slug($code, '_'));
        }

        $docType = DocumentType::create([
            'code' => $code,
            'document_name' => trim($validated['document_name']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'fee' => (float) $validated['fee'],
            'validity_days' => (int) $validated['validity_days'],
            'is_fee_exempt_default' => $request->boolean('is_fee_exempt_default'),
            'requires_business_details' => $request->boolean('requires_business_details'),
            'requires_supporting_doc' => $request->boolean('requires_supporting_doc'),
            'is_ra11261' => $request->boolean('is_ra11261'),
            'allowed_purposes' => $this->parsePurposes($validated['allowed_purposes_text'] ?? null),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLogger->log(
            action: 'Document Type Created',
            description: "Added document type {$docType->document_name} (Fee: PHP " . number_format((float) $docType->fee, 2) . ", Validity: {$docType->validity_days} days).",
            category: 'document_types',
            subject: $docType,
            newValues: ['document_name' => $docType->document_name, 'fee' => $docType->fee],
            request: $request
        );

        return back()->with('success', "Document type '{$docType->document_name}' created successfully.");
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        Gate::authorize('update', $documentType);

        $validated = $request->validated();
        $oldValues = [
            'document_name' => $documentType->document_name,
            'fee' => (string) $documentType->fee,
            'validity_days' => $documentType->validity_days,
            'is_active' => $documentType->is_active,
        ];

        $code = trim((string) ($validated['code'] ?? ''));
        $code = $code !== '' ? Str::upper(Str::slug($code, '_')) : $documentType->code;

        $documentType->update([
            'code' => $code,
            'document_name' => trim($validated['document_name']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'fee' => (float) $validated['fee'],
            'validity_days' => (int) $validated['validity_days'],
            'is_fee_exempt_default' => $request->boolean('is_fee_exempt_default'),
            'requires_business_details' => $request->boolean('requires_business_details'),
            'requires_supporting_doc' => $request->boolean('requires_supporting_doc'),
            'is_ra11261' => $request->boolean('is_ra11261'),
            'allowed_purposes' => $this->parsePurposes($validated['allowed_purposes_text'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->activityLogger->log(
            action: 'Document Type Updated',
            description: "Updated document type {$documentType->document_name} (Fee: PHP " . number_format((float) $documentType->fee, 2) . ").",
            category: 'document_types',
            subject: $documentType,
            oldValues: $oldValues,
            newValues: [
                'document_name' => $documentType->document_name,
                'fee' => (string) $documentType->fee,
                'validity_days' => $documentType->validity_days,
                'is_active' => $documentType->is_active,
            ],
            request: $request
        );

        return back()->with('success', "Document type '{$documentType->document_name}' updated.");
    }

    public function toggleActive(Request $request, DocumentType $documentType)
    {
        Gate::authorize('update', $documentType);

        $old = $documentType->is_active;
        $documentType->update(['is_active' => !$old]);

        $this->activityLogger->log(
            action: 'Document Type Status Toggled',
            description: "Set '{$documentType->document_name}' to " . ($documentType->is_active ? 'Active' : 'Inactive') . '.',
            category: 'document_types',
            subject: $documentType,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $documentType->is_active],
            request: $request
        );

        return back()->with('success', "'{$documentType->document_name}' is now " . ($documentType->is_active ? 'Active' : 'Inactive') . '.');
    }

    /**
     * @return array<int, string>|null
     */
    private function parsePurposes(?string $text): ?array
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: []),
            fn (string $line) => $line !== ''
        ));

        return empty($lines) ? null : $lines;
    }
}