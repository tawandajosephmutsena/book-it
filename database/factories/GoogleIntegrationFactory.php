<?php

namespace Database\Factories;

use App\Models\GoogleIntegration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoogleIntegration>
 */
class GoogleIntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'google_id' => fake()->unique()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'access_token' => 'ya29.test-access-token-'.fake()->sha256(),
            'refresh_token' => '1//test-refresh-token-'.fake()->sha256(),
            'expires_in' => 3600,
        ];
    }

    /**
     * Indicate that the token has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_in' => 0,
        ])->afterCreating(function (GoogleIntegration $integration) {
            // Force updated_at into the past so the token appears expired
            $integration->forceFill(['updated_at' => now()->subHours(2)])->saveQuietly();
        });
    }

    /**
     * Indicate that no refresh token is available.
     */
    public function withoutRefreshToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'refresh_token' => null,
        ]);
    }
}
