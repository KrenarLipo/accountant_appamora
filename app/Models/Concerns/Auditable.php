<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;

/**
 * Records who created, changed or deleted a workspace record.
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->writeAuditLog('created', null, $model->auditableAttributes()));

        static::updated(function (Model $model): void {
            $changed = array_keys(Arr::except($model->getChanges(), ['updated_at', 'updated_by']));

            if ($changed === []) {
                return;
            }

            $model->writeAuditLog(
                'updated',
                Arr::only($model->getOriginal(), $changed),
                Arr::only($model->auditableAttributes(), $changed),
            );
        });

        static::deleted(fn (Model $model) => $model->writeAuditLog('deleted', $model->auditableAttributes(), null));
    }

    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditableAttributes(): array
    {
        return Arr::except($this->attributesToArray(), ['created_at', 'updated_at', 'deleted_at']);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    protected function writeAuditLog(string $action, ?array $before, ?array $after): void
    {
        $this->auditLogs()->create([
            'workspace_id' => $this->getAttribute('workspace_id'),
            'actor_id' => auth()->id(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
