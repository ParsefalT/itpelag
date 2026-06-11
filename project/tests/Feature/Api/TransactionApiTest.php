<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            "email" => "api@example.com",
            "password" => "password",
        ]);
    }

    public function test_store_creates_transaction_via_api(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $response = $this->withBasicAuth($this->user->email, "password")
            ->postJson("/api/v1/transactions", [
                "date" => "2026-06-11",
                "description" => "API transaction",
                "entries" => [
                    [
                        "account_id" => $debitAccount->id,
                        "amount" => 200,
                        "type" => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        "account_id" => $creditAccount->id,
                        "amount" => 200,
                        "type" => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath("data.description", "API transaction")
            ->assertJsonPath("data.is_posted", true)
            ->assertJsonCount(2, "data.journal_entries");

        $this->assertDatabaseHas("transactions", [
            "description" => "API transaction",
            "is_posted" => true,
        ]);
    }

    public function test_store_rejects_unbalanced_entries(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $response = $this->withBasicAuth($this->user->email, "password")
            ->postJson("/api/v1/transactions", [
                "date" => "2026-06-11",
                "description" => "Broken transaction",
                "entries" => [
                    [
                        "account_id" => $debitAccount->id,
                        "amount" => 100,
                        "type" => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        "account_id" => $creditAccount->id,
                        "amount" => 90,
                        "type" => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonStructure(["error"]);

        $this->assertDatabaseMissing("transactions", [
            "description" => "Broken transaction",
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/transactions", [
            "date" => "2026-06-11",
            "description" => "No auth",
            "entries" => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_request_payload(): void
    {
        $response = $this->withBasicAuth($this->user->email, "password")
            ->postJson("/api/v1/transactions", []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(["date", "description", "entries"]);
    }
}
