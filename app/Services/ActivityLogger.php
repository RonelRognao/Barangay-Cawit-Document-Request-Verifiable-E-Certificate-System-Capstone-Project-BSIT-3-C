<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $action,
        string $description,
        string $category = 'operational',
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): AdminActivityLog {
        $user = Auth::user();
        $req = $request ?? request();

        return AdminActivityLog::create([
            'admin_id' => $user?->id,
            'log_category' => $category,
            'actor_name' => $user?->full_name ?? 'System',
            'actor_role' => $user?->role ?? 'system',
            'action' => $action,
            'description' => $description,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent() ? substr((string) $req->userAgent(), 0, 255) : null,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'created_at' => now('Asia/Manila'),
        ]);
    }
}