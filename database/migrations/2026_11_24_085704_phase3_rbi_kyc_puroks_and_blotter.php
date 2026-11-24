<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puroks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('sitio_leader')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now('Asia/Manila');
        foreach (range(1, 7) as $num) {
            DB::table('puroks')->insert([
                'name' => "Purok {$num}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('purok_id')->nullable()->after('address')->constrained('puroks')->nullOnDelete();
            $table->date('birth_date')->nullable()->after('purok_id');
            $table->string('sex', 20)->nullable()->after('birth_date');
            $table->string('civil_status', 30)->nullable()->after('sex');
            $table->date('residency_since')->nullable()->after('civil_status');
            $table->boolean('is_registered_voter')->default(false)->after('residency_since');
            $table->boolean('is_senior_citizen')->default(false)->after('is_registered_voter');
            $table->boolean('is_pwd')->default(false)->after('is_senior_citizen');
            $table->string('valid_id_type', 80)->nullable()->after('is_pwd');
            $table->string('valid_id_path', 255)->nullable()->after('valid_id_type');
            $table->string('proof_of_residency_path', 255)->nullable()->after('valid_id_path');
            $table->string('kyc_status', 20)->default('pending')->after('proof_of_residency_path');
            $table->string('kyc_remarks', 500)->nullable()->after('kyc_status');
            $table->foreignId('kyc_verified_by')->nullable()->after('kyc_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_verified_by');

            $table->index(['role', 'kyc_status'], 'idx_users_role_kyc');
        });

        // Mark existing seeded/legacy users as verified so existing workflows keep working seamlessly
        $purokOneId = DB::table('puroks')->where('name', 'Purok 1')->value('id');
        DB::table('users')
            ->where('kyc_status', 'pending')
            ->update([
                'kyc_status' => 'verified',
                'kyc_verified_at' => $now,
                'purok_id' => DB::raw("CASE WHEN role = 'resident' THEN " . (int) $purokOneId . " ELSE NULL END"),
            ]);

        Schema::create('blotter_records', function (Blueprint $table) {
            $table->id();
            $table->string('case_no', 50)->unique();
            $table->foreignId('respondent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('respondent_name');
            $table->string('complainant_name');
            $table->string('incident_type', 120);
            $table->date('incident_date');
            $table->foreignId('purok_id')->nullable()->constrained('puroks')->nullOnDelete();
            $table->text('summary');
            $table->string('status', 40)->default('Pending Mediation');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'respondent_user_id'], 'idx_blotter_status_user');
            $table->index('respondent_name', 'idx_blotter_respondent_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blotter_records');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_kyc');
            $table->dropConstrainedForeignId('purok_id');
            $table->dropConstrainedForeignId('kyc_verified_by');
            $table->dropColumn([
                'birth_date',
                'sex',
                'civil_status',
                'residency_since',
                'is_registered_voter',
                'is_senior_citizen',
                'is_pwd',
                'valid_id_type',
                'valid_id_path',
                'proof_of_residency_path',
                'kyc_status',
                'kyc_remarks',
                'kyc_verified_at',
            ]);
        });

        Schema::dropIfExists('puroks');
    }
};