<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position_title', 100)->nullable()->after('role');
        });

        Schema::create('barangay_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('position', 60);
            $table->string('designation_title', 120)->nullable();
            $table->string('committee', 120)->nullable();
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->boolean('is_primary_signatory')->default(false);
            $table->boolean('is_attest_signatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_primary_signatory'], 'idx_officials_primary');
            $table->index(['is_active', 'is_attest_signatory'], 'idx_officials_attest');
        });

        Schema::table('e_certificates', function (Blueprint $table) {
            $table->foreignId('primary_official_id')
                ->nullable()
                ->after('issued_by')
                ->constrained('barangay_officials')
                ->nullOnDelete();
            $table->string('signatory_name')->nullable()->after('primary_official_id');
            $table->string('signatory_title', 120)->nullable()->after('signatory_name');
            $table->string('attested_by_name')->nullable()->after('signatory_title');
            $table->string('attested_by_title', 120)->nullable()->after('attested_by_name');
        });

        // Seed default Punong Barangay official so existing & immediate issuances remain consistent
        $now = now('Asia/Manila');
        $officialId = DB::table('barangay_officials')->insertGetId([
            'full_name' => 'ERNISTO DAEN',
            'position' => 'Punong Barangay',
            'designation_title' => 'Punong Barangay / Barangay Captain',
            'is_primary_signatory' => true,
            'is_attest_signatory' => false,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Backfill snapshot signatory info on previously issued certificates
        DB::table('e_certificates')
            ->whereNull('signatory_name')
            ->update([
                'primary_official_id' => $officialId,
                'signatory_name' => 'ERNISTO DAEN',
                'signatory_title' => 'Punong Barangay / Barangay Captain',
            ]);
    }

    public function down(): void
    {
        Schema::table('e_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_official_id');
            $table->dropColumn([
                'signatory_name',
                'signatory_title',
                'attested_by_name',
                'attested_by_title',
            ]);
        });

        Schema::dropIfExists('barangay_officials');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position_title');
        });
    }
};