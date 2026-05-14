<?php

namespace App\Domain\Modules\Repositories;

use App\Domain\Modules\Models\Module;
use Illuminate\Database\Eloquent\Collection;

class ModuleRepository
{
    public function all(bool $activeOnly = false): Collection
    {
        $q = Module::query()->orderBy('sort_order')->orderBy('name');
        if ($activeOnly) $q->active();
        return $q->get();
    }

    public function find(int $id): ?Module
    {
        return Module::find($id);
    }

    public function findOrFail(int $id): Module
    {
        return Module::findOrFail($id);
    }

    public function findByName(string $name): ?Module
    {
        return Module::where('name', $name)->first();
    }

    public function activeSlugs(): array
    {
        return Module::active()->pluck('name')->all();
    }

    public function allSlugs(): array
    {
        return Module::pluck('name')->all();
    }

    public function create(array $data): Module
    {
        return Module::create($data);
    }
}
