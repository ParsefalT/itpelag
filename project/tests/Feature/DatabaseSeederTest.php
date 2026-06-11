<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_balanced_posted_transactions(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(2, Account::count());
        $this->assertGreaterThanOrEqual(2, Transaction::count());

        Transaction::query()
            ->with("journalEntries")
            ->each(function (Transaction $transaction): void {
                $this->assertGreaterThanOrEqual(
                    2,
                    $transaction->journalEntries->count(),
                    $transaction->description,
                );
                $this->assertTrue(
                    $transaction->isBalanced(),
                    $transaction->description,
                );
                $this->assertTrue(
                    $transaction->isPosted(),
                    $transaction->description,
                );
            });
    }
}
