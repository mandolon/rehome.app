<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['TASK/REDLINE', 'PROGRESS/UPDATE']),
            'status' => $this->faker->randomElement(['open', 'complete']),
            'assignee_id' => null, // Will be set in tests
            'created_by_id' => User::factory(),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'allow_client' => $this->faker->boolean(30), // 30% chance of being client visible
            'files_count' => 0,
            'comments_count' => 0,
        ];
    }

    /**
     * Indicate that the task is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * Indicate that the task is complete.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'complete',
        ]);
    }

    /**
     * Indicate that the task is client visible.
     */
    public function clientVisible(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_client' => true,
        ]);
    }

    /**
     * Indicate that the task is a redline task.
     */
    public function redline(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'TASK/REDLINE',
        ]);
    }

    /**
     * Indicate that the task is a progress update.
     */
    public function progressUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'PROGRESS/UPDATE',
        ]);
    }

    /**
     * Indicate that the task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'status' => 'open',
        ]);
    }

    /**
     * Add some comments to the task.
     */
    public function withComments(int $count = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'comments_count' => $count,
        ]);
    }

    /**
     * Add some files to the task.
     */
    public function withFiles(int $count = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'files_count' => $count,
        ]);
    }
}
