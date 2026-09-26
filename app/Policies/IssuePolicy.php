<?php
// app/Policies/IssuePolicy.php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('issue.view');
    }

    public function view(User $user, Issue $issue): bool
    {
        return $user->can('issue.view');
    }

    public function create(User $user): bool
    {
        return $user->can('issue.create');
    }

    /** Transisi workflow (investigate/resolve/reject/close) */
    public function transition(User $user, Issue $issue): bool
    {
        return $user->can('issue.resolve');
    }
}
