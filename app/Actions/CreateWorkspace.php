<?php

namespace App\Actions;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    /**
     * Starter categories by workspace type: [name, type, color].
     *
     * @var array<string, list<array{0: string, 1: CategoryType, 2: string}>>
     */
    private const STARTER_CATEGORIES = [
        'personal' => [
            ['Salary', CategoryType::Income, '#16a34a'],
            ['Other income', CategoryType::Income, '#65a30d'],
            ['Groceries', CategoryType::Expense, '#ea580c'],
            ['Dining out', CategoryType::Expense, '#f59e0b'],
            ['Housing', CategoryType::Expense, '#7c3aed'],
            ['Utilities', CategoryType::Expense, '#0891b2'],
            ['Transport', CategoryType::Expense, '#2563eb'],
            ['Health', CategoryType::Expense, '#db2777'],
            ['Shopping', CategoryType::Expense, '#c026d3'],
            ['Entertainment', CategoryType::Expense, '#e11d48'],
            ['Subscriptions', CategoryType::Expense, '#4f46e5'],
            ['Other expenses', CategoryType::Expense, '#64748b'],
        ],
        'business' => [
            ['Sales', CategoryType::Income, '#16a34a'],
            ['Services', CategoryType::Income, '#059669'],
            ['Other income', CategoryType::Income, '#65a30d'],
            ['Suppliers', CategoryType::Expense, '#ea580c'],
            ['Rent', CategoryType::Expense, '#7c3aed'],
            ['Salaries', CategoryType::Expense, '#2563eb'],
            ['Utilities', CategoryType::Expense, '#0891b2'],
            ['Marketing', CategoryType::Expense, '#db2777'],
            ['Software', CategoryType::Expense, '#4f46e5'],
            ['Transport', CategoryType::Expense, '#0284c7'],
            ['Taxes & fees', CategoryType::Expense, '#b91c1c'],
            ['Other expenses', CategoryType::Expense, '#64748b'],
        ],
    ];

    /**
     * @param  array{name: string, type: string, base_currency: string, timezone: string, country?: string|null, account_name?: string|null, opening_balance?: string|int|float|null}  $data
     */
    public function handle(User $owner, array $data): Workspace
    {
        return DB::transaction(function () use ($owner, $data): Workspace {
            $workspace = new Workspace([
                'name' => $data['name'],
                'type' => $data['type'],
                'base_currency' => strtoupper($data['base_currency']),
                'timezone' => $data['timezone'],
                'country' => isset($data['country']) ? strtoupper($data['country']) : null,
            ]);
            $workspace->owner()->associate($owner);
            $workspace->save();

            $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

            $type = WorkspaceType::from($data['type'])->value;

            foreach (self::STARTER_CATEGORIES[$type] as [$name, $categoryType, $color]) {
                $workspace->categories()->create([
                    'name' => $name,
                    'type' => $categoryType,
                    'color' => $color,
                ]);
            }

            $workspace->accounts()->create([
                'name' => $data['account_name'] ?? 'Main account',
                'type' => AccountType::Bank,
                'currency' => $workspace->base_currency,
                'opening_balance' => $data['opening_balance'] ?? 0,
            ]);

            return $workspace;
        });
    }
}
