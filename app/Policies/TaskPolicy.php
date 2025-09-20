<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use App\Models\Project;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return $user->isTeamMember() || $user->isClient();
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
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
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->isTeamMember() && 
               $user->account_id === $project->account_id;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Only team members can update tasks
        return $user->isTeamMember();
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Only team members can delete tasks
        return $user->isTeamMember();
    }

    /**
     * Determine whether the user can complete the task.
     */
    public function complete(User $user, Task $task): bool
    {
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
    }

    /**
     * Determine whether the user can comment on the task.
     */
    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Determine whether the user can attach files to the task.
     */
    public function attachFile(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Only team members can attach files
        return $user->isTeamMember();
    }
}
