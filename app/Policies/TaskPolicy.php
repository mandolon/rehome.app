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
     * Admin|team in same account; client only if (allow_client=true AND project member)
     */
    public function view(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Admin|team can see all tasks in their account
        if ($user->isTeamMember()) {
            return true;
        }

        // Client can only see tasks if:
        // 1. Task allows client visibility (allow_client=true)
        // 2. Client is the project member (project owner)
        if ($user->isClient()) {
            return $task->allow_client && 
                   $task->project->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create tasks.
     * Admin|team in same account only
     */
    public function create(User $user, Project $project): bool
    {
        return $user->isTeamMember() && 
               $user->account_id === $project->account_id;
    }

    /**
     * Determine whether the user can update the task.
     * Admin|team in same account only
     */
    public function update(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Only admin|team members can update tasks
        return $user->isTeamMember();
    }

    /**
     * Determine whether the user can delete the task.
     * Admin|team in same account only
     */
    public function delete(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Only admin|team members can delete tasks
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
     * Admin|team; client only if allow_client=true
     */
    public function comment(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Admin|team can comment on all tasks
        if ($user->isTeamMember()) {
            return true;
        }

        // Client can comment only if task allows client visibility
        if ($user->isClient()) {
            return $task->allow_client && 
                   $task->project->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can attach files to the task.
     * Admin|team; client only if allow_client=true
     */
    public function attachFile(User $user, Task $task): bool
    {
        // Must be in same account
        if ($user->account_id !== $task->project->account_id) {
            return false;
        }

        // Admin|team can attach files to all tasks
        if ($user->isTeamMember()) {
            return true;
        }

        // Client can attach files only if task allows client visibility
        if ($user->isClient()) {
            return $task->allow_client && 
                   $task->project->user_id === $user->id;
        }

        return false;
    }
}
