<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenants\Repositories\TenantRepository;
use App\Domain\Tenants\Services\TenantService;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\TenantResource;
use App\Models\SubscriptionPlan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TenantService    $tenantService,
        private readonly TenantRepository $repo,
    ) {}

    // ─── Tenants ──────────────────────────────────────────────────────────────

    public function tenants(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        $perPage = min((int) $request->get('per_page', 20), 100);
        $tenants = $this->repo->paginate($perPage, $filters);

        return $this->success(TenantResource::collection($tenants));
    }

    public function showTenant(int $tenant): JsonResponse
    {
        $t = $this->repo->findByIdOrFail($tenant);

        return $this->success(new TenantResource($t));
    }

    public function verifyTenant(int $tenant): JsonResponse
    {
        $t       = $this->repo->findByIdOrFail($tenant);
        $updated = $this->tenantService->verify($t);

        return $this->success(new TenantResource($updated), 'Tenant verified');
    }

    public function suspendTenant(int $tenant): JsonResponse
    {
        $t       = $this->repo->findByIdOrFail($tenant);
        $updated = $this->tenantService->suspend($t);

        return $this->success(new TenantResource($updated), 'Tenant suspended');
    }

    public function assignSubscription(Request $request, int $tenant): JsonResponse
    {
        $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'ends_at'              => ['nullable', 'date'],
        ]);

        $t       = $this->repo->findByIdOrFail($tenant);
        $updated = $this->tenantService->assignSubscription(
            $t,
            $request->validated('subscription_plan_id'),
            $request->validated('ends_at')
        );

        return $this->success(new TenantResource($updated), 'Subscription assigned');
    }

    // ─── Subscription Plans ───────────────────────────────────────────────────

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::orderBy('price')->get();

        return $this->success(SubscriptionPlanResource::collection($plans));
    }

    public function createPlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:50', 'unique:subscription_plans,name'],
            'display_name'  => ['required', 'string', 'max:100'],
            'price'         => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'max_employees' => ['required', 'integer', 'min:1'],
            'max_products'  => ['required', 'integer', 'min:-1'],
            'features'      => ['nullable', 'array'],
        ]);

        $plan = SubscriptionPlan::create($data);

        return $this->created(new SubscriptionPlanResource($plan), 'Plan created');
    }

    public function updatePlan(Request $request, int $plan): JsonResponse
    {
        $p = SubscriptionPlan::findOrFail($plan);

        $data = $request->validate([
            'display_name'  => ['sometimes', 'string', 'max:100'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'in:monthly,yearly'],
            'max_employees' => ['sometimes', 'integer', 'min:1'],
            'max_products'  => ['sometimes', 'integer', 'min:-1'],
            'features'      => ['sometimes', 'nullable', 'array'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $p->update($data);

        return $this->success(new SubscriptionPlanResource($p->fresh()), 'Plan updated');
    }
}
