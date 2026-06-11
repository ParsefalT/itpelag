<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\Transaction;

final class JournalEntryObserver
{
    public function saved(JournalEntry $journalEntry): void
    {
        $this->syncPostedStatus($journalEntry->transaction);
    }

    public function deleted(JournalEntry $journalEntry): void
    {
        $this->syncPostedStatus(
            Transaction::query()->find($journalEntry->transaction_id),
        );
    }

    private function syncPostedStatus(?Transaction $transaction): void
    {
        if ($transaction === null) {
            return;
        }

        $transaction->refresh();
        $isPosted = $transaction->shouldBePosted();

        if ($transaction->is_posted === $isPosted) {
            return;
        }

        $transaction->forceFill(["is_posted" => $isPosted])->saveQuietly();
    }
}
