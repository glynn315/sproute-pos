<?php

namespace App\Domain\Expenses\Services;

use App\Domain\Expenses\DTOs\CreateExpenseDTO;
use App\Domain\Expenses\Repositories\ExpenseRepository;
use App\Models\Expense;
use App\Traits\AuditLogger;

class ExpenseService
{
    use AuditLogger;

    public function __construct(private readonly ExpenseRepository $repo) {}

    public function create(CreateExpenseDTO $dto): Expense
    {
        $expense = $this->repo->create([
            'tenant_id'    => $dto->tenantId,
            'user_id'      => $dto->userId,
            'category'     => $dto->category,
            'amount'       => $dto->amount,
            'description'  => $dto->description,
            'expense_date' => $dto->expenseDate,
            'receipt_url'  => $dto->receiptUrl,
        ]);

        $this->auditModel('created', $expense);

        return $expense->load('user:id,name');
    }

    public function update(Expense $expense, array $data): Expense
    {
        $old     = $expense->toArray();
        $updated = $this->repo->update($expense, $data);
        $this->auditModel('updated', $updated, $old);
        return $updated;
    }

    public function delete(Expense $expense): void
    {
        $this->audit('deleted', 'Expense', $expense->id, $expense->toArray());
        $this->repo->delete($expense);
    }
}
