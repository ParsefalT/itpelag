<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\TypeEntryEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntry> */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'type' => fake()->randomElement(TypeEntryEnum::cases())->value,
        ];
    }
}
