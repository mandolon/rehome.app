<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;

class TaskPolicy
{
    /**
     * Collection authorization (used in index + create)
     * Admin: can see everything in account
     * Team: can only access projects they are assigned to
     * Client: no access to team board
     */
    public function viewAny(User $user, Project $project): bool
    {
        $role = $user->roleIn($project->account);

        if ($role === 'admin') return true;

        // team can only access projects they are assigned to
        if ($role === 'team') {
            return $project->members()->where('user_id', $user->id)->exists();
        }

        return false; // clients not allowed on team board
    }

    /**
     * Single task authorization
     * Admin: can see everything in account
     * Team: can see only tasks assigned to them or ones they created
     * Client: no access to team board
     */
    public function view(User $user, Task $task): bool
    {
        $role = $user->roleIn($task->project->account);

        if ($role === 'admin') return true;

        if ($role === 'team') {
            // see only tasks assigned to them (or ones they created)
            return $task->assignee_id === $user->id
                || $task->created_by_id === $user->id;
        }

        return false; // clients not allowed
    }

    /**
     * Task creation authorization
     * Admin: can create anywhere in account
     * Team: can create only on projects they're assigned to
     * Client: no creation access
     */
    public function create(User $user, Project $project): bool
    {
        $role = $user->roleIn($project->account);
        
        if ($role === 'admin') return true;

        // allow team to create tasks only on projects they're assigned to
        if ($role === 'team') {
            return $project->members()->where('user_id', $user->id)->exists();
        }
        
        return false; // clients cannot create
    }

    /**
     * Task update authorization
     * Admin: can update everything in account
     * Team: can update only their own tasks (assignee or creator)
     * Client: no update access
     */
    public function update(User $user, Task $task): bool
    {
        $role = $user->roleIn($task->project->account);
        
        if ($role === 'admin') return true;

        // team can update only their own tasks (assignee or creator)
        if ($role === 'team') {
            return $task->assignee_id === $user->id
                || $task->created_by_id === $user->id;
        }
        
        return false; // clients cannot update
    }

    /**
     * Task deletion authorization
     * Admin: can delete everything in account
     * Team/Client: no deletion access (admin-only operation)
     */
    public function delete(User $user, Task $task): bool
    {
        $role = $user->roleIn($task->project->account);
        
        if ($role === 'admin') return true;

        // usually only admin deletes; strict rule
        return false;
    }

    /**
     * Task completion authorization
     * Admin: can complete everything in account
     * Team: can complete only their assigned tasks or ones they created
     * Client: no completion access
     */
    public function complete(User $user, Task $task): bool
    {
        $role = $user->roleIn($task->project->account);
        
        if ($role === 'admin') return true;

        if ($role === 'team') {
            return $task->assignee_id === $user->id
                || $task->created_by_id === $user->id;
        }
        
        return false;
    }

    /**
     * File attachment authorization
     * Admin: can attach files anywhere in account
     * Team: can attach only to their tasks
     * Client: no file attachment access
     */
    public function attachFile(User $user, Task $task): bool
    {
        $role = $user->roleIn($task->project->account);
        
        if ($role === 'admin') return true;
        
        if ($role === 'team') {
            return $task->assignee_id === $user->id
                || $task->created_by_id === $user->id;
        }
        
        return false; // clients cannot attach files
    }

    /**
     * Comment authorization
     * Admin: can comment anywhere in account
     * Team: can comment only on their tasks
     * Client: no comment access
     */
    public function comment(User $user, Task $task): bool
    {
        $role = $user->roleIn($task->project->account);
        
        if ($role === 'admin') return true;
        
        if ($role === 'team') {
            return $task->assignee_id === $user->id
                || $task->created_by_id === $user->id;
        }
        
        return false; // clients cannot comment
    }
}
