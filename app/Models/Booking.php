<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'event_type_id',
        'uuid',
        'guest_name',
        'guest_email',
        'guest_timezone',
        'start_time',
        'end_time',
        'meet_link',
        'status',
        'lead_data',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->uuid)) {
                $booking->uuid = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'lead_data' => 'array',
    ];

    /**
     * Get the team that owns the booking.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who is booked.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event type that this booking belongs to.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function getPhoneAttribute()
    {
        return $this->lead_data['phone'] ?? null;
    }

    public function getCompanyAttribute()
    {
        return $this->lead_data['company'] ?? null;
    }

    public function getIndustryAttribute()
    {
        return $this->lead_data['industry'] ?? null;
    }

    public function getProjectBriefAttribute()
    {
        return $this->lead_data['project_brief'] ?? null;
    }

    public function getNotesAttribute()
    {
        return $this->lead_data['notes'] ?? null;
    }

    public function isNew()
    {
        return $this->created_at && $this->created_at->diffInHours(now()) < 24;
    }
}
