<?php

namespace App\Http\Resources;

use App\Contracts\ExplainsAlerts;
use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workspace
 */
class WorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->pivot?->role ?? $request->user()?->roleIn($this->resource)?->value;
        $roleEnum = $role ? WorkspaceRole::from($role) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'base_currency' => $this->base_currency,
            'timezone' => $this->timezone,
            'country' => $this->country,
            'ai_insights_enabled' => $this->ai_insights_enabled,
            'ai_available' => app(ExplainsAlerts::class)->isAvailable(),
            'role' => $role,
            'permissions' => [
                'manage_workspace' => $roleEnum?->canManageWorkspace() ?? false,
                'manage_finances' => $roleEnum?->canManageFinances() ?? false,
                'record_transactions' => $roleEnum?->canRecordTransactions() ?? false,
                'delete_workspace' => $roleEnum === WorkspaceRole::Owner,
            ],
        ];
    }
}
