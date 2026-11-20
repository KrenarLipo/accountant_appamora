<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case FinanceManager = 'finance_manager';
    case Contributor = 'contributor';
    case Viewer = 'viewer';

    /**
     * Members, workspace settings.
     */
    public function canManageWorkspace(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    /**
     * Accounts, categories, budgets.
     */
    public function canManageFinances(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::FinanceManager], true);
    }

    /**
     * Create, edit and delete transactions.
     */
    public function canRecordTransactions(): bool
    {
        return $this !== self::Viewer;
    }
}
