<?php

namespace Tests\Unit;

use App\Enums\WorkspaceRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkspaceRoleTest extends TestCase
{
    /**
     * @return array<string, array{WorkspaceRole, bool, bool, bool}>
     */
    public static function permissionMatrix(): array
    {
        return [
            'owner' => [WorkspaceRole::Owner, true, true, true],
            'admin' => [WorkspaceRole::Admin, true, true, true],
            'finance manager' => [WorkspaceRole::FinanceManager, false, true, true],
            'contributor' => [WorkspaceRole::Contributor, false, false, true],
            'viewer' => [WorkspaceRole::Viewer, false, false, false],
        ];
    }

    #[DataProvider('permissionMatrix')]
    public function test_role_permissions(WorkspaceRole $role, bool $manageWorkspace, bool $manageFinances, bool $recordTransactions): void
    {
        $this->assertSame($manageWorkspace, $role->canManageWorkspace());
        $this->assertSame($manageFinances, $role->canManageFinances());
        $this->assertSame($recordTransactions, $role->canRecordTransactions());
    }
}
