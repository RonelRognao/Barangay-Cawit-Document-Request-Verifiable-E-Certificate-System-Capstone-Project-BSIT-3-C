<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Keeps Laravel from failing if your table doesn't have updated_at
    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'role',
        'contact_number',
        'address',
        'account_status',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Tells Laravel your password column is named 'password_hash'
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }
}