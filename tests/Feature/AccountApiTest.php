<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\FinancialAccount;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_accounts_use_the_workspace_currency(): void
    {
        $workspace = Workspace::factory()->create(['base_currency' => 'ALL']);
        Sanctum::actingAs($workspace->owner);

        $this->postJson("/api/v1/workspaces/{$workspace->id}/accounts", [
            'name' => 'Savings', 'type' => 'savings', 'opening_balance' => '5000', 'currency' => 'USD',
        ])->assertCreated()->assertJsonPath('data.currency', 'ALL');
    }

    public function test_the_last_account_cannot_be_removed(): void
    {
        $workspace = Workspace::factory()->create();
        $account = FinancialAccount::factory()->for($workspace)->create();
        Sanctum::actingAs($workspace->owner);

        $this->deleteJson("/api/v1/workspaces/{$workspace->id}/accounts/{$account->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A workspace needs at least one account.');
    }

    public function test_a_contributor_cannot_manage_accounts(): void
    {
        $workspace = Workspace::factory()->create();
        Sanctum::actingAs($this->memberOf($workspace, WorkspaceRole::Contributor));

        $this->postJson("/api/v1/workspaces/{$workspace->id}/accounts", [
            'name' => 'Savings', 'type' => 'savings', 'opening_balance' => '0',
        ])->assertForbidden();
    }
}
