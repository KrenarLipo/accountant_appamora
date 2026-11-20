<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_workspace_adds_the_owner_starter_categories_and_a_first_account(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/workspaces', [
            'name' => 'Home',
            'type' => 'personal',
            'base_currency' => 'eur',
            'timezone' => 'Europe/Tirane',
            'account_name' => 'Wallet',
            'opening_balance' => '150.50',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.base_currency', 'EUR')
            ->assertJsonPath('data.role', 'owner')
            ->assertJsonPath('data.permissions.delete_workspace', true);

        $workspace = Workspace::sole();
        $this->assertSame(WorkspaceRole::Owner, $user->roleIn($workspace));
        $this->assertTrue($workspace->categories()->where('name', 'Groceries')->exists());
        $this->assertDatabaseHas('financial_accounts', [
            'workspace_id' => $workspace->id,
            'name' => 'Wallet',
            'currency' => 'EUR',
            'opening_balance' => '150.5000',
        ]);
    }

    public function test_business_workspaces_get_business_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/workspaces', [
            'name' => 'Shop', 'type' => 'business', 'base_currency' => 'EUR', 'timezone' => 'Europe/Tirane',
        ])->assertCreated();

        $this->assertTrue(Workspace::sole()->categories()->where('name', 'Suppliers')->exists());
    }

    public function test_workspace_creation_validates_its_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/workspaces', ['type' => 'family', 'timezone' => 'Mars/Base'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'base_currency', 'timezone']);
    }

    public function test_users_only_see_workspaces_they_belong_to(): void
    {
        $mine = Workspace::factory()->create(['name' => 'Mine']);
        Workspace::factory()->create(['name' => 'Someone else']);
        Sanctum::actingAs($mine->owner);

        $this->getJson('/api/v1/workspaces')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_another_users_workspace_is_reported_as_not_found(): void
    {
        $other = Workspace::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/workspaces/{$other->id}")->assertNotFound();
        $this->getJson("/api/v1/workspaces/{$other->id}/dashboard")->assertNotFound();
        $this->getJson("/api/v1/workspaces/{$other->id}/transactions")->assertNotFound();
        $this->putJson("/api/v1/workspaces/{$other->id}", ['name' => 'Hacked', 'timezone' => 'UTC'])->assertNotFound();

        $this->assertNotSame('Hacked', $other->fresh()->name);
    }

    public function test_a_viewer_cannot_change_workspace_settings(): void
    {
        $workspace = Workspace::factory()->create(['name' => 'Original']);
        Sanctum::actingAs($this->memberOf($workspace, WorkspaceRole::Viewer));

        $this->putJson("/api/v1/workspaces/{$workspace->id}", ['name' => 'Renamed', 'timezone' => 'UTC'])
            ->assertForbidden();

        $this->assertSame('Original', $workspace->fresh()->name);
    }

    public function test_only_the_owner_can_delete_a_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        Sanctum::actingAs($this->memberOf($workspace, WorkspaceRole::Admin));
        $this->deleteJson("/api/v1/workspaces/{$workspace->id}")->assertForbidden();

        Sanctum::actingAs($workspace->owner);
        $this->deleteJson("/api/v1/workspaces/{$workspace->id}")->assertNoContent();
        $this->assertSoftDeleted($workspace);
    }
}
