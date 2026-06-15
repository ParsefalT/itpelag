<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = app(LedgerService::class);
    }

    public function test_it_creates_balanced_transaction_with_journal_entries(): void
    {
        $debitAccount = Account::factory()->create(['type' => 'asset']);
        $creditAccount = Account::factory()->create(['type' => 'revenue']);

        $transaction = $this->ledgerService->createTransaction(
            [
                'date' => '2026-06-11',
                'description' => 'Test payment',
            ],
            [
                [
                    'account_id' => $debitAccount->id,
                    'amount' => 150.25,
                    'type' => TypeEntryEnum::DEBIT->value,
                ],
                [
                    'account_id' => $creditAccount->id,
                    'amount' => 150.25,
                    'type' => TypeEntryEnum::CREDIT->value,
                ],
            ],
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $transaction->refresh();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Test payment',
            'is_posted' => true,
        ]);
        $this->assertCount(2, $transaction->journalEntries);
        $this->assertTrue($transaction->isBalanced());
        $this->assertTrue($transaction->isPosted());
    }

    public function test_it_rejects_unbalanced_debit_and_credit_totals(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Сумма дебета');

        $this->ledgerService->createTransaction(
            [
                'date' => '2026-06-11',
                'description' => 'Unbalanced',
            ],
            [
                [
                    'account_id' => $debitAccount->id,
                    'amount' => 100,
                    'type' => TypeEntryEnum::DEBIT->value,
                ],
                [
                    'account_id' => $creditAccount->id,
                    'amount' => 50,
                    'type' => TypeEntryEnum::CREDIT->value,
                ],
            ],
        );
    }

    public function test_it_requires_at_least_two_journal_entries(): void
    {
        $account = Account::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('минимум 2 проводки');

        $this->ledgerService->createTransaction(
            [
                'date' => '2026-06-11',
                'description' => 'Single entry',
            ],
            [
                [
                    'account_id' => $account->id,
                    'amount' => 100,
                    'type' => TypeEntryEnum::DEBIT->value,
                ],
            ],
        );
    }
}
