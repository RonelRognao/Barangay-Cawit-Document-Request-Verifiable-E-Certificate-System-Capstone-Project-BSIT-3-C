<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique()->after('id');
            $table->boolean('is_fee_exempt_default')->default(false)->after('fee');
            $table->unsignedInteger('validity_days')->default(180)->after('is_fee_exempt_default');
            $table->boolean('requires_business_details')->default(false)->after('validity_days');
            $table->boolean('requires_supporting_doc')->default(false)->after('requires_business_details');
            $table->boolean('is_ra11261')->default(false)->after('requires_supporting_doc');
            $table->json('allowed_purposes')->nullable()->after('is_ra11261');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ra11261_availed_at')->nullable()->after('kyc_verified_at');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('purpose_category', 120)->nullable()->after('purpose');
            $table->json('document_metadata')->nullable()->after('purpose_category');
            $table->string('attachment_path', 255)->nullable()->after('document_metadata');
            $table->string('attachment_label', 100)->nullable()->after('attachment_path');
            $table->decimal('assessed_fee', 10, 2)->default(0)->after('attachment_label');
            $table->string('fee_exemption_type', 40)->default('none')->after('assessed_fee');
            $table->decimal('final_fee', 10, 2)->default(0)->after('fee_exemption_type');
            $table->string('payment_status', 30)->default('unpaid')->after('final_fee');
            $table->string('official_receipt_no', 60)->nullable()->after('payment_status');
            $table->date('official_receipt_date')->nullable()->after('official_receipt_no');
            $table->foreignId('payment_verified_by')->nullable()->after('official_receipt_date')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');

            $table->index(['payment_status', 'official_receipt_no'], 'idx_req_payment_or');
        });

        $now = now('Asia/Manila');

        $configs = [
            'Barangay Clearance' => [
                'code' => 'BARANGAY_CLEARANCE',
                'is_fee_exempt_default' => false,
                'validity_days' => 180,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => json_encode([
                    'Local Employment',
                    'Police / NBI Clearance Requirement',
                    'Postal / Government ID Application',
                    'Bank / Loan Application',
                    'Travel / Relocation Requirement',
                ]),
            ],
            'Certificate of Indigency' => [
                'code' => 'INDIGENCY',
                'is_fee_exempt_default' => true,
                'validity_days' => 90,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => json_encode([
                    'Medical / Hospital Assistance (MAIP / DSWD)',
                    'Educational / Scholarship Assistance (CHED / LGU)',
                    'Burial / Financial Assistance',
                    'PAO / Free Legal Aid Requirement',
                ]),
            ],
            'Certificate of Residency' => [
                'code' => 'RESIDENCY',
                'is_fee_exempt_default' => false,
                'validity_days' => 180,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => json_encode([
                    'School Enrollment / Transfer',
                    'Government Agency / PhilSys Verification',
                    'Utility Connection (Water / Electricity)',
                    'Employment / Bank Verification',
                ]),
            ],
            'Barangay Business Clearance' => [
                'code' => 'BUSINESS_CLEARANCE',
                'is_fee_exempt_default' => false,
                'validity_days' => 365,
                'requires_business_details' => true,
                'requires_supporting_doc' => true,
                'is_ra11261' => false,
                'allowed_purposes' => json_encode([
                    'New Business Permit (Mayor\'s Permit Application)',
                    'Annual Business Permit Renewal',
                    'Lending / Microfinance Business Registration',
                ]),
            ],
            'Certificate of Good Moral Character' => [
                'code' => 'GOOD_MORAL',
                'is_fee_exempt_default' => false,
                'validity_days' => 180,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => json_encode([
                    'College / University Admission or Scholarship',
                    'Local / Overseas Employment Requirement',
                    'Professional License / Board Exam Application',
                ]),
            ],
        ];

        foreach ($configs as $name => $data) {
            DB::table('document_types')
                ->where('document_name', $name)
                ->update($data + ['created_at' => $now, 'updated_at' => $now]);
        }

        // Add RA 11261 First-Time Jobseeker Assistance Document Type if not yet present
        if (!DB::table('document_types')->where('code', 'FIRST_TIME_JOBSEEKER')->exists()) {
            DB::table('document_types')->insert([
                'code' => 'FIRST_TIME_JOBSEEKER',
                'document_name' => 'First-Time Jobseeker Certificate (RA 11261)',
                'description' => 'One-time fee-waived barangay certification for qualified first-time jobseekers with at least 6 months residency (RA 11261).',
                'fee' => 0.00,
                'is_fee_exempt_default' => true,
                'validity_days' => 365,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => true,
                'allowed_purposes' => json_encode([
                    'First-Time Local Employment Application (RA 11261 Fee Waiver)',
                    'NBI / Police / Pre-Employment Clearance Waiver (RA 11261)',
                ]),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Backfill fee & payment status on existing historical document requests so old completed records stay valid
        $docFees = DB::table('document_types')->pluck('fee', 'id');
        $existingRequests = DB::table('document_requests')->select('id', 'document_type_id', 'status')->get();

        foreach ($existingRequests as $req) {
            $fee = (float) ($docFees[$req->document_type_id] ?? 0);
            $paymentStatus = $fee <= 0 ? 'exempt' : ($req->status === 'Completed' ? 'paid' : 'unpaid');

            DB::table('document_requests')
                ->where('id', $req->id)
                ->update([
                    'assessed_fee' => $fee,
                    'final_fee' => $fee,
                    'fee_exemption_type' => $fee <= 0 ? 'indigency' : 'none',
                    'payment_status' => $paymentStatus,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndex('idx_req_payment_or');
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn([
                'purpose_category',
                'document_metadata',
                'attachment_path',
                'attachment_label',
                'assessed_fee',
                'fee_exemption_type',
                'final_fee',
                'payment_status',
                'official_receipt_no',
                'official_receipt_date',
                'payment_verified_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ra11261_availed_at');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'is_fee_exempt_default',
                'validity_days',
                'requires_business_details',
                'requires_supporting_doc',
                'is_ra11261',
                'allowed_purposes',
                'created_at',
                'updated_at',
            ]);
        });
    }
};