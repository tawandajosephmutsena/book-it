<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'slug',
        'duration',
        'buffer_time',
        'color_theme',
        'screening_questions',
        'minimum_notice',
    ];

    protected $casts = [
        'screening_questions' => 'array',
        'duration' => 'integer',
        'buffer_time' => 'integer',
        'minimum_notice' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
