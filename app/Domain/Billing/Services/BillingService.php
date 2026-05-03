<?php

namespace App\Domain\Billing\Services;

use App\Domain\Tenants\Services\TenantService;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Traits\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingService
{
    use AuditLogger;

    public function __construct(private readonly TenantService $tenants) {}

    /**
     * Issue a pending invoice for the given plan. Free plans (price 0) skip
     * the invoice and are applied directly via TenantService.
     */
    public function issueInvoice(Tenant $tenant, SubscriptionPlan $plan): ?Invoice
    {
        if ((float) $plan->price <= 0) {
            $this->applyPlan($tenant, $plan);
            return null;
        }

        $dueIn = (int) config('billing.invoice.due_in_days', 7);

        $invoice = Invoice::create([
            'tenant_id'            => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'invoice_number'       => $this->nextInvoiceNumber(),
            'amount'               => $plan->price,
            'billing_cycle'        => $plan->billing_cycle,
            'status'               => Invoice::STATUS_PENDING,
            'payment_method'       => 'gcash',
            'gcash_number'         => config('billing.gcash.number'),
            'due_at'               => now()->addDays($dueIn),
        ]);

        $this->auditModel('invoice_issued', $invoice);

        return $invoice;
    }

    /**
     * Tenant-side: record the GCash reference. The invoice moves to
     * STATUS_SUBMITTED and stays there until an admin verifies — the
     * subscription plan is NOT applied yet.
     */
    public function submitReference(Invoice $invoice, string $reference): Invoice
    {
        if (! in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_SUBMITTED], true)) {
            throw new RuntimeException('Invoice is not awaiting payment.');
        }

        $invoice->update([
            'reference_number' => $reference,
            'status'           => Invoice::STATUS_SUBMITTED,
        ]);

        $this->auditModel('invoice_submitted', $invoice->fresh());
        return $invoice->fresh('subscriptionPlan');
    }

    /**
     * Admin-side: verify the submitted payment. If $actualReference is
     * provided it must match the reference the tenant submitted; otherwise
     * the verification is rejected. On success the plan is applied.
     */
    public function verifyPayment(Invoice $invoice, ?string $actualReference = null): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_SUBMITTED) {
            throw new RuntimeException('Only submitted invoices can be verified.');
        }

        $submitted = (string) ($invoice->reference_number ?? '');
        if ($actualReference !== null) {
            $a = $this->normaliseRef($actualReference);
            $b = $this->normaliseRef($submitted);
            if ($a === '' || $a !== $b) {
                throw new RuntimeException(
                    "Reference does not match. Tenant submitted '{$submitted}'."
                );
            }
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status'  => Invoice::STATUS_PAID,
                'paid_at' => now(),
            ]);

            if ($invoice->subscription_plan_id && $invoice->tenant) {
                $this->applyPlan(
                    $invoice->tenant,
                    SubscriptionPlan::findOrFail($invoice->subscription_plan_id),
                );
            }

            $this->auditModel('invoice_verified', $invoice->fresh());
            return $invoice->fresh(['subscriptionPlan', 'tenant']);
        });
    }

    /**
     * Admin-side: reject a submitted payment (e.g. reference doesn't match
     * any GCash transaction). Status returns to pending so the tenant can
     * resubmit, and the reason is stored in notes.
     */
    public function rejectPayment(Invoice $invoice, ?string $reason = null): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_SUBMITTED) {
            throw new RuntimeException('Only submitted invoices can be rejected.');
        }

        $invoice->update([
            'status'           => Invoice::STATUS_PENDING,
            'reference_number' => null,
            'notes'            => $reason ? trim($reason) : 'Reference did not match — please re-submit.',
        ]);

        $this->auditModel('invoice_rejected', $invoice->fresh());
        return $invoice->fresh('subscriptionPlan');
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_SUBMITTED], true)) {
            throw new RuntimeException('Only pending or submitted invoices can be cancelled.');
        }

        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);
        $this->auditModel('invoice_cancelled', $invoice->fresh());
        return $invoice->fresh();
    }

    /** Normalise a reference for comparison — strip whitespace/dashes, casefold. */
    private function normaliseRef(string $ref): string
    {
        return strtolower(preg_replace('/[\s\-]+/', '', $ref) ?? '');
    }

    private function applyPlan(Tenant $tenant, SubscriptionPlan $plan): void
    {
        $endsAt = $plan->billing_cycle === 'yearly'
            ? Carbon::now()->addYear()->toDateTimeString()
            : Carbon::now()->addMonth()->toDateTimeString();

        $this->tenants->assignSubscription($tenant, $plan->id, $endsAt);
    }

    private function nextInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $count = Invoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%s-%05d', $year, $count);
    }
}
