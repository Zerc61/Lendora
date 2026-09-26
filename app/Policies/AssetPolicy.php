<?php
// app/Policies/AssetPolicy.php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('asset.view'); // semua role boleh browse (termasuk borrower)
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('asset.view');
    }

    public function create(User $user): bool
    {
        return $user->can('asset.create');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('asset.update');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('asset.delete');
    }
}