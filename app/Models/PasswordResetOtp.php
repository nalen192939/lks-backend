<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'verified_at',
        'reset_token_hash',
        'reset_token_expires_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'reset_token_expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
