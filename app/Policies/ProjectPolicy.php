<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): Response
    {
        return $this->ownedBy($user, $project);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): Response
    {
        return $this->ownedBy($user, $project);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): Response
    {
        return $this->ownedBy($user, $project);
    }

    /**
     * Hide the existence of projects owned by other users.
     */
    private function ownedBy(User $user, Project $project): Response
    {
        return $user->id === $project->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
