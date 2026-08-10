<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'tawanda@ottomate.space'],
            [
                'name' => 'Tawanda (Super Admin)',
                'password' => Hash::make('Mhondoro716.'),
            ]
        );

        $team = Team::firstOrCreate(
            ['slug' => 'ottomate-space'],
            [
                'name' => 'Ottomate Space',
                'is_personal' => false,
            ]
        );

        // Attach user to team as owner (role 'admin' or 'owner' depending on Jetstream/Breeze setup)
        // Usually handled by team_members pivot or similar, but the models use `members()` with `role`.
        if (! $team->members->contains($user)) {
            $team->members()->attach($user, ['role' => 'owner']);
        }

        $user->current_team_id = $team->id;
        $user->save();

        // Also seed default availability for the team
        $team->availabilities()->firstOrCreate(
            ['day_of_week' => 1], // Monday
            ['type' => 'recurring', 'start_time' => '09:00:00', 'end_time' => '17:00:00']
        );
        $team->availabilities()->firstOrCreate(
            ['day_of_week' => 2], // Tuesday
            ['type' => 'recurring', 'start_time' => '09:00:00', 'end_time' => '17:00:00']
        );
        $team->availabilities()->firstOrCreate(
            ['day_of_week' => 3], // Wednesday
            ['type' => 'recurring', 'start_time' => '09:00:00', 'end_time' => '17:00:00']
        );
        $team->availabilities()->firstOrCreate(
            ['day_of_week' => 4], // Thursday
            ['type' => 'recurring', 'start_time' => '09:00:00', 'end_time' => '17:00:00']
        );
        $team->availabilities()->firstOrCreate(
            ['day_of_week' => 5], // Friday
            ['type' => 'recurring', 'start_time' => '09:00:00', 'end_time' => '17:00:00']
        );
    }
}
