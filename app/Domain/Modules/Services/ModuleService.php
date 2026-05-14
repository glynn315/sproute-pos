<?php

namespace App\Domain\Modules\Services;

use App\Domain\Modules\DTOs\StoreModuleDTO;
use App\Domain\Modules\DTOs\UpdateModuleDTO;
use App\Domain\Modules\Models\Module;
use App\Domain\Modules\Repositories\ModuleRepository;
use App\Models\Tenant;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleService
{
    use AuditLogger;

    public function __construct(private readonly ModuleRepository $repo) {}

    public function create(StoreModuleDTO $dto): Module
    {
        if ($this->repo->findByName($dto->name)) {
            throw ValidationException::withMessages([
                'name' => ["Module '{$dto->name}' already exists."],
            ]);
        }

        $module = $this->repo->create($dto->toArray());
        $this->audit('created', 'Module', $module->module_id, null, $module->toArray());
        return $module;
    }

    public function update(Module $module, UpdateModuleDTO $dto): Module
    {
        $old = $module->toArray();
        $patch = $dto->toArray();
        if (! empty($patch)) {
            $module->fill($patch)->save();
        }
        $this->audit('updated', 'Module', $module->module_id, $old, $module->fresh()->toArray());
        return $module->fresh();
    }

    /**
     * Soft-delete a module. We do NOT strip it from tenants that have it
     * enabled — historical access is preserved. The middleware will still
     * grant access to tenants that already have the slug in their modules
     * array, but new tenants can't enable it (because validation reads from
     * the active registry).
     *
     * If you want a hard purge that also disables it on every tenant, call
     * detachFromAllTenants() first.
     */
    public function destroy(Module $module): void
    {
        $old = $module->toArray();
        $module->delete();
        $this->audit('deleted', 'Module', $old['module_id'] ?? null, $old, null);
    }

    public function detachFromAllTenants(Module $module): int
    {
        return DB::transaction(function () use ($module) {
            $count = 0;
            Tenant::query()->chunkById(200, function ($tenants) use ($module, &$count) {
                foreach ($tenants as $t) {
                    if ($t->hasModule($module->name)) {
                        $t->disableModule($module->name);
                        $count++;
                    }
                }
            });
            $this->audit('detached_all', 'Module', $module->module_id, null, ['detached_tenant_count' => $count]);
            return $count;
        });
    }
}
