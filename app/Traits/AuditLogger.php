<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait AuditLogger
{
    protected function audit(
        string $action,
        string $entityType,
        string|int|null $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $tenantId = null,
        ?int $userId = null,
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'tenant_id'   => $tenantId ?? $user?->tenant_id,
            'user_id'     => $userId   ?? $user?->id,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => (string) $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

    protected function auditModel(string $action, Model $model, ?array $oldValues = null): void
    {
        $this->audit(
            action:      $action,
            entityType:  class_basename($model),
            entityId:    $model->getKey(),
            oldValues:   $oldValues,
            newValues:   $action !== 'deleted' ? $model->toArray() : null,
        );
    }
}
