<?php

namespace App\Domain\Expenses\DTOs;

use App\Http\Requests\Expenses\CreateExpenseRequest;

readonly class CreateExpenseDTO
{
    public function __construct(
        public int     $tenantId,
        public int     $userId,
        public string  $category,
        public float   $amount,
        public ?string $description,
        public string  $expenseDate,
        public ?string $receiptUrl,
    ) {}

    public static function fromRequest(CreateExpenseRequest $request): self
    {
        $user = auth()->user();

        return new self(
            tenantId:    $user->tenant_id,
            userId:      $user->id,
            category:    $request->validated('category'),
            amount:      (float) $request->validated('amount'),
            description: $request->validated('description'),
            expenseDate: $request->validated('expense_date'),
            receiptUrl:  $request->validated('receipt_url'),
        );
    }
}
