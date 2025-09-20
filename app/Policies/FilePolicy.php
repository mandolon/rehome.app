<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function view(User $user, File $file): bool
    {
        // Must be in the same account
        if ($user->account_id !== $file->account_id) {
            return false;
        }

        // Admin and team members can view all files
        if ($user->isTeamMember()) {
            return true;
        }

        // Clients can view files from projects they own
        if ($file->project) {
            return $user->id === $file->project->user_id;
        }

        return false;
    }

    public function download(User $user, File $file): bool
    {
        return $this->view($user, $file);
    }
}
