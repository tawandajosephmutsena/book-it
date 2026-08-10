<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('+1 day', '+2 weeks');

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_timezone' => 'UTC',
            'start_time' => $startTime,
            'end_time' => (clone $startTime)->modify('+1 hour'),
            'status' => 'confirmed',
        ];
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Set the team for the booking.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $team->id,
        ]);
    }

    /**
     * Set the booking time.
     */
    public function at(string $date, string $time): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => "{$date} {$time}:00",
            'end_time' => "{$date} ".Carbon::parse($time)->addHour()->format('H:i:s'),
        ]);
    }
}
