<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBalanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_accounts(): void
    {
        $user = User::factory()->create([
            "email" => "api@example.com",
            "password" => "password",
        ]);

        $account = Account::factory()->create([
            "code" => "1010",
            "name" => "Cash",
            "type" => "asset",
            "is_active" => true,
        ]);

        Account::factory()->create(["is_active" => false]);

        $response = $this->withBasicAuth($user->email, "password")
            ->getJson("/api/v1/accounts");

        $response
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.id", $account->id)
            ->assertJsonPath("data.0.code", "1010");
    }

    public function test_it_returns_account_balance(): void
    {
        $user = User::factory()->create([
            "email" => "api@example.com",
            "password" => "password",
        ]);

        $account = Account::factory()->create([
            "code" => "1010",
            "name" => "Cash",
            "type" => "asset",
        ]);

        $otherAccount = Account::factory()->create(["type" => "revenue"]);
        $transaction = Transaction::factory()->create();

        JournalEntry::factory()->create([
            "transaction_id" => $transaction->id,
            "account_id" => $account->id,
            "amount" => 300,
            "type" => TypeEntryEnum::DEBIT->value,
        ]);

        JournalEntry::factory()->create([
            "transaction_id" => $transaction->id,
            "account_id" => $otherAccount->id,
            "amount" => 300,
            "type" => TypeEntryEnum::CREDIT->value,
        ]);

        $response = $this->withBasicAuth($user->email, "password")
            ->getJson("/api/v1/accounts/{$account->id}/balance");

        $response
            ->assertOk()
            ->assertJsonPath("data.account_id", $account->id)
            ->assertJsonPath("data.debit_total", "300.00")
            ->assertJsonPath("data.credit_total", "0.00")
            ->assertJsonPath("data.balance", "300.00");
    }

    public function test_balance_excludes_unposted_transactions(): void
    {
        $user = User::factory()->create([
            "email" => "api@example.com",
            "password" => "password",
        ]);

        $account = Account::factory()->create([
            "code" => "1010",
            "name" => "Cash",
            "type" => "asset",
        ]);

        $draftTransaction = Transaction::factory()->create(["is_posted" => false]);

        JournalEntry::factory()->create([
            "transaction_id" => $draftTransaction->id,
            "account_id" => $account->id,
            "amount" => 500,
            "type" => TypeEntryEnum::DEBIT->value,
        ]);

        $response = $this->withBasicAuth($user->email, "password")
            ->getJson("/api/v1/accounts/{$account->id}/balance");

        $response
            ->assertOk()
            ->assertJsonPath("data.debit_total", "0.00")
            ->assertJsonPath("data.balance", "0.00");
    }
}
