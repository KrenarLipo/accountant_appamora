<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_with_transactions_must_be_reassigned_before_deletion(): void
    {
        $workspace = Workspace::factory()->create();
        $account = FinancialAccount::factory()->for($workspace)->create();
        $old = Category::factory()->for($workspace)->create(['name' => 'Old']);
        $new = Category::factory()->for($workspace)->create(['name' => 'New']);
        $transaction = Transaction::factory()->for($workspace)->create(['account_id' => $account->id, 'category_id' => $old->id]);
        Sanctum::actingAs($workspace->owner);
        $url = "/api/v1/workspaces/{$workspace->id}/categories/{$old->id}";

        $this->deleteJson($url)
            ->assertJsonValidationErrors(['reassign_to' => 'This category has transactions. Choose another category to move them to.']);
        $this->assertNotSoftDeleted($old);

        $this->deleteJson($url, ['reassign_to' => $new->id])->assertNoContent();

        $this->assertSoftDeleted($old);
        $this->assertSame($new->id, $transaction->fresh()->category_id);
    }

    public function test_an_unused_category_can_be_deleted_directly(): void
    {
        $workspace = Workspace::factory()->create();
        $category = Category::factory()->for($workspace)->create();
        Sanctum::actingAs($workspace->owner);

        $this->deleteJson("/api/v1/workspaces/{$workspace->id}/categories/{$category->id}")->assertNoContent();

        $this->assertSoftDeleted($category);
    }

    public function test_category_names_are_unique_per_type_within_a_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        Category::factory()->for($workspace)->create(['name' => 'Travel']);
        Category::factory()->create(['name' => 'Travel']);
        Sanctum::actingAs($workspace->owner);
        $url = "/api/v1/workspaces/{$workspace->id}/categories";

        $this->postJson($url, ['name' => 'Travel', 'type' => 'expense'])->assertJsonValidationErrors('name');
        $this->postJson($url, ['name' => 'Travel', 'type' => 'income'])->assertCreated();
    }

    public function test_listing_includes_usage_counts(): void
    {
        $workspace = Workspace::factory()->create();
        $account = FinancialAccount::factory()->for($workspace)->create();
        $category = Category::factory()->for($workspace)->create();
        Transaction::factory()->count(2)->for($workspace)->create(['account_id' => $account->id, 'category_id' => $category->id]);
        Sanctum::actingAs($workspace->owner);

        $this->getJson("/api/v1/workspaces/{$workspace->id}/categories")
            ->assertOk()
            ->assertJsonPath('data.0.transactions_count', 2);
    }
}
