<?php

namespace Tests;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user who belongs to the workspace with the given role.
     */
    protected function memberOf(Workspace $workspace, WorkspaceRole $role): User
    {
        $user = User::factory()->create();
        $workspace->members()->attach($user, ['role' => $role->value]);

        return $user;
    }

    /**
     * Point outgoing email at a closed local port, like a host that blocks SMTP.
     */
    protected function breakMailDelivery(): void
    {
        config([
            'mail.default' => 'unreachable',
            'mail.mailers.unreachable' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 2],
        ]);
    }
}
