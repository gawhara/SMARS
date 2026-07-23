<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state. Users default to super_admin, which
     * bypasses every gate — matching the "full-access admin" that the bulk of
     * the feature tests assume. Use ->role('name') or ->withoutRole() to narrow.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => Role::firstOrCreate(
                ['name' => 'super_admin'],
                ['display_name_ar' => 'مدير النظام', 'display_name_en' => 'Super Admin', 'is_active' => true],
            )->id,
        ];
    }

    public function role(string $name): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['name' => $name],
                ['display_name_ar' => $name, 'display_name_en' => $name, 'is_active' => true],
            )->id,
        ]);
    }

    public function withoutRole(): static
    {
        return $this->state(fn () => ['role_id' => null]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
