<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Categories\DTOs\CreateCategoryDTO;
use App\Domain\Categories\Repositories\CategoryRepository;
use App\Domain\Categories\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\CreateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CategoryService    $categoryService,
        private readonly CategoryRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->repo->allForTenant($request->user()->tenant_id);

        return $this->success(CategoryResource::collection($categories));
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create(CreateCategoryDTO::fromRequest($request));

        return $this->created(new CategoryResource($category), 'Category created');
    }

    public function update(CreateCategoryRequest $request, int $category): JsonResponse
    {
        $cat = $this->repo->findForTenant($request->user()->tenant_id, $category);

        if (! $cat) {
            return $this->notFound('Category not found');
        }

        $updated = $this->categoryService->update(
            $cat,
            $request->validated('name'),
            $request->validated('description')
        );

        return $this->success(new CategoryResource($updated), 'Category updated');
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden();
        }

        $cat = $this->repo->findForTenant($request->user()->tenant_id, $category);

        if (! $cat) {
            return $this->notFound('Category not found');
        }

        $this->categoryService->delete($cat);

        return $this->noContent('Category deleted');
    }
}
