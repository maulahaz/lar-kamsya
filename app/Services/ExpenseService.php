<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function create(array $data, User $user): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $expense = Expense::query()->create([
                'code' => $this->sequences->expenseCode($data['date']),
                'date' => $data['date'],
                'category' => $data['category'],
                'amount' => round((float) $data['amount'], 2),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->logger->log('create', $expense, 'Mencatat beban '.$expense->code, [
                'amount' => (float) $expense->amount,
            ]);

            return $expense;
        });
    }

    public function void(Expense $expense, string $reason, User $user): void
    {
        if ($expense->isVoided()) {
            throw ValidationException::withMessages(['expense' => 'Beban ini sudah di-void.']);
        }

        $expense->forceFill([
            'voided_at' => now(),
            'void_reason' => $reason,
            'voided_by' => $user->id,
        ])->save();

        $this->logger->log('void', $expense, 'Void beban '.$expense->code, [
            'reason' => $reason,
        ]);
    }
}
