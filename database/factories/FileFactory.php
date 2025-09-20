<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\File;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'project_id' => Project::factory(),
            'original_name' => $this->faker->fileName(),
            'storage_path' => 'account-' . $this->faker->randomNumber(3) . '/document-' . $this->faker->uuid() . '.pdf',
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png', 'text/plain']),
            'size' => $this->faker->numberBetween(1024, 1048576), // 1KB to 1MB
            'metadata' => [
                'uploaded_by_factory' => true,
                'test_data' => true,
            ],
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'original_name' => $this->faker->words(2, true) . '.pdf',
        ]);
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png']),
            'original_name' => $this->faker->words(2, true) . '.jpg',
        ]);
    }

    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => $this->faker->numberBetween(5242880, 52428800), // 5MB to 50MB
        ]);
    }
}
