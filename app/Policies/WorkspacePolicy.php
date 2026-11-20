<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

/**
 * Every workspace-scoped API request is authorized here against the user's membership role.
 * Non-members get a 404 so they cannot learn that another workspace exists.
 */
class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): Response
    {
        return $this->check($user, $workspace, fn (WorkspaceRole $role) => true);
    }

    public function update(User $user, Workspace $workspace): Response
    {
        return $this->check($user, $workspace, fn (WorkspaceRole $role) => $role->canManageWorkspace());
    }

    public function delete(User $user, Workspace $workspace): Response
    {
        return $this->check($user, $workspace, fn (WorkspaceRole $role) => $role === WorkspaceRole::Owner);
    }

    public function manageFinances(User $user, Workspace $workspace): Response
    {
        return $this->check($user, $workspace, fn (WorkspaceRole $role) => $role->canManageFinances());
    }

    public function recordTransactions(User $user, Workspace $workspace): Response
    {
        return $this->check($user, $workspace, fn (WorkspaceRole $role) => $role->canRecordTransactions());
    }

    /**
     * @param  callable(WorkspaceRole): bool  $allowed
     */
    private function check(User $user, Workspace $workspace, callable $allowed): Response
    {
        $role = $user->roleIn($workspace);

        if ($role === null) {
            return Response::denyAsNotFound();
        }

        return $allowed($role) ? Response::allow() : Response::deny('Your role in this workspace does not allow this.');
    }
}
