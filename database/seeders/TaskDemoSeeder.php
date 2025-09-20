<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskDemoSeeder extends Seeder
{
    /**
     * Seed demo tasks for development testing.
     */
    public function run(): void
    {
        // Create or get existing account
        $account = Account::firstOrCreate(
            ['name' => 'Demo Construction Co'],
            [
                'email' => 'demo@precon.test',
                'subscription_status' => 'active',
            ]
        );

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Admin User',
                'account_id' => $account->id,
                'role' => 'admin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        // Create team user
        $teamUser = User::firstOrCreate(
            ['email' => 'team@demo.test'],
            [
                'name' => 'Team Member',
                'account_id' => $account->id,
                'role' => 'team',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        // Create demo project with ID=1
        $project = Project::firstOrCreate(
            ['id' => 1],
            [
                'id' => 1,
                'name' => 'Demo Project',
                'account_id' => $account->id,
                'description' => 'Demo project for testing task management',
                'created_by_id' => $adminUser->id,
            ]
        );

        // Add team member to project
        $project->members()->syncWithoutDetaching([$teamUser->id]);

        // Create 8 demo tasks with mixed categories and statuses
        $categories = ['Task', 'Redline', 'Progress', 'Update'];
        $statuses = ['todo', 'in_progress', 'blocked', 'done'];
        
        $tasks = [
            [
                'title' => 'Electrical rough-in inspection',
                'description' => 'Inspect electrical rough-in work in master bedroom before drywall installation.',
                'category' => $categories[0], // Task
                'status' => $statuses[0], // todo
                'assignee_id' => $teamUser->id,
                'created_by_id' => $adminUser->id,
                'due_date' => now()->addDays(5),
            ],
            [
                'title' => 'Foundation inspection complete',
                'description' => 'Foundation inspection has been completed and passed all requirements.',
                'category' => $categories[2], // Progress
                'status' => $statuses[3], // done
                'assignee_id' => $adminUser->id,
                'created_by_id' => $adminUser->id,
                'due_date' => now()->subDays(2),
            ],
            [
                'title' => 'Plumbing fixture placement review',
                'description' => 'Review placement of all bathroom fixtures against architectural plans.',
                'category' => $categories[1], // Redline
                'status' => $statuses[1], // in_progress
                'assignee_id' => $teamUser->id,
                'created_by_id' => $teamUser->id,
                'due_date' => now()->addDays(10),
            ],
            [
                'title' => 'HVAC system installation progress',
                'description' => 'HVAC system installation is 75% complete. All ductwork installed.',
                'category' => $categories[3], // Update
                'status' => $statuses[1], // in_progress
                'assignee_id' => $adminUser->id,
                'created_by_id' => $teamUser->id,
                'due_date' => now()->addDays(7),
            ],
            [
                'title' => 'Roofing material delivery blocked',
                'description' => 'Roofing materials delayed due to supplier issues. Need alternative source.',
                'category' => $categories[0], // Task
                'status' => $statuses[2], // blocked
                'assignee_id' => $adminUser->id,
                'created_by_id' => $adminUser->id,
                'due_date' => now()->addDays(1),
            ],
            [
                'title' => 'Concrete pour quality check',
                'description' => 'Quality control inspection of concrete pour for garage foundation.',
                'category' => $categories[1], // Redline
                'status' => $statuses[0], // todo
                'assignee_id' => $teamUser->id,
                'created_by_id' => $adminUser->id,
                'due_date' => now()->addDays(3),
            ],
            [
                'title' => 'Insulation installation progress',
                'description' => 'Insulation installation in all exterior walls completed.',
                'category' => $categories[2], // Progress
                'status' => $statuses[3], // done
                'assignee_id' => $teamUser->id,
                'created_by_id' => $teamUser->id,
                'due_date' => now()->subDays(1),
            ],
            [
                'title' => 'Window installation schedule review',
                'description' => 'Review and update window installation schedule due to weather delays.',
                'category' => $categories[3], // Update
                'status' => $statuses[2], // blocked
                'assignee_id' => $adminUser->id,
                'created_by_id' => $teamUser->id,
                'due_date' => now()->addDays(2),
            ],
        ];

        foreach ($tasks as $taskData) {
            $task = Task::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'title' => $taskData['title'],
                ],
                array_merge($taskData, [
                    'project_id' => $project->id,
                    'allow_client' => rand(0, 1) === 1, // Random client visibility
                ])
            );

            // Create initial activity log
            $task->activities()->firstOrCreate(
                [
                    'task_id' => $task->id,
                    'action_type' => 'created',
                ],
                [
                    'user_id' => $taskData['created_by_id'],
                    'comment' => 'Task created',
                    'is_system' => true,
                ]
            );
        }

        $this->command->info('Demo tasks seeded successfully!');
        $this->command->info("Project ID: {$project->id}");
        $this->command->info("Admin User: {$adminUser->email} (password: password)");
        $this->command->info("Team User: {$teamUser->email} (password: password)");
        $this->command->info("Visit: /teams/tasks?project={$project->id}");
    }
}