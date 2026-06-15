<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashAccount = Account::where('code', '128')->firstOrFail();
        $revenueAccount = Account::where('code', '200')->firstOrFail();
        $expenseAccount = Account::where('code', '135')->firstOrFail();

        if (! Transaction::where('description', 'Оплата по счету')->exists()) {
            $transaction1 = Transaction::create([
                'date' => now(),
                'description' => 'Оплата по счету',
            ]);

            JournalEntry::create([
                'transaction_id' => $transaction1->id,
                'account_id' => $cashAccount->id,
                'amount' => 250.0,
                'type' => 'debit',
            ]);

            JournalEntry::create([
                'transaction_id' => $transaction1->id,
                'account_id' => $revenueAccount->id,
                'amount' => 250.0,
                'type' => 'credit',
            ]);

            $this->syncPostedStatus($transaction1);
        }

        if (! Transaction::where('description', 'Ежемесячная оплата аренды офиса')->exists()) {
            $transaction2 = Transaction::create([
                'date' => now()->subDays(5),
                'description' => 'Ежемесячная оплата аренды офиса',
            ]);

            JournalEntry::create([
                'transaction_id' => $transaction2->id,
                'account_id' => $expenseAccount->id,
                'amount' => 500.5,
                'type' => 'debit',
            ]);

            JournalEntry::create([
                'transaction_id' => $transaction2->id,
                'account_id' => $cashAccount->id,
                'amount' => 500.5,
                'type' => 'credit',
            ]);

            $this->syncPostedStatus($transaction2);
        }
    }

    private function syncPostedStatus(Transaction $transaction): void
    {
        $transaction->refresh();

        $transaction->forceFill([
            'is_posted' => $transaction->shouldBePosted(),
        ])->saveQuietly();
    }
}
