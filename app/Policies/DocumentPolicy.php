<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): Response
    {
        return $this->ownedBy($user, $document);
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): Response
    {
        return $this->ownedBy($user, $document);
    }

    /**
     * Hide the existence of documents that belong to other users' projects.
     */
    private function ownedBy(User $user, Document $document): Response
    {
        return $user->id === $document->project->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
