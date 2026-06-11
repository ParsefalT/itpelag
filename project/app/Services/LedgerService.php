<?php
// namespace App\Services;
// namespace App\Services;
// use App\Models\Transaction;
// use App\Models\JournalEntry;
// use App\TypeEntryEnum;
// use Illuminate\Support\Facades\DB;

// class LedgerService
// {
//     /**
//      * создается транзакция с проводками.
//      *
//      * @param array $transactionData Данные транзакции (date, description)
//      * @param array $entriesData Массив проводок
//      * @return Transaction
//      * @throws \Exception Если дебет не равен кредиту или проводок меньше 2
//      */
//     public function createBalancedTransaction(
//         array $transactionData,
//         array $entriesData,
//     ): Transaction {
//         if (count($entriesData) < 2) {
//             throw new \Exception(
//                 "Транзакция должна содержать минимум 2 проводки.",
//             );
//         }

//         $debitSum = collect($entriesData)
//             ->where("type", TypeEntryEnum::DEBIT->value)
//             ->sum("amount");
//         $creditSum = collect($entriesData)
//             ->where("type", TypeEntryEnum::CREDIT->value)
//             ->sum("amount");

//         if (bccomp((string) $debitSum, (string) $creditSum, 2) !== 0) {
//             throw new \Exception(
//                 "Сумма дебета должна быть равна сумме кредита.",
//             );
//         }

//         return DB::transaction(function () use (
//             $transactionData,
//             $entriesData,
//         ) {
//             $transaction = Transaction::create($transactionData);

//             foreach ($entriesData as $entry) {
//                 JournalEntry::create([
//                     "transaction_id" => $transaction->id,
//                     "account_id" => $entry["account_id"],
//                     "amount" => $entry["amount"],
//                     "type" => $entry["type"],
//                 ]);
//             }

//             return $transaction->load("journalEntries.account");
//         });
//     }
// }
namespace App\Services;

use App\Exceptions\PostedTransactionException;
use App\Models\Transaction;
use App\Models\JournalEntry;
use App\TypeEntryEnum;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Создание сбалансированной транзакции
     */
    public function createTransaction(array $data, array $entries): Transaction
    {
        $this->validateEntries($entries);

        return DB::transaction(function () use ($data, $entries) {
            $transaction = Transaction::create($data);
            
            foreach ($entries as $entry) {
                JournalEntry::create([
                    "transaction_id" => $transaction->id,
                    "account_id" => $entry["account_id"],
                    "amount" => $entry["amount"],
                    "type" => $entry["type"],
                ]);
            }

            return $transaction->refresh()->load("journalEntries.account");
        });
    }

    /**
     * Обновление транзакции и её проводок
     */
    public function updateTransaction(
        Transaction $transaction,
        array $data,
        array $entries,
    ): Transaction {
        $this->ensureNotPosted($transaction);
        $this->validateEntries($entries);

        return DB::transaction(function () use ($transaction, $data, $entries) {
            // Удаляем старые проводки и создаем новые (для гарантии целостности)
            $transaction->journalEntries()->delete();
            $transaction->update($data);

            foreach ($entries as $entry) {
                JournalEntry::create([
                    "transaction_id" => $transaction->id,
                    "account_id" => $entry["account_id"],
                    "amount" => $entry["amount"],
                    "type" => $entry["type"],
                ]);
            }

            return $transaction->refresh()->load("journalEntries.account");
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        if ($transaction->isPosted()) {
            throw PostedTransactionException::delete();
        }

        $transaction->delete();
    }

    private function ensureNotPosted(Transaction $transaction): void
    {
        if ($transaction->isPosted()) {
            throw PostedTransactionException::modify();
        }
    }

    /**
     * Валидация правил двойной записи
     */
    private function validateEntries(array $entries): void
    {
        if (count($entries) < 2) {
            throw new \InvalidArgumentException(
                "Транзакция должна содержать минимум 2 проводки.",
            );
        }

        $debitSum = 0;
        $creditSum = 0;

        foreach ($entries as $entry) {
            $amount = (int) round(((float) $entry["amount"]) * 100);

            if ($entry["type"] === TypeEntryEnum::DEBIT->value) {
                $debitSum += $amount;
            } elseif ($entry["type"] === TypeEntryEnum::CREDIT->value) {
                $creditSum += $amount;
            }
        }

        if ($debitSum !== $creditSum) {
            $debitFormatted = number_format($debitSum / 100, 2, ".", "");
            $creditFormatted = number_format($creditSum / 100, 2, ".", "");

            throw new \InvalidArgumentException(
                "Сумма дебета ({$debitFormatted}) должна быть равна сумме кредита ({$creditFormatted}).",
            );
        }
    }
}
