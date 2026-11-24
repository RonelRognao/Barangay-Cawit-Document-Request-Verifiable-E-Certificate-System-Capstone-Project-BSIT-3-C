@extends('layouts.admin')

@section('title', 'DILG & COA Reports')

@push('styles')
<style>
    .filter-bar {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 10px; align-items: end; background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d8edf8; border-radius: 14px; padding: 16px 20px; margin-bottom: 22px;
    }
    .print-header { display: none; text-align: center; margin-bottom: 18px; border-bottom: 2px solid #174b70; padding-bottom: 10px; }
    @media print {
        .portal-header, .subnav, .portal-footer, .filter-bar, .no-print, .snowflake { display: none !important; }
        .portal-main { margin: 0; padding: 0; max-width: 100%; }
        .glass-card, .mini-stat { box-shadow: none !important; border: 1px solid #cbd5e1 !important; break-inside: avoid; }
        .print-header { display: block; }
    }
</style>
@endpush

@section('content')
<div class="print-header">
    <strong>REPUBLIC OF THE PHILIPPINES &middot; MUNICIPALITY OF MANITO, ALBAY</strong><br>
    <strong style="font-size: 18px;">BARANGAY CAWIT &mdash; DILG &amp; COA ISSUANCE &amp; COLLECTION REPORT</strong><br>
    <small>Period: {{ $filters['date_from'] }} to {{ $filters['date_to'] }} &middot; Generated: {{ now('Asia/Manila')->format('M d, Y h:i A') }} (PHT)</small>
</div>

<section class="page-banner no-print">
    <div>
        <div class="eyebrow">DILG &amp; COA Compliance {{ $activeTheme === 'christmas' ? '❄️' : '' }}</div>
        <h1>Monthly Issuance &amp; Financial Reports</h1>
        <p>Summarize document issuances, walk-in volume, Official Receipt (O.R.) collections, and RA 11261 / Indigency fee waivers.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button type="button" class="btn-outline" onclick="window.print()">🖨️ Print Report</button>
        <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn-winter">📥 Export CSV</a>
    </div>
</section>

<form method="GET" action="{{ route('admin.reports.index') }}" class="filter-bar no-print">
    <div>
        <label class="form-label" style="margin-top: 0;">Date From</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
    </div>
    <div>
        <label class="form-label" style="margin-top: 0;">Date To</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
    </div>
    <div>
        <label class="form-label" style="margin-top: 0;">Document Type</label>
        <select name="document_type_id" class="form-control">
            <option value="">All Documents</option>
            @foreach ($documentTypes as $dt)
                <option value="{{ $dt->id }}" {{ (string) $filters['document_type_id'] === (string) $dt->id ? 'selected' : '' }}>{{ $dt->document_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="margin-top: 0;">Purok</label>
        <select name="purok_id" class="form-control">
            <option value="">All Puroks</option>
            @foreach ($puroks as $p)
                <option value="{{ $p->id }}" {{ (string) $filters['purok_id'] === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn-winter">Apply</button>
        <a href="{{ route('admin.reports.index') }}" class="btn-outline">Reset</a>
    </div>
</form>

<section class="mini-stats">
    <div class="mini-stat"><span>Requests (Walk-In: {{ $summary['walk_in_count'] }})</span><strong>{{ $summary['total_requests'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #16a34a;"><span>Certificates Issued</span><strong>{{ $summary['certs_issued'] }}</strong></div>
    <div class="mini-stat" style="border-left-color: #0f766e;"><span>COA Revenue Collected</span><strong>₱{{ number_format($summary['total_revenue'], 2) }}</strong></div>
    <div class="mini-stat" style="border-left-color: #2563eb;"><span>Fee Waivers ({{ $summary['exempt_count'] }})</span><strong>₱{{ number_format($summary['waived_value'], 2) }}</strong></div>
</section>

<section class="glass-card">
    <h2>📊 Summary by Document Type</h2>
    <div class="table-responsive">
        <table class="winter-table">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Total Requests</th>
                    <th>Completed</th>
                    <th>Paid</th>
                    <th>Exempt / Waived</th>
                    <th>Total Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($byDocumentType as $row)
                    <tr>
                        <td><strong>{{ $row['document_name'] }}</strong></td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['completed'] }}</td>
                        <td>{{ $row['paid_count'] }}</td>
                        <td>{{ $row['exempt_count'] }}</td>
                        <td><strong>₱{{ number_format($row['revenue'], 2) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: #647f91;">No records for selected period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="glass-card">
    <h2>🧾 COA Official Receipt (O.R.) Collection Ledger</h2>
    <div class="table-responsive">
        <table class="winter-table">
            <thead>
                <tr>
                    <th>O.R. No.</th>
                    <th>O.R. Date</th>
                    <th>Payor (Resident)</th>
                    <th>Document &amp; Ref No.</th>
                    <th>Amount</th>
                    <th>Verified By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paidLedger as $item)
                    <tr>
                        <td><strong>{{ $item->official_receipt_no ?: '— (Legacy)' }}</strong></td>
                        <td>{{ $item->official_receipt_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $item->user?->full_name }}</td>
                        <td>{{ $item->documentType?->document_name }} <small>({{ $item->reference_no }})</small></td>
                        <td><strong>₱{{ number_format((float) $item->final_fee, 2) }}</strong></td>
                        <td>{{ $item->paymentVerifier?->full_name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: #647f91;">No paid O.R. collections in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="glass-card">
    <h2>⚖️ Fee Exemption &amp; RA 11261 Waiver Register</h2>
    <div class="table-responsive">
        <table class="winter-table">
            <thead>
                <tr>
                    <th>Reference No.</th>
                    <th>Resident</th>
                    <th>Document</th>
                    <th>Exemption Basis</th>
                    <th>Waived Fee</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($exemptLedger as $item)
                    <tr>
                        <td><strong>{{ $item->reference_no }}</strong></td>
                        <td>{{ $item->user?->full_name }}</td>
                        <td>{{ $item->documentType?->document_name }}</td>
                        <td>{{ \App\Services\FeeAndMandateService::EXEMPTION_LABELS[$item->fee_exemption_type] ?? $item->fee_exemption_type }}</td>
                        <td>₱{{ number_format((float) $item->assessed_fee, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: #647f91;">No fee exemptions in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection