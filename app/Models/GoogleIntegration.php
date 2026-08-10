<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_id',
        'email',
        'access_token',
        'refresh_token',
        'expires_in',
    ];

    /**
     * Get the user that owns the integration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
