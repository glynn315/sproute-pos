<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Products\DTOs\CreateProductsDTO;
use App\Domain\Products\DTOs\UpdateProductDTO;
use App\Domain\Products\Repositories\ProductsRepository;
use App\Domain\Products\Services\ProductsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\CreateProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductsService    $productService,
        private readonly ProductsRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only(['search', 'category_id', 'is_active']);
        $perPage  = min((int) $request->get('per_page', 20), 100);
        $products = $this->repo->paginateForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(ProductResource::collection($products));
    }

    public function pricelist(Request $request): JsonResponse
    {
        $products = $this->repo->allActiveForTenant($request->user()->tenant_id);

        return $this->success(ProductResource::collection($products));
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(CreateProductsDTO::fromRequest($request));

        return $this->created(new ProductResource($product), 'Product created');
    }

    public function show(Request $request, int $product): JsonResponse
    {
        $prod = $this->repo->findForTenant($request->user()->tenant_id, $product);

        if (! $prod) {
            return $this->notFound('Product not found');
        }

        return $this->success(new ProductResource($prod->load('category')));
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $prod = $this->repo->findForTenant($request->user()->tenant_id, $product);

        if (! $prod) {
            return $this->notFound('Product not found');
        }

        $updated = $this->productService->update($prod, UpdateProductDTO::fromRequest($request));

        return $this->success(new ProductResource($updated), 'Product updated');
    }

    public function destroy(Request $request, int $product): JsonResponse
    {
        if (! $request->user()->canAdminTenant()) {
            return $this->forbidden();
        }

        $prod = $this->repo->findForTenant($request->user()->tenant_id, $product);

        if (! $prod) {
            return $this->notFound('Product not found');
        }

        $this->productService->delete($prod);

        return $this->noContent('Product deactivated');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $products = $this->repo->lowStockForTenant($request->user()->tenant_id);

        return $this->success(ProductResource::collection($products));
    }
}
