<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Services\AccountBalanceService;
use App\Services\TrialBalanceService;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_balance_ignores_unposted_transactions(): void
    {
        $account = Account::factory()->create(['type' => 'asset']);
        $otherAccount = Account::factory()->create(['type' => 'revenue']);

        $postedTransaction = Transaction::factory()->create(['is_posted' => true]);
        JournalEntry::factory()->create([
            'transaction_id' => $postedTransaction->id,
            'account_id' => $account->id,
            'amount' => 100,
            'type' => TypeEntryEnum::DEBIT->value,
        ]);
        JournalEntry::factory()->create([
            'transaction_id' => $postedTransaction->id,
            'account_id' => $otherAccount->id,
            'amount' => 100,
            'type' => TypeEntryEnum::CREDIT->value,
        ]);

        $draftTransaction = Transaction::factory()->create(['is_posted' => false]);

        JournalEntry::factory()->create([
            'transaction_id' => $draftTransaction->id,
            'account_id' => $account->id,
            'amount' => 500,
            'type' => TypeEntryEnum::DEBIT->value,
        ]);

        $balance = app(AccountBalanceService::class)->getBalance($account);

        $this->assertSame('100.00', $balance['debit_total']);
        $this->assertSame('0.00', $balance['credit_total']);
        $this->assertSame('100.00', $balance['balance']);
    }

    public function test_trial_balance_ignores_unposted_transactions(): void
    {
        $account = Account::factory()->create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $otherAccount = Account::factory()->create(['type' => 'revenue', 'is_active' => true]);

        $postedTransaction = Transaction::factory()->create([
            'date' => '2026-06-10',
            'is_posted' => true,
        ]);
        JournalEntry::factory()->create([
            'transaction_id' => $postedTransaction->id,
            'account_id' => $account->id,
            'amount' => 200,
            'type' => TypeEntryEnum::DEBIT->value,
        ]);
        JournalEntry::factory()->create([
            'transaction_id' => $postedTransaction->id,
            'account_id' => $otherAccount->id,
            'amount' => 200,
            'type' => TypeEntryEnum::CREDIT->value,
        ]);

        $draftTransaction = Transaction::factory()->create([
            'date' => '2026-06-10',
            'is_posted' => false,
        ]);
        JournalEntry::factory()->create([
            'transaction_id' => $draftTransaction->id,
            'account_id' => $account->id,
            'amount' => 900,
            'type' => TypeEntryEnum::DEBIT->value,
        ]);

        $rows = app(TrialBalanceService::class)->build('2026-06-01', '2026-06-30');
        $cashRow = collect($rows)->first(
            static fn (array $row): bool => ($row['code'] ?? '') === '1010',
        );

        $this->assertNotNull($cashRow);
        $this->assertSame('200.00', $cashRow['turnover_debit']);
        $this->assertSame('0.00', $cashRow['turnover_credit']);
    }
}
