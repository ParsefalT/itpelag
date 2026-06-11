<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashAccount = Account::where("code", "128")->first();
        $revenueAccount = Account::where("code", "200")->first();
        $expenseAccount = Account::where("code", "135")->first();

        $transaction1 = Transaction::create([
            "date" => now(),
            "description" => "Оплата по счету",
        ]);

        JournalEntry::create([
            "transaction_id" => $transaction1->id,
            "account_id" => $cashAccount->id,
            "amount" => 250.0,
            "type" => "debit",
        ]);

        JournalEntry::create([
            "transaction_id" => $transaction1->id,
            "account_id" => $revenueAccount->id,
            "amount" => 250.0,
            "type" => "credit",
        ]);

        $transaction2 = Transaction::create([
            "date" => now()->subDays(5),
            "description" => "Ежемесячная оплата аренды офиса",
        ]);

        JournalEntry::create([
            "transaction_id" => $transaction2->id,
            "account_id" => $expenseAccount->id,
            "amount" => 500.5,
            "type" => "debit",
        ]);

        JournalEntry::create([
            "transaction_id" => $transaction2->id,
            "account_id" => $cashAccount->id,
            "amount" => 700.0,
            "type" => "credit",
        ]);
    }
}
