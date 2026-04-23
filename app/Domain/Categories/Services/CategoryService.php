<?php

namespace App\Domain\Categories\Services;

use App\Domain\Categories\DTOs\CreateCategoryDTO;
use App\Domain\Categories\Repositories\CategoryRepository;
use App\Models\Category;
use App\Traits\AuditLogger;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    use AuditLogger;

    public function __construct(private readonly CategoryRepository $repo) {}

    public function create(CreateCategoryDTO $dto): Category
    {
        $existing = Category::where('tenant_id', $dto->tenantId)
            ->where('name', $dto->name)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages(['name' => ['Category name already exists.']]);
        }

        $category = $this->repo->create([
            'tenant_id'   => $dto->tenantId,
            'name'        => $dto->name,
            'description' => $dto->description,
        ]);

        $this->auditModel('created', $category);

        return $category;
    }

    public function update(Category $category, string $name, ?string $description): Category
    {
        $old = $category->toArray();

        $conflict = Category::where('tenant_id', $category->tenant_id)
            ->where('name', $name)
            ->where('id', '!=', $category->id)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['name' => ['Category name already exists.']]);
        }

        $updated = $this->repo->update($category, array_filter(
            ['name' => $name, 'description' => $description],
            fn ($v) => $v !== null
        ));

        $this->auditModel('updated', $updated, $old);

        return $updated;
    }

    public function delete(Category $category): void
    {
        $this->audit('deleted', 'Category', $category->id, $category->toArray());
        $this->repo->delete($category);
    }
}
