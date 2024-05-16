<?php

namespace Namnb\Authorization\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    protected $action;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function before($user, $ability)
    {
        $this->action = $ability;
    }

    public function checkRole(User $user)
    {
        $resultCheck = false;
        if ($user->is_admin && $user->extendRoles->where('action', $this->action)->count() > 0) {
            $resultCheck = true;
        } else if (
            $user->support === 'admin' &&
            ((!$user->group_permission_id && $user->channelInfo?->groupPermission?->extendRoles->where('action', $this->action)->count() > 0) ||
                $user->groupPermission?->extendRoles->where('action', $this->action)->count() > 0)
        ) {
            $resultCheck = true;
        }

        return $resultCheck;
    }
}
