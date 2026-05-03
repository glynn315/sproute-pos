<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\BillingService;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\TenantResource;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-facing subscription management.
 *
 * Free plans are applied immediately; paid plans issue a pending invoice
 * the user must settle through the billing flow before the plan activates.
 */
class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BillingService $billing) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return $this->success(SubscriptionPlanResource::collection($plans));
    }

    public function current(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant->load('subscriptionPlan');

        return $this->success(new TenantResource($tenant));
    }

    public function change(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->canManage()) {
            return $this->error('You do not have permission to change the subscription.', 403);
        }

        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::where('id', $data['subscription_plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return $this->error('Selected plan is not available.', 422);
        }

        // Block plan changes while an invoice is awaiting payment or
        // verification. The tenant must settle, cancel, or have admin reject
        // the existing invoice before picking another plan.
        $openInvoice = Invoice::with('subscriptionPlan')
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_SUBMITTED])
            ->latest('id')
            ->first();

        if ($openInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an invoice awaiting payment or verification.',
                'data'    => (new InvoiceResource($openInvoice))->resolve(),
            ], 409);
        }

        $invoice = $this->billing->issueInvoice($user->tenant, $plan);

        // Free plan → applied immediately; surface the refreshed tenant.
        if ($invoice === null) {
            return $this->success(
                new TenantResource($user->tenant->fresh('subscriptionPlan')),
                'Subscription updated',
            );
        }

        // Paid plan → return the invoice; the frontend routes the user to
        // the billing screen to complete payment.
        return $this->success(
            new InvoiceResource($invoice->load('subscriptionPlan')),
            'Invoice issued — pay to activate plan',
            202,
        );
    }
}
