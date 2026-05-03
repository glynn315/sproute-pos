<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\BillingService;
use App\Domain\Tenants\Repositories\TenantRepository;
use App\Domain\Tenants\Services\TenantService;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\TenantResource;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TenantService    $tenantService,
        private readonly TenantRepository $repo,
        private readonly BillingService   $billing,
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

    // ─── Invoices (verification & monitoring) ─────────────────────────────────

    public function invoices(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = Invoice::with(['subscriptionPlan', 'tenant'])->latest('id');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($tenantId = $request->get('tenant_id')) {
            $query->where('tenant_id', (int) $tenantId);
        }

        return $this->success(InvoiceResource::collection($query->paginate($perPage)));
    }

    /**
     * Verify a submitted invoice. The admin pastes the actual GCash
     * reference they see in the merchant app — the service compares it to
     * what the tenant submitted before activating the plan.
     */
    public function verifyInvoice(Request $request, int $invoice): JsonResponse
    {
        $data = $request->validate([
            'actual_reference_number' => ['nullable', 'string', 'max:64'],
        ]);

        $inv = Invoice::with('subscriptionPlan')->findOrFail($invoice);

        try {
            $updated = $this->billing->verifyPayment($inv, $data['actual_reference_number'] ?? null);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new InvoiceResource($updated), 'Payment verified');
    }

    public function rejectInvoice(Request $request, int $invoice): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $inv = Invoice::with('subscriptionPlan')->findOrFail($invoice);

        try {
            $updated = $this->billing->rejectPayment($inv, $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new InvoiceResource($updated), 'Payment rejected');
    }
}
