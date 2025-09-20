<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'slug' => Str::slug($this->faker->unique()->company),
            'plan' => $this->faker->randomElement(['free', 'pro', 'enterprise']),
            'settings' => [
                'timezone' => $this->faker->timezone,
                'default_language' => 'en',
            ],
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }
}
