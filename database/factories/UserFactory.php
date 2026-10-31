<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $idFaker = fake('id_ID');
        $name = $idFaker->name();
        $safeName = Str::slug($name, '.');
        $email = $safeName . '.' . fake()->unique()->numberBetween(10, 9999) . '@wirodev.com';

        return [
            'name' => $name,
            'email' => $email,
            'personal_email' => $idFaker->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('wirodev2026'),
            'role' => null,
            'client_id' => null,
            'is_active' => true,
            'must_change_password' => false,
            'last_login_at' => fake()->boolean(80) ? now()->subMinutes(fake()->numberBetween(5, 43200)) : null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure model hooks.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->role) {
                $user->syncRoles([$user->role]);
            }
        });
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

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'superadmin',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function pm(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'pm',
        ]);
    }

    public function finance(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'finance',
        ]);
    }

    public function qc(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'qc',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'staff',
        ]);
    }

    public function client(?int $clientId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'client',
            'client_id' => $clientId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'must_change_password' => true,
        ]);
    }
}
