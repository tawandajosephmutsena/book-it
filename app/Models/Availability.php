<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'type',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'specific_date' => 'date',
    ];

    /**
     * Get the team that owns the availability.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
