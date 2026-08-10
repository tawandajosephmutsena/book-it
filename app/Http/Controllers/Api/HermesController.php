<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class HermesController extends Controller
{
    /**
     * Get availability slots.
     */
    public function availability(Request $request)
    {
        // Simple implementation: retrieve availabilities.
        // In a real scenario, this would filter by date and resolve against existing bookings and Google Calendar.
        $availabilities = Availability::where('is_available', true)->get();

        return response()->json([
            'status' => 'success',
            'data' => $availabilities,
        ]);
    }

    /**
     * Book a slot.
     */
    public function book(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id',
            'guest_name' => 'required|string',
            'guest_email' => 'required|email',
            'guest_timezone' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'lead_data' => 'nullable|array',
        ]);

        $booking = Booking::create(array_merge($validated, [
            'status' => 'confirmed',
        ]));

        NotificationService::trigger($booking);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking created successfully.',
            'data' => $booking,
        ], 201);
    }
}
