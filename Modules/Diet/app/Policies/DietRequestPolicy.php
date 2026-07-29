<?php

namespace Modules\Diet\app\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Entities\User;

class DietRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('diet_request');
    }
}
