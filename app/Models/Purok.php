<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purok extends Model
{
    protected $fillable = [
        'name',
        'sitio_leader',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function residents()
    {
        return $this->hasMany(User::class)->where('role', 'resident');
    }
}