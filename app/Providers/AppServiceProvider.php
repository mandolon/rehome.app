<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Project;
use App\Models\Task;
use App\Models\File;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\FilePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(File::class, FilePolicy::class);

        // Define custom gates
        Gate::define('manage-tasks', function ($user, Project $project) {
            return $user->isTeamMember() && 
                   $user->account_id === $project->account_id;
        });

        Gate::define('view-task', function ($user, Task $task) {
            // Must be in same account
            if ($user->account_id !== $task->project->account_id) {
                return false;
            }

            // Clients can only see tasks marked as client-visible
            if ($user->isClient()) {
                return $task->allow_client && 
                       $task->project->user_id === $user->id;
            }

            // Team members can see all tasks in their account
            return $user->isTeamMember();
        });

        Gate::define('complete-task', function ($user, Task $task) {
            // Must be in same account
            if ($user->account_id !== $task->project->account_id) {
                return false;
            }

            // Task assignee can complete their own task
            if ($task->assignee_id === $user->id) {
                return true;
            }

            // Team members can complete any task
            return $user->isTeamMember();
        });
    }
}
