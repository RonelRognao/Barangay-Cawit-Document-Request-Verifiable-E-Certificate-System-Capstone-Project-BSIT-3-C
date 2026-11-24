<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('e_certificates', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('issued_at');
            $table->index('expires_at', 'idx_cert_expires_at');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->boolean('is_walk_in')->default(false)->after('reference_no');
            $table->foreignId('encoded_by')->nullable()->after('processed_by')->constrained('users')->nullOnDelete();
        });

        // Backfill expires_at on existing issued certificates based on each DocumentType's validity_days
        $certs = DB::table('e_certificates')
            ->join('document_requests', 'document_requests.id', '=', 'e_certificates.request_id')
            ->join('document_types', 'document_types.id', '=', 'document_requests.document_type_id')
            ->select('e_certificates.id', 'e_certificates.issued_at', 'document_types.validity_days')
            ->whereNull('e_certificates.expires_at')
            ->get();

        foreach ($certs as $cert) {
            $days = max(1, (int) ($cert->validity_days ?? 180));
            $expiresAt = Carbon::parse($cert->issued_at, 'Asia/Manila')->addDays($days)->format('Y-m-d H:i:s');

            DB::table('e_certificates')
                ->where('id', $cert->id)
                ->update(['expires_at' => $expiresAt]);
        }
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('encoded_by');
            $table->dropColumn('is_walk_in');
        });

        Schema::table('e_certificates', function (Blueprint $table) {
            $table->dropIndex('idx_cert_expires_at');
            $table->dropColumn('expires_at');
        });
    }
};