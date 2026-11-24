<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_logs';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'log_category',
        'actor_name',
        'actor_role',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}