<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Users can list projects within their account
    }

    public function view(User $user, Project $project): bool
    {
        // Must be in the same account
        if ($user->account_id !== $project->account_id) {
            return false;
        }

        // Admin and team members can view all projects
        if ($user->isTeamMember()) {
            return true;
        }

        // Clients can only view projects they own
        return $user->id === $project->user_id;
    }

    public function create(User $user): bool
    {
        // Only admin and team members can create projects
        return $user->isTeamMember();
    }

    public function update(User $user, Project $project): bool
    {
        // Must be in the same account
        if ($user->account_id !== $project->account_id) {
            return false;
        }

        // Admin and team members can update all projects
        if ($user->isTeamMember()) {
            return true;
        }

        // Clients cannot update projects
        return false;
    }

    public function delete(User $user, Project $project): bool
    {
        // Must be in the same account
        if ($user->account_id !== $project->account_id) {
            return false;
        }

        // Only admins can delete projects
        return $user->isAdmin();
    }

    public function uploadDocs(User $user, Project $project): bool
    {
        // Must be in the same account
        if ($user->account_id !== $project->account_id) {
            return false;
        }

        // Admin and team members can upload to any project
        if ($user->isTeamMember()) {
            return true;
        }

        // Clients can upload to their own projects
        return $user->id === $project->user_id;
    }
}
