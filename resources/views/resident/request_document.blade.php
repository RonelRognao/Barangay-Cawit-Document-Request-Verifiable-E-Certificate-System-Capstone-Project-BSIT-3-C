@extends('layouts.resident')

@section('title', 'Request a Document')

@push('styles')
<style>
    .request-wrap {
        max-width: 720px;
        margin: 0 auto;
    }

    .doc-select-hint {
        margin-top: 8px;
        font-size: 12px;
        color: #647f91;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 24px;
    }

    .ref-box {
        display: inline-block;
        margin: 8px 0;
        padding: 8px 14px;
        background: white;
        border: 1px dashed #16a34a;
        border-radius: 8px;
        font-size: 16px;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="request-wrap">
    <section class="page-banner">
        <div>
            <div class="eyebrow">Online Document Services {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
            <h1>Request a Barangay Document</h1>
            <p>Choose the document you need and describe its purpose below.</p>
        </div>
        <div class="banner-icon" aria-hidden="true">📄</div>
    </section>

    <div class="glass-card">
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                <strong>{{ $activeTheme === 'christmas' ? '🎄' : '✓' }} {{ session('success') }}</strong><br>
                <span>Your Reference Number:</span><br>
                <span class="ref-box">{{ session('reference_no') }}</span><br>
                <small>Please save this number or track it anytime under <strong>My Requests</strong>.</small>
            </div>
        @endif

        <form method="POST" action="{{ route('resident.request.store') }}">
            @csrf

            <label for="document_type_id" class="form-label">Select Document *</label>
            <select name="document_type_id" id="document_type_id" class="form-control" required>
                <option value="">-- Choose a document --</option>
                @foreach ($documents as $document)
                    <option value="{{ $document->id }}" {{ old('document_type_id') == $document->id ? 'selected' : '' }}>
                        {{ $document->document_name }}
                        @if ($document->fee > 0)
                            (₱{{ number_format($document->fee, 2) }})
                        @else
                            (Free)
                        @endif
                    </option>
                @endforeach
            </select>
            <div class="doc-select-hint">
                Fees shown are official Barangay Cawit processing fees.
            </div>

            <label for="purpose" class="form-label">Purpose of Request *</label>
            <textarea
                name="purpose"
                id="purpose"
                rows="5"
                maxlength="2000"
                class="form-control"
                placeholder="Example: Requirement for local employment / scholarship application..."
                required
            >{{ old('purpose') }}</textarea>

            <div class="form-actions">
                <a href="{{ route('resident.dashboard') }}" class="btn-outline">← Back to Dashboard</a>
                <button type="submit" class="btn-winter">{{ $activeTheme === 'christmas' ? '🎁 Submit Document Request' : 'Submit Document Request' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection