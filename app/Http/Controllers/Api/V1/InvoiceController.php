<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\BillingService;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BillingService $billing) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage  = min((int) $request->get('per_page', 20), 100);

        $invoices = Invoice::with('subscriptionPlan')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success(InvoiceResource::collection($invoices));
    }

    public function show(Request $request, int $invoice): JsonResponse
    {
        $inv = Invoice::with('subscriptionPlan')
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($invoice);

        return $this->success(new InvoiceResource($inv));
    }

    public function submitReference(Request $request, int $invoice): JsonResponse
    {
        $user = $request->user();
        if (! $user?->canManage()) {
            return $this->error('You do not have permission to settle invoices.', 403);
        }

        $data = $request->validate([
            'reference_number' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        $inv = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($invoice);

        try {
            $updated = $this->billing->submitReference($inv, $data['reference_number']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new InvoiceResource($updated->load('subscriptionPlan')),
            'Reference submitted — awaiting verification',
        );
    }

    public function cancel(Request $request, int $invoice): JsonResponse
    {
        $user = $request->user();
        if (! $user?->canManage()) {
            return $this->error('You do not have permission to cancel invoices.', 403);
        }

        $inv = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($invoice);

        try {
            $updated = $this->billing->cancel($inv);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new InvoiceResource($updated), 'Invoice cancelled');
    }
}
