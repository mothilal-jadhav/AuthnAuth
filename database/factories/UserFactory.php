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
    protected static ?string $password;

    public function definition(): array
    {
        return [
            // Faker's raw name() can include punctuation (titles like "Mrs.",
            // suffixes like "Jr.", apostrophes/hyphens in surnames) that
            // fails the app's strict name-validation regex
            // (HasNameEmailRules::nameRules(), letters/spaces only) whenever
            // a test reuses a factory-generated name in a request payload —
            // an intermittent failure that's recurred several times across
            // this test suite. Stripping anything the regex wouldn't accept
            // removes the flakiness at its source instead of patching each
            // test that happens to trip over it.
            'name' => trim((string) preg_replace('/[^a-zA-Z\s]/', '', fake()->name())),

            'email' => fake()->unique()->safeEmail(),

            'email_verified_at' => now(),

            'password' => static::$password ??= Hash::make('password'),

            'remember_token' => Str::random(10),

            'role_id' => function () {
                return Role::firstOrCreate([
                    'name' => 'user',
                ])->id;
            },
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
