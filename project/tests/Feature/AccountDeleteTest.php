<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\AccountInUseException;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\MoonShine\Resources\Account\AccountResource;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Core\Exceptions\ResourceException;
use Tests\TestCase;

class AccountDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_with_journal_entries_cannot_be_deleted(): void
    {
        $account = Account::factory()->create();
        $otherAccount = Account::factory()->create();
        $transaction = Transaction::factory()->create(['is_posted' => true]);

        JournalEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => 100,
            'type' => TypeEntryEnum::DEBIT->value,
        ]);
        JournalEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $otherAccount->id,
            'amount' => 100,
            'type' => TypeEntryEnum::CREDIT->value,
        ]);

        $resource = app(AccountResource::class);
        $resource->setItem($account);

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage(AccountInUseException::delete()->getMessage());

        $resource->delete($resource->getCaster()->cast($account));
    }

    public function test_account_without_journal_entries_can_be_deleted(): void
    {
        $account = Account::factory()->create();

        $resource = app(AccountResource::class);
        $resource->setItem($account);

        $this->assertTrue($resource->delete($resource->getCaster()->cast($account)));
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
