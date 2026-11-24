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
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->string('log_category', 30)->default('operational')->after('admin_id');
            $table->string('actor_name')->nullable()->after('log_category');
            $table->string('actor_role', 30)->nullable()->after('actor_name');
            $table->string('ip_address', 45)->nullable()->after('description');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
            $table->string('subject_type', 100)->nullable()->after('user_agent');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->json('old_values')->nullable()->after('subject_id');
            $table->json('new_values')->nullable()->after('old_values');

            $table->index(['log_category', 'created_at'], 'idx_logs_category_created');
            $table->index(['action', 'created_at'], 'idx_logs_action_created');
        });

        Schema::table('e_certificates', function (Blueprint $table) {
            $table->string('verification_hash', 64)->nullable()->after('verification_token');
            $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users')->nullOnDelete();
            $table->string('revocation_reason', 255)->nullable()->after('revoked_by');
        });

        // 1) Reconcile any approved requests that already have a valid (unrevoked) e-certificate
        $issuedRequestIds = DB::table('e_certificates')
            ->whereNull('revoked_at')
            ->pluck('request_id');

        if ($issuedRequestIds->isNotEmpty()) {
            DB::table('document_requests')
                ->whereIn('id', $issuedRequestIds)
                ->where('status', 'Approved')
                ->update([
                    'status' => 'Completed',
                    'updated_at' => now('Asia/Manila'),
                ]);
        }

        // 2) Repair UTC-vs-PHT artifact on existing certificates and backfill verification_hash
        $certificates = DB::table('e_certificates')
            ->join('document_requests', 'document_requests.id', '=', 'e_certificates.request_id')
            ->select(
                'e_certificates.id',
                'e_certificates.request_id',
                'e_certificates.certificate_no',
                'e_certificates.verification_token',
                'e_certificates.issued_at',
                'document_requests.requested_at'
            )
            ->get();

        foreach ($certificates as $cert) {
            $issuedAt = Carbon::parse($cert->issued_at, 'Asia/Manila');
            $requestedAt = Carbon::parse($cert->requested_at, 'Asia/Manila');

            if ($issuedAt->lt($requestedAt)) {
                $shifted = $issuedAt->copy()->addHours(8);
                if ($shifted->gte($requestedAt) && $shifted->lte(now('Asia/Manila'))) {
                    $issuedAt = $shifted;
                }
            }

            $formattedIssuedAt = $issuedAt->format('Y-m-d H:i:s');
            $payload = implode('|', [
                $cert->certificate_no,
                $cert->request_id,
                $cert->verification_token,
                $formattedIssuedAt,
            ]);

            DB::table('e_certificates')
                ->where('id', $cert->id)
                ->update([
                    'issued_at' => $formattedIssuedAt,
                    'verification_hash' => hash_hmac('sha256', $payload, (string) config('app.key')),
                ]);
        }

        // 3) Categorize existing logs & backfill actor snapshots
        DB::table('admin_activity_logs')->where('action', 'Site Theme Updated')->update(['log_category' => 'ui_preference']);
        DB::table('admin_activity_logs')->whereIn('action', ['Request Status Updated', 'Request Remarks Updated'])->update(['log_category' => 'request']);
        DB::table('admin_activity_logs')->whereIn('action', ['E-Certificate Issued', 'E-Certificate Revoked'])->update(['log_category' => 'certificate']);

        $users = DB::table('users')->select('id', 'full_name', 'role')->get()->keyBy('id');
        foreach ($users as $userId => $user) {
            DB::table('admin_activity_logs')
                ->where('admin_id', $userId)
                ->whereNull('actor_name')
                ->update([
                    'actor_name' => $user->full_name,
                    'actor_role' => $user->role,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('e_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn(['verification_hash', 'revocation_reason']);
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_category_created');
            $table->dropIndex('idx_logs_action_created');
            $table->dropColumn([
                'log_category',
                'actor_name',
                'actor_role',
                'ip_address',
                'user_agent',
                'subject_type',
                'subject_id',
                'old_values',
                'new_values',
            ]);
        });
    }
};