<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\Transaction;

final class JournalEntryObserver
{
    public function saved(JournalEntry $journalEntry): void
    {
        $transaction = $journalEntry->transaction()->first();

        $this->syncPostedStatus($transaction instanceof Transaction ? $transaction : null);
    }

    public function deleted(JournalEntry $journalEntry): void
    {
        $this->syncPostedStatus(
            Transaction::query()->find($journalEntry->getAttribute('transaction_id')),
        );
    }

    private function syncPostedStatus(?Transaction $transaction): void
    {
        if ($transaction === null) {
            return;
        }

        $transaction->refresh();
        $isPosted = $transaction->shouldBePosted();

        if ($transaction->isPosted() === $isPosted) {
            return;
        }

        $transaction->forceFill(['is_posted' => $isPosted])->saveQuietly();
    }
}
