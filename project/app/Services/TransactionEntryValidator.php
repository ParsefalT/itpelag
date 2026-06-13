<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\Transaction;
use App\TypeEntryEnum;

final class TransactionEntryValidator
{
    /**
     * @return list<array{amount: float|int|string, type: string}>
     */
    public function entriesForTransaction(
        Transaction $transaction,
        ?JournalEntry $except = null,
        ?JournalEntry $replacement = null,
    ): array {
        $entries = $transaction->journalEntries()
            ->when(
                $except?->exists,
                static fn ($query) => $query->whereKeyNot($except->id),
            )
            ->get()
            ->map(static fn (JournalEntry $entry): array => [
                "amount" => $entry->amount,
                "type" => $entry->type instanceof TypeEntryEnum
                    ? $entry->type->value
                    : (string) $entry->type,
            ])
            ->all();

        if ($replacement !== null) {
            $entries[] = [
                "amount" => $replacement->amount,
                "type" => $replacement->type instanceof TypeEntryEnum
                    ? $replacement->type->value
                    : (string) $replacement->type,
            ];
        }

        return $entries;
    }

    public function assertBalancedWhenComplete(Transaction $transaction, ?JournalEntry $except = null, ?JournalEntry $replacement = null): void
    {
        $entries = $this->entriesForTransaction($transaction, $except, $replacement);

        app(LedgerService::class)->validateEntries($entries);
    }
}
